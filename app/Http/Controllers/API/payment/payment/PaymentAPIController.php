<?php
namespace App\Http\Controllers\API\payment\payment;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use DB;

session_start();

class PaymentAPIController extends Controller
{
    public function get($invoiceId, Request $request)
    {
        if (!$invoiceId) {
            return $this->sendError('Хүсэлтын утга байхгүй байна', '500');
        }
        try {
            $client = new Client();
            $request = $client->request(
                'GET',
                env('PAYMENT_IP') . '/v2/payment/'.$invoiceId,
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
            return $this->sendResponse('Энэ хүсэлт цуцлагдсан байна', 500);
        }
        return $this->sendError('Хүсэлтыг авж чадсангүй', 500);
    }
    
    public function check($invoiceId, Request $request)
    {
        if (!$invoiceId) {
            return $this->sendError('Хүсэлтын утга байхгүй байна', '500');
        }
        $offset = array (
            "page_number" => 1,
            "page_limit" => 100
        );
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

    public function cancel($invoiceId, Request $request)
    {
        if (!$invoiceId) {
            return $this->sendError('Хүсэлтын утга байхгүй байна', '500');
        }
        $offset = array (
            "page_number" => 1,
            "page_limit" => 100
        );
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

    public function refund(Request $request)
    {

    }

    public function list(Request $request)
    {

    }
}