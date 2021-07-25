<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param $marketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($marketId)
    {
        $employees = $this->getEmployeeList($marketId, null);

        if ($employees === null) {
            return $this->sendError('Тунхайн салбар дээр ажилтан байхгүй байна', 400);
        }
        return $this->sendResponse(true, $employees);
    }

    /**
     * Display a listing of the resource.
     *
     * @param $marketId
     * @param $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function findOne($marketId, $employeeId)
    {
        $employees = $this->getEmployeeList($marketId, $employeeId);

        if (count($employees) === 0) {
            return $this->sendError('Засварлах мэдээлэл олдсонгүй', 400);
        }
        return $this->sendResponse(true, $employees);
    }

    /**
     * Display a listing of the resource.
     *
     * @param $marketId
     * @param $employeeId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($marketId, $employeeId, Request $request)
    {
        $checkEmployee = $this->getEmployeeList($marketId, $employeeId);

        if($checkEmployee == null) {
            return $this->sendError('Энэ салбар дээр тухайн ажилтан байхгүй байна',400);
        }

        $beforeCheck = $this->getEmployeeList($marketId, $request->input('marketId'));
        if($beforeCheck != null) {
            return $this->sendError('Энэ ажилтаны аль хэдийн бүртгэгдсэн байна',400);
        }

        $employeeMarketCheck = DB::table('employee_markets')
            ->where('market_id', $marketId)
            ->where('user_id', $employeeId)
            ->update([
                'market_id' => $request->input('marketId')
            ]);

        if($employeeMarketCheck == 0) {
            return $this->sendError('Өөрчлөлт хийхэд алдаа гарлаа', 400);
        }
        return $this->sendResponse(true, 'success');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create($marketId, Request $request)
    {
        $beforeCheck = $this->getEmployeeList($marketId, $request->input('employeeId'));

        if ($beforeCheck != null) {
            return $this->sendError('Аль хэдийн үүссэн байна', 500);
        }
        $employeeMarketCheck = DB::table('employee_markets')
            ->insert([
                'market_id' => $marketId,
                'user_id' => $request->input('employeeId')
            ]);
        if ($employeeMarketCheck == false) {
            return $this->sendError('Тухайн ажилтаны үүсгэсэн байна', 400);
        }
        return $this->sendResponse(true, 'Амжилттай үүсгэлээ');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $marketId
     * @param $employeeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($marketId, $employeeId)
    {
        $beforeCheck = $this->getEmployeeList($marketId, $employeeId);
        if ($beforeCheck == null) {
            return $this->sendError('Устгах мэдээлэл олдсонгүй', 400);
        }

        $employeeMarketCheck = DB::table('employee_markets')
            ->where('market_id', $marketId)
            ->where('user_id', $employeeId)
            ->delete();

        if ($employeeMarketCheck == 0) {
            return $this->sendError('Устгах боломжгүй утга байна', 500);
        }
        return $this->sendResponse(true, 'Амжилттай устгагдлаа');
    }

    private function getEmployeeList($marketId, $employeeId) {
        $employees = null;
        if ($employeeId == null) {
            $employees = DB::table('employee_markets')
                ->where('market_id', $marketId)
                ->get();

        } else {
            $employees = DB::table('employee_markets')
                ->where('market_id', $marketId)
                ->where('user_id', $employeeId)
                ->get();
        }
        if (count($employees) === 0) {
            return null;
        }
        $reData = [];
        foreach ($employees as $employee) {
            $employee = DB::table('users')->find($employee->user_id);
            array_push($reData, $employee);
        }
        return $reData;
    }
}
