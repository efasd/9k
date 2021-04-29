<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DateTime;

class OrderCalendarController
{
    public function index()
    {
        return view('calendar.index');
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
//                    ->where('is_active', 1)
                    ->where('employee_id', $employee->user_id)
                    ->where('active_day', 'like', $startDate->format('Y-m').'%')
                    ->get();

                foreach($employeeAppointment as $app) {
                    $app->userInfo = $employeeInfo;
                }
                array_push($appointment,  $employeeAppointment);
            }
        } catch (\Exception $e) {

//            error_log(json_encode($e));
        }

        return $appointment;
    }

//    public function getAppointmentMonth() {
//        return
//    }


}