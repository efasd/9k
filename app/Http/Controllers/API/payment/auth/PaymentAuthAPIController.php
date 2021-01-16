<?php

namespace App\Http\Controllers\API\payment\auth;

use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Date;

class PaymentAuthAPIController extends Controller
{
    public function token()
    {
        if(!isset($_SESSION)) { session_start(); }

        $client = new Client();
        $request = $client->request(
            'POST',
            env('PAYMENT_IP').'/v2/auth/token',
            [
                'auth' => ['DULGUUN_MERCHANT', 'ReLXMYim']
            ]
        );
        if ($request->getStatusCode() === 200) {
            $token = json_decode($request->getBody());
            $_SESSION['qpay_access_token'] = $token->{'access_token'};
            $_SESSION['qpay_access_token_refresh'] = $token->{'refresh_token'};
            $_SESSION['qpay_token_expire_time'] = $token->{'refresh_expires_in'};
            return $this->sendResponse('Token амжилттай авлаа ', 200);
        }
        return $this->sendError('Token авж чадсангүй', 500);
    }

    public function refresh($refreshTokenRequest)
    {
        $client = new Client();
        $request = $client->request(
            'POST',
            env('PAYMENT_IP').'/v2/auth/refresh',
            [
                'headers' =>
                    [
                        'Authorization' => 'Bearer '.$refreshTokenRequest
                    ]
            ]
        );
        if ($request->getStatusCode() === 200) {
            $token = json_decode($request->getBody());
            session('qpay_access_token', $token->{'access_token'});
            session('qpay_access_token_refresh', $token->{'refresh_token'});
            session('qpay_token_expire_time', $token->{'refresh_expires_in'});

            return $this->sendResponse('Амжилттай refresh хийлээ гэхгүй юу', 200);
        }
        return $this->sendError('Token авж чадсангүй', 500);
    }
}