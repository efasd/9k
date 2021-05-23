<?php

namespace App\Http\Controllers;

use App\Criteria\Users\ClientsCriteria;
use App\Criteria\Users\DriversCriteria;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\MarketRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderStatusRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use DB;
use DateTime;

class OrderCalendarController
{
    /** @var  OrderRepository */
    private $orderRepository;

    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /** @var  NotificationRepository */
    private $notificationRepository;
    /** @var  PaymentRepository */
    private $paymentRepository;
    /**
     * @var MarketRepository
     */
    private $marketRepository;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /** @var  ProductRepository */
    private $productRepository;

    public function __construct(OrderRepository $orderRepo, CustomFieldRepository $customFieldRepo, UserRepository $userRepo
        , OrderStatusRepository $orderStatusRepo, NotificationRepository $notificationRepo, PaymentRepository $paymentRepo
        , MarketRepository $marketRepo
        , CategoryRepository $categoryRepo
        , ProductRepository $productRepo)
    {
        $this->orderRepository = $orderRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->userRepository = $userRepo;
        $this->orderStatusRepository = $orderStatusRepo;
        $this->notificationRepository = $notificationRepo;
        $this->paymentRepository = $paymentRepo;
        $this->marketRepository = $marketRepo;
        $this->categoryRepository = $categoryRepo;
        $this->productRepository = $productRepo;
    }
    public function index()
    {
        return view('calendar.index');
    }

    public function create() {
        $category = $this->categoryRepository->pluck('name', 'id');
        $orderStatus = $this->orderStatusRepository->pluck('status', 'id');
        $hasCustomField = in_array($this->orderRepository->model(), setting('custom_field_models', []));
        if ($hasCustomField) {
            $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->orderRepository->model());
            $html = generateCustomField($customFields);
        }
        $market = DB::table('employee_markets')
            ->where('user_id', auth()->user()->getKey('id'))
            ->get();

        $employees = DB::table('employee_markets')
            ->where('market_id', $market->get(0)->market_id)
            ->get();

        $employeeList = [];
        foreach($employees as $employeeId) {
            $employeeName = User::find($employeeId->user_id)->name;
            array_push($employeeList, $employeeName);
        }
        $product = [];

        return view('calendar.create')
            ->with("customFields", isset($html) ? $html : false)
            ->with("category", $category)
            ->with("products", $product)
            ->with("employees", $employeeList)
            ->with("orderStatus", $orderStatus);
    }

    public function getEmployees(Request $request) {
        $employee = $request->input('employeeName');
        $user = DB::table('users')
            ->where('name', $request->input('employeeName'))
            ->get();

        $productIds = DB::table('employee_product')
            ->where('user_id', $user->get(0)->id)
            ->get();
        $productList = [];
        foreach($productIds as $productId) {
            $product = DB::table('products')->find($productId->product_id);
            array_push($productList, $product);
        }
        error_log(json_encode($productList));
        return $productList;
    }

    public function getAppointmentMonth(Request $request) {

        $market = DB::table('markets')->find($request->input('marketId'));

        if ($market->start_date == null || $market->end_date == null || $market->duration_range == null) {
            return $this->sendResponse(false, 'Маркет дээр эхлэх болон дуусах хугацаа оруулаагүй байна');
        }

        if ($request->input('employeeId')) {
            $employees = DB::table('employee_markets')
                ->where('user_id', $request->input('employeeId'))
                ->where('market_id', $request->input('marketId'))
                ->get();
        } else {
            $employees = DB::table('employee_markets')
                ->where('market_id', $request->input('marketId'))
                ->get();
        }

        if(count($employees) === 0) {
            return $this->sendError('Ажилтан салбар дээр бүртгэлгүй байна', 500);
        }

        $appointment = [];

        try {
            $startDate = new DateTime($request->input('startDate'));
            foreach($employees as $employee) {
                $employeeInfo = DB::table('users')->find($employee->user_id);

                $employeeAppointment = DB::table('employee_appointments')
                    ->where('employee_id', $employee->user_id)
                    ->where('active_day', 'like', $startDate->format('Y-m').'%')
                    ->get();

                foreach($employeeAppointment as $app) {
                    $orderList = [];
                    $orders = DB::table('orders')->where('hint', $app->id)->get();
                    foreach($orders as $order) {
                        $customerInfo = DB::table('users')->find( $order->user_id);
                        $order->customerInfo = $customerInfo;
                        array_push($orderList, $order);
                    }
                    $product = DB::table('products')->where('id', $app->product_id)->get();
                    if (count($orderList) > 0) {
                        $app->employee = $employeeInfo;
                        $app->product = $product;
                        $app->order = $orderList;
                        array_push($appointment,  (object) $app);
                    }
                }
            }
        } catch (\Exception $e) {

//            error_log(json_encode($e));
        }

        return $appointment;
    }

}