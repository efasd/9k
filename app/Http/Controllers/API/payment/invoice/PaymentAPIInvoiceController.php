<?php
namespace App\Http\Controllers\API\payment\invoice;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use DB;

session_start();

class PaymentAPIInvoiceController extends Controller
{

    /* OrderAPIController дээр хийсэн туд comment болгож байна*/
//    public function create(Request $request) {
//
//        $order = DB::table('product_orders')
//            ->where('order_id', $request->input('orderId'))
//            ->get();
//
//        if ($order->count() === 0) {
//            return $this->sendError('Захилга олдсонгүй');
//        }
//
//        $totalAmount = 0.0;
//        for($i=0; $i < $order->count(); $i++) {
//            $totalAmount += $order[$i]->price;
//        }
//
//        $market = DB::table('markets')
//            ->find($request->input('marketId'));
//
//        if(!$market) {
//            return $this->sendError('Салбарын мэдээлэл байхгүй байна');
//        }
//
//        $line = array (
//            "line_description" => "Invoice description",
//            "line_quantity" => "1.00",
//            "line_unit_price" => $totalAmount,
//            "discounts" => [],
//            "surcharges" => [],
//            "taxes" => []
//        );
//
//        $test = (object) array();
//        $reData = array(
//            "invoice_code" => "TEST_INVOICE",
//            "sender_invoice_no" => "9329873948",
//            "sender_branch_code" => $market->id.'',
//            "invoice_receiver_code" => "terminal",
//            "invoice_receiver_data" => $test,
//            "invoice_description" => "Invoice description",
//            "lines" => []
//        );
//        $reData['lines'][0] = $line;
//
//        $client = new Client();
//        $request = $client->request(
//            'POST',
//            env('PAYMENT_IP') . '/v2/invoice',
//            [
//                'headers' => [
//                    'Authorization' => 'Bearer ' . $_SESSION['qpay_access_token'],
//                    'Content-Type' => 'application/json'
//                ],
//                'body' => json_encode($reData)
//            ]
//        );
//
//        if ($request->getStatusCode() === 200) {
//            $res = json_decode($request->getBody());
//            return $this->sendResponse($res, 200);
//        }
//        return $this->sendError('Зарлагын хүсэлт үүсгэж чадсангүй', 500);
//    }

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

        // dd(json_encode($reData), env('PAYMENT_IP') . '/v2/invoice/'.$invoiceId);
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

    private function getInvoiceCode()
    {
        $returnCode = '';
        $returnCode += date('YmdHms');
        return $returnCode;
    }
}