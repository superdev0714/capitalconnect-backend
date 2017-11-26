<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use Nathanmac\Utilities\Parser\Facades\Parser;
use App\PaymentTransaction;

class WebController extends Controller
{

    //The PayGate PayXML URL
    const PAYGATE_SERVER_URL = "https://www.paygate.co.za/payxml/process.trans";

    //The PayXML version number
    const PAYGATE_XML_VER = "4.0";

    //The XML document declaration; precedes any XML document           ?
    const PAYGATE_XML_DECLARATION = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    //The PayGate Document Type Definition
    const PAYGATE_DTD_DEF = "<!DOCTYPE protocol SYSTEM \"https://www.paygate.co.za/payxml/payxml_v4.dtd\">";

    public function preparePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|numeric',
            'amount' => 'required|numeric',
        ]);

        return view("payments/prepare", [
            'errors' => $validator->errors()
        ]);
    }

    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|numeric',
            'amount' => 'required|numeric',
            'currency' => 'required|in:ZAR',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'card' => 'required|numeric',
            'expiry_year' => 'required|integer|min:2016|max:2050',
            'expiry_month' => 'required|integer|min:1|max:12',
            'cvv' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return view("payments/prepare", [
                'errors' => $validator->errors()
            ]);
        }

        //Construct the XML document header
        $XMLHeader = self::PAYGATE_XML_DECLARATION . self::PAYGATE_DTD_DEF;

        //Build the XML transaction string
        // - First,  build the transaction header
        $TransHeader = sprintf('<protocol ver="%s" pgid="%s" pwd="%s">', self::PAYGATE_XML_VER, env('PAYGATE_ID'), env('PAYGATE_PASSWORD'));

        // - Second, build the transaction detail
        $TransDetail = sprintf('<authtx cref="%s" cname="%s %s" cc="%s" exp="%02d%d" budp="0" amt="%d" cur="%s" cvv="%s" rurl="%s" nurl="%s" />',
            $request->reference,
            $request->first_name, $request->last_name, $request->card, $request->expiry_month, $request->expiry_year,
            $request->amount * 100, $request->currency, $request->cvv,
            url('payment/final'),
            url('payment/notify'));

        // - Third, build the transaction trailer
        $TransTrailer = "</protocol>";

        // - Then construct the full transaction XML
        $XMLTrans = $TransHeader . $TransDetail . $TransTrailer;

        // Construct the request XML by combining the XML header and transaction
        $Request = $XMLHeader . $XMLTrans;

        $client = new Client();
        $response = $client->request('POST', self::PAYGATE_SERVER_URL, ['body' => $Request]);
        if ($response->getStatusCode() != 200) {
            return view("payments/result", [
                'errors' => ['Payment System is not available now. Please try again after a short time.']
            ]);
        }

        $parsed = Parser::xml($response->getBody());

        if (!array_key_exists('securerx', $parsed)) {
            if (array_key_exists('authrx', $parsed)) {
                return view("payments/result", [
                    'errors' => [$parsed['authrx']['@attributes']['rdesc']]
                ]);
            } else if (array_key_exists('errorrx', $parsed)) {
                return view("payments/result", [
                    'errors' => [$parsed['errorrx']['@attributes']['edesc']]
                ]);
            } else {
                return view("payments/result", [
                    'errors' => ['Internal server error was occurred, please try again.']
                ]);
            }
        }

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->reference = $request->reference;
        $paymentTransaction->success_url = '';
        $paymentTransaction->failed_url = '';
        $paymentTransaction->currency = $request->currency;
        $paymentTransaction->first_name = $request->first_name;
        $paymentTransaction->last_name = $request->last_name;
        $paymentTransaction->email = $request->email;
        $paymentTransaction->amount = $request->amount * 100;
        $paymentTransaction->hash = '';
        $paymentTransaction->hotel = 0;
        $paymentTransaction->paygate_transaction = '';
        $paymentTransaction->status = 0;
        $paymentTransaction->status_description = '';
        $paymentTransaction->save();

        $securerx = $parsed['securerx']['@attributes'];
        return redirect(sprintf('%s?TRANS_ID=%s&PAYGATE_ID=%s&CHECKSUM=%s', $securerx['url'], $securerx['tid'], env('PAYGATE_ID'), $securerx['chk']));
    }

    public function notifyPayment()
    {
        $rawXML = file_get_contents("php://input");

        // Calculate the name of the file that will store the values.
        $filename = "PayXML_Notify_" . date("Ymd") . ".txt";

        // Open the file that will store the values
        $handle = fopen(storage_path("app/$filename"), "a+");

        $data = "Raw XML\r\n" .
            "-------\r\n" .
            $rawXML . "\r\n";
        fwrite($handle, $data);

        // Close the file
        fwrite($handle, "\r\n\r\n");
        fclose($handle);
    }

    public function finalPayment(Request $request)
    {
        $PAYGATE_ID = $request['PAYGATE_ID'];
        $REFERENCE = $request['REFERENCE'];
        $CARD_TYPE = $request['CARD_TYPE'];
        $TRANSACTION_STATUS = $request['TRANSACTION_STATUS'];
        $RESULT_CODE = $request['RESULT_CODE'];
        $RESULT_DESC = $request['RESULT_DESC'];
        $AUTH_CODE = $request['AUTH_CODE'];
        $TRANSACTION_ID = $request['TRANSACTION_ID'];
        $RISK_INDICATOR = $request['RISK_INDICATOR'];
        $CHECKSUM = $request['CHECKSUM'];

        $checksum_source = $PAYGATE_ID . "|" . $REFERENCE . "|" . $CARD_TYPE . "|" . $TRANSACTION_STATUS . "|" . $RESULT_CODE . "|" . $RESULT_DESC . "|" . $AUTH_CODE . "|" . $TRANSACTION_ID . "|" . $RISK_INDICATOR . "|" . env('PAYGATE_PASSWORD');
        $test_checksum = md5($checksum_source);

        if ($TRANSACTION_STATUS == 1 && $CHECKSUM == $test_checksum) {

            // send mail to administrator

            $paymentTransaction = PaymentTransaction::where('reference', $REFERENCE)->first();
            if ($paymentTransaction) {
                Mail::send(
                    'mails/confirm_payment',
                    [
                        'customer' => [
                            'name' => sprintf('%s %s', $paymentTransaction->first_name, $paymentTransaction->last_name),
                            'email' => $paymentTransaction->email
                        ],
                        'payment' => [
                            'reference' => $REFERENCE,
                            'amount' => $paymentTransaction->amount / 100,
                            'date' => date("Y-m-d H:i:s")
                        ]
                    ],
                    function ($message) use ($paymentTransaction) {
                        $message->from(config('mail.username'));
                        $message->to($paymentTransaction->email)->subject('The Capital Hotel Group - Payment Confirmation');
                    }
                );

                Mail::send(
                    'mails/manual_payment',
                    [
                        'customer' => [
                            'name' => sprintf('%s %s', $paymentTransaction->first_name, $paymentTransaction->last_name),
                            'email' => $paymentTransaction->email
                        ],
                        'payment' => [
                            'reference' => $REFERENCE,
                            'amount' => $paymentTransaction->amount / 100
                        ]
                    ],
                    function ($message) {
                        $message->from(config('mail.username'));
                        $message->to(env('MANAGER_MAIL'))->subject('New CapitalConnect Payment');
                    }
                );
            }

            return view("payments/result");
        } else {
            return view("payments/result", [
                'errors' => [$RESULT_DESC]
            ]);
        }
    }

}
