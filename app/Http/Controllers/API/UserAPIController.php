<?php
/**
 * File name: UserAPIController.php
 * Last modified: 2020.05.04 at 09:04:09
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;

use App\Console\Commands\PaymentChecker;
use App\Http\Controllers\Controller;
use App\Models\EmployeeAppointment;
use App\Models\User;
use App\Repositories\CustomFieldRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UploadRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Prettus\Validator\Exceptions\ValidatorException;
use DB;
use DateTime;

class UserAPIController extends Controller
{
    private $userRepository;
    private $uploadRepository;
    private $roleRepository;
    private $customFieldRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserRepository $userRepository, UploadRepository $uploadRepository, RoleRepository $roleRepository, CustomFieldRepository $customFieldRepo)
    {
        $this->userRepository = $userRepository;
        $this->uploadRepository = $uploadRepository;
        $this->roleRepository = $roleRepository;
        $this->customFieldRepository = $customFieldRepo;
    }

    function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                // Authentication passed...
                $user = auth()->user();
                $user->device_token = $request->input('device_token', '');
                $user->save();
                return $this->sendResponse($user, 'User retrieved successfully');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }

    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return
     */
    function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required|unique:users|email',
                'password' => 'required',
            ]);
            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->device_token = $request->input('device_token', '');
            $user->password = Hash::make($request->input('password'));
            $user->api_token = str_random(60);
            $user->save();

            $defaultRoles = $this->roleRepository->findByField('default', '1');
            $defaultRoles = $defaultRoles->pluck('name')->toArray();
            $user->assignRole($defaultRoles);


            if (copy(public_path('images/avatar_default.png'), public_path('images/avatar_default_temp.png'))) {
                $user->addMedia(public_path('images/avatar_default_temp.png'))
                    ->withCustomProperties(['uuid' => bcrypt(str_random())])
                    ->toMediaCollection('avatar');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function logout(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();
        if (!$user) {
            return $this->sendError('User not found', 401);
        }
        try {
            auth()->logout();
        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), 401);
        }
        return $this->sendResponse($user['name'], 'User logout successfully');

    }

    function user(Request $request)
    {
        $user = $this->userRepository->findByField('api_token', $request->input('api_token'))->first();

        if (!$user) {
            return $this->sendError('User not found', 401);
        }

        return $this->sendResponse($user, 'User retrieved successfully');
    }

    function settings(Request $request)
    {
        $settings = setting()->all();
        $settings = array_intersect_key($settings,
            [
                'default_tax' => '',
                'default_currency' => '',
                'default_currency_decimal_digits' => '',
                'app_name' => '',
                'currency_right' => '',
                'enable_paypal' => '',
                'enable_stripe' => '',
                'enable_razorpay' => '',
                'main_color' => '',
                'main_dark_color' => '',
                'second_color' => '',
                'second_dark_color' => '',
                'accent_color' => '',
                'accent_dark_color' => '',
                'scaffold_dark_color' => '',
                'scaffold_color' => '',
                'google_maps_key' => '',
                'mobile_language' => '',
                'app_version' => '',
                'enable_version' => '',
                'distance_unit' => '',
                'home_section_1'=> '',
                'home_section_2'=> '',
                'home_section_3'=> '',
                'home_section_4'=> '',
                'home_section_5'=> '',
                'home_section_6'=> '',
                'home_section_7'=> '',
                'home_section_8'=> '',
                'home_section_9'=> '',
                'home_section_10'=> '',
                'home_section_11'=> '',
                'home_section_12'=> '',
            ]
        );

        if (!$settings) {
            return $this->sendError('Settings not found', 401);
        }

        return $this->sendResponse($settings, 'Settings retrieved successfully');
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param Request $request
     *
     */
    public function update($id, Request $request)
    {
        $user = $this->userRepository->findWithoutFail($id);

        if (empty($user)) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        $input = $request->except(['password', 'api_token']);
        try {
            if ($request->has('device_token')) {
                $user = $this->userRepository->update($request->only('device_token'), $id);
            } else {
                $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->userRepository->model());
                $user = $this->userRepository->update($input, $id);

                foreach (getCustomFieldsValues($customFields, $request) as $value) {
                    $user->customFieldsValues()
                        ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
                }
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage(), 401);
        }

        return $this->sendResponse($user, __('lang.updated_successfully', ['operator' => __('lang.user')]));
    }

    function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = Password::broker()->sendResetLink(
            $request->only('email')
        );

        if ($response == Password::RESET_LINK_SENT) {
            return $this->sendResponse(true, 'Reset link was sent successfully');
        } else {
            return $this->sendError('Reset link not sent', 401);
        }
    }

    function getEmployees($id) {
        $user = $this->userRepository->findWithoutFail($id);
        $user = DB::table('users')
            ->where('id', '=', $id)
            ->where('id', '=', $id)
            ->get();

        if (empty($user)) {
            return $this->sendResponse([
                'error' => true,
                'code' => 404,
            ], 'User not found');
        }
        return $this->sendResponse(true, $user);
    }

    /**
     * getEmployeeAppointment
     * @param Timestamp $date you want to use date (DATE)
     * @param int $employeeId employee Id  (INT)
     * @param int $marketId market id you should wanna take the salon or something like that (INT)
     * @return time-schedule
     * @throws \Exception
     */
    function getEmployeeAppointment(Request $request) {
        $market = DB::table('markets')->find($request->input('marketId'));

        if ($market->start_date == null || $market->end_date == null || $market->duration_range == null) {
            return $this->sendResponse(false, 'Маркет дээр эхлэх болон дуусах хугацаа оруулаагүй байна');
        }

        $nowDateTimes = new DateTime();

        $employeeCheck = $this->getEmployeeCheck(
            $request->input('employeeId'),
            $request->input('marketId'),
            $request->input('date')
        );

        if (!$employeeCheck->success) {
            return $this->sendError($employeeCheck->message, 500);
        }

        $appointment = [];
        $numberOfWeek = new DateTime($request->input('date'));

        /* Employee active day of job*/
        $isEmployeeDuringJobDay = DB::table('active_job_days')
            ->where('employee_id', $request->input('employeeId'))
            ->get();

        $employeeActiveDays = [];
        $acceptedActiveDay = true;
        if (count($isEmployeeDuringJobDay) > 0) {
            $selectedDate = null;
            $employeeActiveDays = DB::table('active_job_days')
                ->where('employee_id', $request->input('employeeId'))
                ->where('day', ($numberOfWeek->format("N") - 1))
                ->where('active', 1)
                ->get();
            if (count($employeeActiveDays) == 0) {
                return $this->sendError('Ажилтан энэ '.($numberOfWeek->format("N") - 1).' ажилладагүй болно', 500);
            }
        }
        $now = date("Y-m-d H:i:s");
        if (strtotime(date("Y-m-d", strtotime($now))) == strtotime($request->input('date'))){
            if ($acceptedActiveDay) {
                DB::table('employee_appointments')
                    ->where('active_day', 'like', '%'.$request->input('date').'%')
                    ->where('is_active', '0')
                    ->where('employee_id', $request->input('employeeId'))
                    ->where('start_date', '>=', $nowDateTimes->format('H:i:s'))
                    ->where('active_day', '>=', $nowDateTimes->format('Y-m-d'))
                    ->update(['product_id' => $request->input('productId')]);

                $haveTimeLimit = false;
                if (!$haveTimeLimit) {
                    if ($employeeActiveDays && $employeeActiveDays[0]->start_date != '00:00:00' && $employeeActiveDays[0]->end_date != '00:00:00') {
                        $haveTimeLimit = true;
                    }
                }
                $appointment = null;
                if ($haveTimeLimit) {
                    $appointment = DB::table('employee_appointments')
                        ->where('active_day', 'like', '%'.$request->input('date').'%')
                        ->where('is_active', '0')
                        ->where('employee_id', $request->input('employeeId'))
                        ->where('start_date', '>=', $nowDateTimes->format('H:i:s'))
                        ->where('active_day', '>=', $nowDateTimes->format('Y-m-d'))
                        ->whereBetween('start_date', [$employeeActiveDays[0]->start_date, $employeeActiveDays[0]->end_date])
                        ->whereBetween('end_date', [$employeeActiveDays[0]->start_date, $employeeActiveDays[0]->end_date])
                        ->get();
                } else {
                    $appointment = DB::table('employee_appointments')
                        ->where('active_day', 'like', '%'.$request->input('date').'%')
                        ->where('is_active', '0')
                        ->where('employee_id', $request->input('employeeId'))
                        ->where('start_date', '>=', $nowDateTimes->format('H:i:s'))
                        ->where('active_day', '>=', $nowDateTimes->format('Y-m-d'))
                        ->get();
                }
                foreach ($appointment as $value) {
                    $value->start_date = substr($value->start_date, 0, -3);
                    $value->end_date = substr($value->end_date, 0, -3);
                    $discountPercent = $this->setAppointmentDiscount($request->input('productId'), $value->start_date, $request->input('date'));
                    $value->discount_percent = $discountPercent;
                }
            }
        } else {
            if ($acceptedActiveDay) {
                $updated = DB::table('employee_appointments')
                    ->where('active_day', 'like', '%' . $request->input('date') . '%')
                    ->where('is_active', '0')
                    ->where('employee_id', $request->input('employeeId'))
                    ->update(['product_id' => $request->input('productId')]);

                $haveTimeLimit = false;
                if (!$haveTimeLimit) {
                    if ($employeeActiveDays[0]->start_date != '00:00:00' && $employeeActiveDays[0]->end_date != '00:00:00') {
                        $haveTimeLimit = true;
                    }
                }
                $appointment = null;
                if ($haveTimeLimit) {
                    $appointment = DB::table('employee_appointments')
                        ->where('active_day', 'like', '%' . $request->input('date') . '%')
                        ->where('is_active', '0')
                        ->where('employee_id', $request->input('employeeId'))
                        ->whereBetween('start_date', [$employeeActiveDays[0]->start_date, $employeeActiveDays[0]->end_date])
                        ->whereBetween('end_date', [$employeeActiveDays[0]->start_date, $employeeActiveDays[0]->end_date])
                        ->get();
                } else {
                    $appointment = DB::table('employee_appointments')
                        ->where('active_day', 'like', '%' . $request->input('date') . '%')
                        ->where('is_active', '0')
                        ->where('employee_id', $request->input('employeeId'))
                        ->get();
                }

                foreach ($appointment as $value) {
                    $value->start_date = substr($value->start_date, 0, -3);
                    $value->end_date = substr($value->end_date, 0, -3);
                    $discountPercent = $this->setAppointmentDiscount($request->input('productId'), $value->start_date, $request->input('date'));
                    $value->discount_percent = $discountPercent;
                }
            }
        }
        if(count($appointment) > 0) {
            return $this->sendResponse(true, $appointment);
        } else {
            if (!$acceptedActiveDay) {
                return $this->sendResponse(true, 'Тухайн ажилтаны ажлын өдөр биш байна');
            }

            $activeDay = new DateTime(date_create($request->input('date'))->format('Y-m-d H:i:s'));
            $nowAddDays = new DateTime(date_create()->format('Y-m-d H:i:s'));
            date_add($nowAddDays, date_interval_create_from_date_string('16 days'));
            if ($nowAddDays <= $activeDay) {
                return $this->sendResponse(true, 'Та 7 ирэх хоногын дотор захиалга үүсгэх боломжтой');
            }

            $startDate = date_time_set(date_create($request->input('date')), date('H', strtotime($market->start_date)), date('i', strtotime($market->start_date)));
            $endDate = date_time_set(date_create($request->input('date')), date('H', strtotime($market->end_date)), date('i', strtotime($market->end_date)));

            $nowDateTime = new DateTime();

            $betweenDates = [];
            while($startDate < $endDate) {
                $dates = array();
                $nextHourMinute = date('H:i', strtotime($startDate->format('H:i')) + $market->duration_range * 60);

                $startDateClone = new DateTime($startDate->format('Y-m-d H:i:s'));
                $nextEndTime = date_time_set($startDateClone, date('H', strtotime($nextHourMinute)), date('i', strtotime($nextHourMinute)));

                $dates['startTime'] = $startDate;
                $dates['endDate'] = $nextEndTime;
                $dates['activeDate'] = $activeDay;
                if ($startDate > $nowDateTime) {
                    array_push($betweenDates, $dates);
                }
                $startDate = $nextEndTime;
            }
            $tableResult = [];
            foreach ($betweenDates as $betweenDate) {

                $isActiveChecker = DB::table('employee_appointments')
                    ->where([
                        'employee_id' => $request->input('employeeId'),
                        'active_day' => $betweenDate['activeDate'],
                        'start_date' => $betweenDate['startTime'],
                    ])->get();

                if (count($isActiveChecker) == 0) {

                    $inserted = DB::table('employee_appointments')
                        ->updateOrInsert(
                            [
                                'employee_id' => $request->input('employeeId'),
                                'product_id' => $request->input('productId'),
                                'active_day' => $betweenDate['activeDate'],
                                'start_date' => $betweenDate['startTime']
                            ],
                            [
                                'start_date' => $betweenDate['startTime'],
                                'end_date' => $betweenDate['endDate'],
                                'duration_date' => $market->duration_range,
                                'employee_id' => $request->input('employeeId'),
                                'product_id' => $request->input('productId'),
                                'active_day' => $betweenDate['activeDate'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    if ($inserted) {
                        $getTime = $betweenDate['startTime']->format("H:i");
                        $employeeAppointment = DB::table('employee_appointments')
                            ->where([
                                ['employee_id', '=', $request->input('employeeId')],
                                ['active_day', '=', $betweenDate['activeDate']],
                                ['product_id', '=', $request->input('productId')],
                                ['start_date', 'like', '%'.$getTime.'%']
                            ])->get();
                        array_push($tableResult, $employeeAppointment->get(0));
                    }
                }
            }
            $alreadyFinished = true;
            forEach($tableResult as $res) {
                if ($res->is_active === 1) {
                    $alreadyFinished = false;
                }
            }
            if (!$alreadyFinished) {
                return $this->sendResponse(true, 'цаг дууссан байна');
            }
            foreach ($tableResult as $value) {
                $value->start_date = substr($value->start_date, 0, -3);
                $value->end_date = substr($value->end_date, 0, -3);
                $discountPercent = $this->setAppointmentDiscount($request->input('productId'), $value->start_date, $request->input('date'));
                $value->discount_percent = $discountPercent;
            }
            if($tableResult !== 0) {
                return $this->sendResponse(true, $tableResult);
            }
            return $this->sendResponse(false, 'Үүсгэж чадсангүй');
        }
    }

    private function getEmployeeCheck($employeeId, $marketId, $date) {
        $now = date("Y-m-d H:i:s");
        $employeeCheck = DB::table('employee_markets')
            ->where('user_id', $employeeId)
            ->where('market_id', $marketId)
            ->get();

        if (count($employeeCheck) === 0) {
            return $res = (object) [
                'success' => false,
                'message' => 'Ажилтан салбар дээр бүртгэлгүй байна'
            ];
        }

        if (strtotime(date("Y-m-d", strtotime($now))) > strtotime($date)) {
            return $res = (object) [
                'success' => false,
                'message' => 'Өнгөрсөн цаг дээр цаг захиалах боломжгүй'
            ];
        }
        return $res = (object) ['success' => true];
    }

    private function setAppointmentDiscount($productId, $startDate, $chosenDate) {

        $nowDateTimes = new DateTime($chosenDate);

        $appointmentDiscount = DB::table('appointment_discount')
            ->where('is_active', 1)
            ->where('product_id', $productId)
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $startDate)
            ->where('active_day', ($nowDateTimes->format("N")))
            ->get();

        if ($appointmentDiscount && count($appointmentDiscount) > 0) {
            return $appointmentDiscount->get(0)->discount_percent;
        }
        return 0;
    }

    /**
     * @Parameter "appointmentId" you chosen employee appointment   
     * @Parameter "userId" user who want to use id
     * @Parameter "employeeId" chosen employee
     * @Parameter "productId" chosen product
     */
    function setEmployeeAppointment(Request $request) {
        $user = DB::table('users')->where('id', $request->input('userId'))->get();
        if ($user->count() === 0) {
            return $this->sendError('Хэрэглэгчийн мэдээлэл буруу байна', 500);
        }

        $products = DB::table('products')->where('id', $request->input('productId'))->get();
        if ($products->count() === 0) {
            return $this->sendError('Бүтээгдэхүүний мэдээлэл буруу байна', 500);
        }

        $employeeAppointments = DB::table('employee_appointments')
            ->where('id', $request->input('appointmentId'))
            ->where('employee_id', $request->input('employeeId'))
            ->get();
        if ($employeeAppointments->count() === 0) {
            return $this->sendError('Ажилтан цагийн бүртгэл таарахгүй байна', 500);
        }

        $table = DB::table('employee_appointments')
            ->where('id', $request->input('appointmentId'))
            ->where('employee_id', $request->input('employeeId'))
            ->update(
                [
                    'user_id' => $request->input('userId'),
                    'product_id' => $request->input('productId'),
                    'is_active' => 1
                ]
            );
        if($table > 0) {
            return $this->sendResponse(true, 'Амжилттай бүртгэгдлээ');
        }
        return $this->sendResponse(false, 'Бүртгэгдсэн цаг байна');
    }

    function getEmployee($marketId) {
        if (!$marketId) {
            return $this->sendResponse(false, 'Маркет сонгоогүй байна');
        }

        $employeeData = DB::table('employee_markets')
            ->where('market_id', $marketId)
            ->get();

        foreach ($employeeData as $employee) {
            $days = DB::table('active_job_days')
                ->where('employee_id', $employee->user_id)
                ->get();

            $employee->detail = DB::table('users')->find($employee->user_id);

            $employee->active_date = $days;
        }
        return $this->sendResponse(true, $employeeData);
    }

    function setEmployee(Request $request) {
        if (!$request->input('marketId')) {
            return $this->sendResponse(false, 'Маркет сонгоогүй байна');
        }

        if (!$request->input('userId')) {
            return $this->sendResponse(false, 'Хэрэглэгч сонгоогүй байна');
        }

        $employeeCheck = DB::table('employee_markets')
            ->where('market_id', $request->input('marketId'))
            ->where('user_id', $request->input('userId'))
            ->get();

        if (count($employeeCheck) === 0) {
            return $this->sendResponse(true, 'Ажилтаны мэдээлэл салбар дээр бүртгэлгүй байна та шалгана уу');
        }

        $employee =

        $days = DB::table('active_job_days')
            ->where('employee_id', $employee->user_id)
            ->get();

    }
}