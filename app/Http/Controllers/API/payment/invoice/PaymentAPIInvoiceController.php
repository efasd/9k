<?php
namespace App\Http\Controllers\API\payment\invoice;

use App\Events\OrderChangedEvent;
use App\Http\Controllers\API\OrderAPIController;
use App\Http\Controllers\Controller;
use App\Notifications\StatusChangedOrder;
use App\Repositories\OrderRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Notification;
use Prettus\Validator\Exceptions\ValidatorException;
use stdClass;

session_start();

class PaymentAPIInvoiceController extends Controller {

    private $orderRepository;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepository = $orderRepo;
    }

    public function get($invoiceId) {
        if (!$invoiceId) {
            return $this->sendError('Хүсэлтын утга байхгүй байна', '500');
        }
        $client = new Client();
        $request = $client->request(
            'GET',
            env('PAYMENT_IP') . '/v2/invoice/'.$invoiceId,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token'],
                    'Content-Type' => 'application/json'
                ]
            ]
        );
        if ($request->getStatusCode() === 200) {
            $response = json_decode($request->getBody());

            return $this->sendResponse($response, 200);
        }
        return $this->sendError('Хүсэлтыг авж чадсангүй', 500);
    }

    public function cancel($invoiceId, Request $request) {
        if (!$invoiceId) {
            return $this->sendError('Хүсэлтын утга байхгүй байна', '500');
        }
        try {
            $client = new Client();
            $request = $client->request(
                'DELETE',
                env('PAYMENT_IP') . '/v2/invoice/'.$invoiceId,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token']
                    ]
                ]
            );
            if ($request->getStatusCode() === 200) {
                $response = json_decode($request->getBody());

                return $this->sendResponse($response, 200);
            }
        } catch (ClientException $e) {
            return $this->sendResponse($e->getMessage(), 500);
        }
        return $this->sendError('Хүсэлтыг авж чадсангүй', 500);
    }

    public function qpaySuccess(Request $request) {
        $input = $request->all();
        if(isset($input['object_id'])){
            $invoice = DB::table('invoice')
                ->where('invoice_id', $input['object_id'])
                ->first();

            if($invoice) {
                DB::table('orders')
                    ->where('id', $invoice->order_id)
                    ->update(['order_status_id' => 5]);
                return $this->sendResponse(true, 'Invoice updated successfully');
            }
        }
        return $this->sendError('Хүсэлтийн утга байхгүй байна', '500');
    }

    public function qPayChecker($orderId) {

        $now = new \DateTime('NOW');
        if ($orderId) {
            $order = DB::table('orders')
                ->find($orderId);
            if ($order != null) {
                $invoice = DB::table('invoice')
                    ->where('order_id', $order->id)
                    ->update(['accepted' => true, 'accept_date' => $now]);
                if ($invoice) {
                    $oldOrder = $this->orderRepository->findWithoutFail($order->id);
                    if (empty($oldOrder)) {
                        return $this->sendError('Order not found');
                    }
                    try {
                        $order->order_status_id = 5;
                        $order = $this->orderRepository->update((array)$order, $order->id);
                        if (setting('enable_notifications', false)) {
                            Notification::send([$order->user], new StatusChangedOrder($order));
                        }
                    } catch (ValidatorException $e) {
                        return $this->sendError('Exception: '.$e->getMessage());
                    }
                }
            }
        }

        return $this->sendResponse(true, "Success bro");
    }

//    public function test() {
//        error_log('test');
//        try {
//            $client = new Client();
//            $request = $client->request(
//                'GET',
//                'https://9000.mn/api/products',
//                [
//                    'headers' => []
//                ]
//            );
//            error_log('test =>'.json_encode($request));
//            if ($request->getStatusCode() === 200) {
//                error_log(json_encode($request->getStatusCode()));
//                error_log(json_encode($request));
//                error_log(json_encode($request->getBody()->getContents()));
//                $response = json_decode($request->getBody());
//
//                return $this->sendResponse($response, 200);
//            }
//        } catch (\Exception $e) {
//            error_log('Exception :'.json_encode($e->getMessage()));
//            return $this->sendResponse('Энэ хүсэлт цуцлагдсан байна', 500);
//        } catch (GuzzleException $e) {
//            error_log('GuzzleException :'.json_encode($e->getMessage()));
//            return $this->sendResponse($e->getMessage(), 500);
//        }
//
//    }
}