<?php
/**
 * File name: OrderAPIController.php
 * Last modified: 2020.05.31 at 19:34:40
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;


use App\Criteria\Orders\OrdersOfStatusesCriteria;
use App\Criteria\Orders\OrdersOfUserCriteria;
use App\Events\OrderChangedEvent;
use App\Http\Controllers\API\payment\auth\PaymentAuthAPIController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\AssignedOrder;
use App\Notifications\NewOrder;
use App\Notifications\StatusChangedOrder;
use App\Repositories\CartRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductOrderRepository;
use App\Repositories\UserRepository;
use DateTime;
use Flash;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;
use stdClass;
use Stripe\Token;
use GuzzleHttp\Exception\RequestException;

session_start();

/**
 * Class OrderController
 * @package App\Http\Controllers\API
 */
class OrderAPIController extends Controller
{
    /** @var  OrderRepository */
    private $orderRepository;
    /** @var  ProductOrderRepository */
    private $productOrderRepository;
    /** @var  CartRepository */
    private $cartRepository;
    /** @var  UserRepository */
    private $userRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;
    /** @var  paymentAuthAPIRepo */
    private $paymentAuthAPIRepo;

    /**
     * OrderAPIController constructor.
     * @param OrderRepository $orderRepo
     * @param ProductOrderRepository $productOrderRepository
     * @param CartRepository $cartRepo
     * @param PaymentRepository $paymentRepo
     * @param NotificationRepository $notificationRepo
     * @param UserRepository $userRepository
     * @param UserRepository $paymentAuthAPIController
     */
    public function __construct(OrderRepository $orderRepo, ProductOrderRepository $productOrderRepository, CartRepository $cartRepo, PaymentRepository $paymentRepo, NotificationRepository $notificationRepo, UserRepository $userRepository, PaymentAuthAPIController $paymentAuthAPIController)
    {
        $this->orderRepository = $orderRepo;
        $this->productOrderRepository = $productOrderRepository;
        $this->cartRepository = $cartRepo;
        $this->userRepository = $userRepository;
        $this->paymentRepository = $paymentRepo;
        $this->notificationRepository = $notificationRepo;
        $this->paymentAuthAPIRepo = $paymentAuthAPIController;
    }

    /**
     * Display a listing of the Order.
     * GET|HEAD /orders
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->orderRepository->pushCriteria(new RequestCriteria($request));
            $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->orderRepository->pushCriteria(new OrdersOfStatusesCriteria($request));
            $this->orderRepository->pushCriteria(new OrdersOfUserCriteria(auth()->id()));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $orders = $this->orderRepository->all();

        return $this->sendResponse($orders->toArray(), 'Orders retrieved successfully');
    }

    /**
     * Display the specified Order.
     * GET|HEAD /orders/{id}
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        /** @var Order $order */
        if (!empty($this->orderRepository)) {
            try {
                $this->orderRepository->pushCriteria(new RequestCriteria($request));
                $this->orderRepository->pushCriteria(new LimitOffsetCriteria($request));
            } catch (RepositoryException $e) {
                return $this->sendError($e->getMessage());
            }
            $order = $this->orderRepository->findWithoutFail($id);
        }

        if (empty($order)) {
            return $this->sendError('Order not found');
        }
        return $this->sendResponse($order->toArray(), 'Order retrieved successfully');
    }

    /**
     * Store a newly created Order in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payment = $request->only('payment');
        if (isset($payment['payment']) && $payment['payment']['method']) {
            if ($payment['payment']['method'] == "Credit Card (Stripe Gateway)") {
                return $this->stripPayment($request);
            } else if ($payment['payment']['method'] === "qpay") {
                $this->paymentAuthAPIRepo->token();
                $response = $this->cashPayment($request);

                $res = $this->setQPayPayment($response);
                if ($res->success) {
                    return $this->sendResponse($res->message, __('lang.saved_successfully', ['operator' => __('lang.order')]));
                } else {
                    error_log($res->message);
                    return $this->sendError($res->message, 500);
                }
            } else {
                $response = $this->cashPayment($request);
                if ($response) {
                    return $this->sendResponse($response, __('lang.saved_successfully', ['operator' => __('lang.order')]));
                }
            }
        }
    }

    private function setQPayPayment($response) {
        $order = DB::table('product_orders')
            ->where('order_id', $response->getData()->data->id)
            ->get();

        if ($order->count() === 0) {
            return $this->sendError('Захилга олдсонгүй');
        }

        $totalAmount = 0.0;
        for($i=0; $i < $order->count(); $i++) {
            $totalAmount += $order[$i]->price;
        }

        $market = DB::table('markets')
            ->find($response->getData()->data->product_orders[0]->product->market->id);

        if(!$market) {
            return $this->sendError('Салбарын мэдээлэл байхгүй байна');
        }

        $line = array (
            "line_description" => $response->getData()->data->id . '',
            "line_quantity" => "1.00",
            "line_unit_price" => $totalAmount,
            "discounts" => [],
            "surcharges" => [],
            "taxes" => []
        );

        error_log('url : '.env('9000IP').'/api/payment/invoice/qpay-check/'.$response->getData()->data->id);

        $test = (object) array();
        $reData = array(
            "invoice_code" => "DULGUUN_INVOICE",
            "sender_invoice_no" => $this->makeSenderInvoiceNo(),
            "sender_branch_code" => $market->id.'',
            "invoice_receiver_code" => "terminal",
            "invoice_receiver_data" => $test,
            "invoice_description" => $response->getData()->data->id . '',
            "callback_url" => env('9000IP').'/api/payment/invoice/qpay-check/'.$response->getData()->data->id,
            "lines" => []
        );
        $reData['lines'][0] = $line;
        try {

            $client = new Client();
            $request = $client->request(
                'POST',
                env('PAYMENT_IP') . '/v2/invoice',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token'],
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($reData)
                ]
            );

            if ($request->getStatusCode() === 200) {
                $invoiceRes = json_decode($request->getBody());
                error_log(json_encode($request));
                $insertInvoice = DB::table('invoice')
                    ->updateOrInsert(
                        [
                            'user_id' => $response->getData()->data->user->id,
                            'order_id' => $response->getData()->data->id
                        ],
                        [
                            'user_id' => $response->getData()->data->user->id,
                            'order_id' => $response->getData()->data->id,
                            'active' => true,
                            'accepted' => false,
                            'invoice_id' => $invoiceRes->invoice_id,
                            'qr_text' => $invoiceRes->qr_text,
                            'start_date' => new DateTime('NOW')
                        ]
                    );
                $response->original['data']['urls'] = $invoiceRes->urls;
                $result = new stdClass();
                $result->message = $response;
                $result->success = true;
                return $result;
            }
            $result = new stdClass();
            $result->message = 'Захиалга үүсгэж чадсангүй';
            $result->success = false;
            return $result;
        } catch (RequestException $e) {
            $result = new stdClass();
            $result->message =  $e->getMessage();
            $result->success = false;
            return $result;
        }
    }

    private function getOrderListener() {
        $now = new DateTime('NOW');
        $now->modify('+30 minute');
        $invoiceNotAccepted = DB::table('invoice')
            ->where('active', true)
            ->where('accepted', false)
            ->where('start_date', '<' ,$now)
            ->get();
        if (count($invoiceNotAccepted) > 0) {
            $this->checkInvoice($invoiceNotAccepted);
        }

        $invoiceRequestTimeOut = DB::table('invoice')
            ->where('active', true)
            ->where('accepted', false)
            ->where('start_date', '>' ,$now)
            ->get();
        if (count($invoiceRequestTimeOut) > 0) {
            $this->cancelForInvoiceRequest($invoiceRequestTimeOut);
        }
    }

    private function cancelForInvoiceRequest($invoiceRequestTimeOut) {
        foreach ($invoiceRequestTimeOut as $invoice) {
            DB::table('invoice')
                ->where('id', $invoice->id)
                ->update(['active' => false]);
        }
    }

    private function checkInvoice($invoiceNotAccepted) {

        foreach ($invoiceNotAccepted as $invoice) {
            $offset = array (
                "page_number" => 1,
                "page_limit" => 100
            );
            $reData = array(
                "object_type" => "INVOICE",
                "object_id" => $invoice->invoice_id,
                "offset" => []
            );
            $reData['offset'] = $offset;

            $client = new Client();
            $request = $client->request(
                'POST',
                env('PAYMENT_IP') . '/v2/payment/check',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token'],
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode($reData)
                ]
            );
            if ($request->getStatusCode() === 200) {
                $response = json_decode($request->getBody());
                if (count($response->rows) > 0) {
                    if ($response->rows[0]->payment_status === 'PAID') {
                        $now = new DateTime('NOW');
                        DB::table('invoice')
                            ->where('id', $invoice->id)
                            ->update(['accepted' => true, 'accept_date' => $now]);
                    }
                }
            }
        }
    }

    public function paymentChecker($invoiceId)
    {
        if (!$invoiceId) {
            return $this->sendError($invoiceId.' : Хүсэлтын утга байхгүй байна', '500');
        }
        $offset = array ("page_number" => 1, "page_limit" => 100 );
        $reData = array(
            "object_type" => "INVOICE",
            "object_id" => $invoiceId,
            "offset" => []
        );
        $reData['offset'] = $offset;

        $client = new Client();
        $request = $client->request(
            'POST',
            env('PAYMENT_IP') . '/v2/payment/check',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token'],
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($reData)
            ]
        );
        if ($request->getStatusCode() === 200) {
            $response = json_decode($request->getBody());

            return $this->sendResponse($response, 200);
        }
        return $this->sendError('Хүсэлтыг авж чадсангүй', 500);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function stripPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;
        try {
            $user = $this->userRepository->findWithoutFail($input['user_id']);
            if (empty($user)) {
                return $this->sendError('User not found');
            }
            $stripeToken = Token::create(array(
                "card" => array(
                    "number" => $input['stripe_number'],
                    "exp_month" => $input['stripe_exp_month'],
                    "exp_year" => $input['stripe_exp_year'],
                    "cvc" => $input['stripe_cvc'],
                    "name" => $user->name,
                )
            ));
            if ($stripeToken->created > 0) {
                if (empty($input['delivery_address_id'])) {
                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'hint', 'employee_appointment_during')
                    );
                } else {
                    $order = $this->orderRepository->create(
                        $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id', 'delivery_fee', 'hint', 'employee_appointment_during')
                    );
                }
                foreach ($input['products'] as $productOrder) {
                    $productOrder['order_id'] = $order->id;
                    $amount += $productOrder['price'] * $productOrder['quantity'];
                    $this->productOrderRepository->create($productOrder);
                }
                $amount += $order->delivery_fee;
                $amountWithTax = $amount + ($amount * $order->tax / 100);
                $charge = $user->charge((int)($amountWithTax * 100), ['source' => $stripeToken]);
                $payment = $this->paymentRepository->create([
                    "user_id" => $input['user_id'],
                    "description" => trans("lang.payment_order_done"),
                    "price" => $amountWithTax,
                    "status" => $charge->status, // $charge->status
                    "method" => $input['payment']['method'],
                ]);
                $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

                $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);
                Notification::send($order->productOrders[0]->product->market->users, new NewOrder($order));
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    private function cashPayment(Request $request)
    {
        $input = $request->all();
        $amount = 0;
        try {
            $order = $this->orderRepository->create(
                $request->only('user_id', 'order_status_id', 'tax', 'delivery_address_id', 'delivery_fee', 'hint', 'employee_appointment_during', 'coupon_id', 'coupon_name', 'coupon_price')
            );
            Log::info($input['products']);
            foreach ($input['products'] as $productOrder) {
                $productOrder['order_id'] = $order->id;
                $amount += $productOrder['price'] * $productOrder['quantity'];
                $this->productOrderRepository->create($productOrder);
            }
            $amount += $order->delivery_fee;
            $amountWithTax = $amount + ($amount * $order->tax / 100);
            $payment = $this->paymentRepository->create([
                "user_id" => $input['user_id'],
                "description" => trans("lang.payment_order_waiting"),
                "price" => $amountWithTax,
                "status" => 'Waiting for Client',
                "method" => $input['payment']['method'],
            ]);

            $this->orderRepository->update(['payment_id' => $payment->id], $order->id);

            $this->cartRepository->deleteWhere(['user_id' => $order->user_id]);

            Notification::send($order->productOrders[0]->product->market->users, new NewOrder($order));

        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        $appointment = DB::table('employee_appointments')->find($input['hint']);
        if ($appointment) {
            $employeeInformation = DB::table('users')->find($appointment->employee_id);
            if ($employeeInformation) {
                if ($order->employee_appointment_during && count(explode('|', $order->employee_appointment_during)) <= 3) {
                    $order->employee_appointment_during = $appointment->active_day.' | '.$appointment->start_date .' | '.$employeeInformation->name;
                }
            }
            DB::table('orders')->where('id', $order->id)->update(['employee_appointment_during' => $order->employee_appointment_during]);
        }
        return $this->sendResponse(
            $order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')])
        );
    }

    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $oldOrder = $this->orderRepository->findWithoutFail($id);
        if (empty($oldOrder)) {
            return $this->sendError('Order not found');
        }
        $oldStatus = $oldOrder->payment->status;
        $input = $request->all();

        try {
            $order = $this->orderRepository->update($input, $id);

            if (isset($input['order_status_id']) && $input['order_status_id'] == 5 && !empty($order)) {
                $this->paymentRepository->update(['status' => 'Paid'], $order['payment_id']);

                if ($request->input('hint') !== null) {

                    $selectedAppointment = DB::table('employee_appointments')
                        ->find($request->input('hint'));
                    if ($selectedAppointment) {
                        DB::table('employee_appointments')
                            ->where('employee_id', $selectedAppointment->employee_id)
                            ->where('active_day', $selectedAppointment->active_day)
                            ->where('start_date', $selectedAppointment->start_date)
                            ->where('end_date', $selectedAppointment->end_date)
                            ->update([
                                'user_id' => $order->user_id,
                                'is_active' => 1
                            ]);
                    }
                    $appointment = DB::table('employee_appointments')->find($request->input('hint'));
                    $employeeInformation = DB::table('users')->find($appointment->employee_id);
                    if ($employeeInformation) {
                        if ($order->employee_appointment_during && count(explode('|', $order->employee_appointment_during)) <= 3) {
                            $order->employee_appointment_during = $appointment->active_day.' | '.$appointment->start_date .' | '.$employeeInformation->name;
                        }
                    }

                    if($request->input('market_id')) {
                        $market = DB::table('markets')->find($request->input('market_id'));
                        if ($market) {
                            $order->balance_name = $market->balance_name;
                            $order->balance_number = $market->balance_number;
                            $order->name_of_bank = $market->name_of_bank;
                            $order->balance_min_value = $market->balance_min_value;
                        }
                    }
                }
            }
            event(new OrderChangedEvent($oldStatus, $order));

            if (setting('enable_notifications', false)) {
                if (isset($input['order_status_id']) && $input['order_status_id'] != $oldOrder->order_status_id) {
                    Notification::send([$order->user], new StatusChangedOrder($order));
                }

                if (isset($input['driver_id']) && ($input['driver_id'] != $oldOrder['driver_id'])) {
                    $driver = $this->userRepository->findWithoutFail($input['driver_id']);
                    if (!empty($driver)) {
                        Notification::send([$driver], new AssignedOrder($order));
                    }
                }
            }

        } catch (ValidatorException $e) {
            return $this->sendError('Exception: '.$e->getMessage());
        }

        return $this->sendResponse($order->toArray(), __('lang.saved_successfully', ['operator' => __('lang.order')]));
    }

    private function makeSenderInvoiceNo() {
        $reString = '9K';
        $reString .= date("YmdHis");
        return $reString;
    }
}
