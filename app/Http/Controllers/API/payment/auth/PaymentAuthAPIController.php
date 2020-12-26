<?php

namespace App\Http\Controllers\API\payment\auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;


class PaymentAuthAPIController extends Controller
{
    public function token(Request $request)
    {
        session_start();
        if ($request->input('username')) {
            return $this->sendError('Хэрэглэгчийн нэр байхгүй байна', '500');
        }
        if ($request->input('password')){
            return $this->sendError('Хэрэглэгчийн нууц байхгүй байна', '500');
        }
        $client = new Client();
        $request = $client->request(
            'POST',
            env('PAYMENT_IP').'/v2/auth/token',
            [
                'auth' => ['TEST_MERCHANT', '123456']
            ]
        );
        if ($request->getStatusCode() === 200) {
            $token = json_decode($request->getBody());
            $_SESSION['qpay_access_token'] = $token->{'access_token'};

            return $this->sendResponse('Token амжилттай авлаа : ', 200);
        }
        return $this->sendError('Token авж чадсангүй', 500);
    }

    public function refresh(Request $request)
    {
        if ($request->input('username')) {
            return $this->sendError('Хэрэглэгчийн нэр байхгүй байна', '500');
        }
        if ($request->input('password')){
            return $this->sendError('Хэрэглэгчийн нууц байхгүй байна', '500');
        }
        $client = new Client();
        $request = $client->request(
            'POST',
            env('PAYMENT_IP').'/v2/auth/refresh',
            [
                'headers' =>
                    [
                        'Authorization' => 'Bearer '.session('qpay_access_token')
                    ]
            ]
        );
        if ($request->getStatusCode() === 200) {
            $token = json_decode($request->getBody());
            session('qpay_access_token', $token->{'access_token'});

            return $this->sendResponse('Амжилттай refresh хийлээ гэхгүй юу', 200);
        }
        return $this->sendError('Token авж чадсангүй', 500);
    }
}