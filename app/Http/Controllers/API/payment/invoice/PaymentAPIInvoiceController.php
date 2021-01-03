<?php
namespace App\Http\Controllers\API\payment\invoice;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use DB;

session_start();

class PaymentAPIInvoiceController extends Controller {

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
}