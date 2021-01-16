<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\payment\auth\PaymentAuthAPIController;
use App\Notifications\NewOrder;
use App\Notifications\StatusChangedOrder;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

session_start();

class PaymentChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:checker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /** @var  paymentAuthAPIRepo */
    private $paymentAuthAPIRepo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PaymentAuthAPIController $paymentAuthAPIController)
    {
        $this->paymentAuthAPIRepo = $paymentAuthAPIController;
        $today = date("Y-m-d H:i:s");
        error_log('=> '.$today);
        $this->paymentAuthAPIRepo->token();
        $this->getOrderListener();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        error_log('working');
    }

    private function getOrderListener() {

        $now = new DateTime('NOW');
        $now->modify('+10 minute');
        $invoiceNotAccepted = DB::table('invoice')
            ->where('active', true)
            ->where('accepted', false)
            ->where('start_date', '<' ,$now)
            ->get();
        if (count($invoiceNotAccepted) > 0) {
            $this->checkInvoice($invoiceNotAccepted);
        }

        $invoiceRequestTimeOut = DB::table('invoice')
            ->where('active', true)
            ->where('accepted', false)
            ->where('start_date', '>' ,$now)
            ->get();
        if (count($invoiceRequestTimeOut) > 0) {
            $this->cancelForInvoiceRequest($invoiceRequestTimeOut);
        }
    }

    private function checkInvoice($invoiceNotAccepted) {

        foreach ($invoiceNotAccepted as $invoice) {
            $offset = array (
                "page_number" => 1,
                "page_limit" => 100
            );
            $reData = array(
                "object_type" => "INVOICE",
                "object_id" => $invoice->invoice_id,
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
                if (count($response->rows) > 0) {
                    if ($response->rows[0]->payment_status === 'PAID') {
                        $now = new DateTime('NOW');
                        DB::table('invoice')
                            ->where('id', $invoice->id)
                            ->update(['accepted' => true, 'accept_date' => $now]);

                        $order = DB::table('orders')->find($invoice->order_id);
                        $updated = DB::table('orders')->where('id', $invoice->order_id)->update(['order_status_id' => 5]);
                        error_log($invoice->user_id);
                        $user = DB::table('users')->find($invoice->user_id);
                        error_log($user->get());
                        error_log($order);
                        Notification::send([$user], new StatusChangedOrder($order));
                    }
                }
            }
        }
    }

    private function cancelForInvoiceRequest($invoiceRequestTimeOut) {
        foreach ($invoiceRequestTimeOut as $invoice) {
            DB::table('invoice')
                ->where('id', $invoice->id)
                ->update(['active' => false]);
        }
    }
}
