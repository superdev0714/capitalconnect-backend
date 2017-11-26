<?php

namespace App\Http\Controllers\api\v1;

use Faker\Provider\Image;
use Faker\Provider\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Prettus\RequestLogger\Handler\HttpLoggerHandler;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use Nathanmac\Utilities\Parser\Facades\Parser;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\City;
use App\Hotel;
use App\PaymentMethod;
use App\PaymentTransaction;

class ApiController extends Controller
{

    //The PayGate PayXML URL
    const PAYGATE_SERVER_URL = "https://www.paygate.co.za/payxml/process.trans";

    //The PayXML version number
    const PAYGATE_XML_VER = "4.0";

    //The XML document declaration; precedes any XML document           ?
    const PAYGATE_XML_DECLARATION = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    //The PayGate Document Type Definition
    const PAYGATE_DTD_DEF = "<!DOCTYPE protocol SYSTEM \"https://www.paygate.co.za/payxml/payxml_v4.dtd\">";


    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users',
            'mobile' => 'required|max:32|unique:users',
            'password' => 'required|min:6',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => bcrypt($request->password),
            'photo' => null,
        ]);

        if ($user != null) {
            Auth::login($user, true);

            return response()->json([
                'token' => $user->remember_token,
                'account' => $user,
            ]);
        } else {
            return response()->json([
                'errors' => ['User registration was failed.']
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], true)) {
            $user = Auth::user();
            return response()->json([
                'token' => $user->remember_token,
                'account' => $user,
            ]);
        } else {
            return response()->json([
                'errors' => ['Invalid email or password.']
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function logout()
    {
        Auth::logout();
    }

    public function getAccount()
    {
        $user = Auth::user();
        return response()->json($user);
    }

    public function updateAccount(Request $request)
    {

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'mobile' => 'max:32|unique:users,mobile,' . $user->id,
            'first_name' => 'max:255',
            'last_name' => 'max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {

                // delete old file
                $user->removeOldPhoto();

                // save new file
                $fileName = Uuid::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path(env('PATH_PHOTOS', '/')), $fileName);

                // update database
                $user->photo = $fileName;
            }
        } else if ($request->has('photo')) { // photo field is string

            // delete old file
            $user->removeOldPhoto();

            // photo field
            $photo = $request->photo;

            // check if photo value is url or base64 string
            if (filter_var($photo, FILTER_VALIDATE_URL)) {
                $user->photo = $photo;
            } else {

                if (preg_match("/^data:image\/(\w+);base64\,(.+)$/i", $photo, $match)) {
                    $ext = $match[1];
                    $photoData = $match[2];
                    if (($content = base64_decode($photoData)) !== false) {
                        // save new file
                        $fileName = Uuid::uuid() . ".$ext";
                        File::put(public_path(env('PATH_PHOTOS', '/')) . $fileName, $content);

                        // update database
                        $user->photo = $fileName;
                    } else {
                        return response()->json([
                            'errors' => ['Invalid photo data.']
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                } else {
                    return response()->json([
                        'errors' => ['Invalid format of photo field.']
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }

        $user->fill($request->except('photo'));
        $user->save();

        Auth::login($user, true);

        return response()->json($user);
    }

    public function getCities()
    {
        $cities = City::with('hotels')->get();
        return response()->json($cities);
    }

    public function getCity($id)
    {
        $city = City::with('hotels')->find($id);
        if ($city != null)
            return response()->json($city);
        else
            return response()->json([
                'errors' => ['Invalid request parameters.']
            ], Response::HTTP_NOT_FOUND);
    }

    public function getHotel($id)
    {
        $hotel = Hotel::find($id);
        if ($hotel != null)
            return response()->json($hotel);
        else
            return response()->json([
                'errors' => ['Invalid request parameters.']
            ], Response::HTTP_NOT_FOUND);
    }

    // register new payment method
    public function postPaymentMethods(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'card_number' => 'required|min:4|max:255',
            'card_first_name' => 'required|max:255',
            'card_last_name' => 'required|max:255',
            'card_expiry_month' => 'required|numeric|between:1,12',
            'card_expiry_year' => 'required|numeric|between:2000,2100',
            'card_cvv' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentMethod = new PaymentMethod();
        $paymentMethod->user_id = Auth::user()->id;
        $paymentMethod->token = $request->card_number;
        $paymentMethod->last_digits = substr($request->card_number, -4);
        $paymentMethod->cvv = $request->card_cvv;
        $paymentMethod->first_name = $request->card_first_name;
        $paymentMethod->last_name = $request->card_last_name;
        $paymentMethod->expiry_month = $request->card_expiry_month;
        $paymentMethod->expiry_year = $request->card_expiry_year;
        $paymentMethod->save();

        return response()->json($paymentMethod);
    }

    // return all payment methods linked with logged-in user
    public function getPaymentMethods()
    {
        // current logined user
        $user = Auth::user();
        $methods = PaymentMethod::where('user_id', $user->id)->get();
        return response()->json($methods);
    }

    // delete the specified payment method
    public function deletePaymentMethods($id)
    {
        // current logined user
        $user = Auth::user();

        $paymentMethod = PaymentMethod::find($id);
        if ($paymentMethod && $paymentMethod->user_id == $user->id) {

            $paymentMethod->delete();
            return response()->json([
                'status' => 'ok'
            ]);

        } else {
            return response()->json([
                'errors' => ['Specified payment method can\'t be found']
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function postPaymentInit(Request $request)
    {
        if ($request->has('data')) {
            $data = $request->data;
            $items = $data['items'];

            $sortedItems = [];
            $totalAmount = 0;
            $itemAmounts = '';
            $hotelId = 0;
            foreach ($items as $item) {
                $inserted = false;
                for ($i = 0; $i < count($sortedItems); $i++) {
                    $sortedItems = $sortedItems[$i];
                    if ($sortedItems['price'] > $item['price']) {
                        array_splice($sortedItems, $i, 0, $item);
                        $inserted = true;
                        break;
                    }
                }

                if (!$inserted) {
                    $sortedItems[] = $item;
                }

                $hotelId = $item['hotelId'];
                $amount = $item['count'] * $item['price'];
                $totalAmount += $amount;
                $itemAmounts .= strval(count($sortedItems) - 1) . strval($amount);
            }

            $hash = md5($data['reference'] . $itemAmounts . 'protel$PAYMENT');

            $paymentTransaction = new PaymentTransaction();
            $paymentTransaction->reference = $data['reference'];
            $paymentTransaction->success_url = $data['successUrl'];
            $paymentTransaction->failed_url = $data['failedUrl'];
            $paymentTransaction->currency = $data['currency'];
            $paymentTransaction->first_name = $data['booker']['firstName'];
            $paymentTransaction->last_name = $data['booker']['lastName'];
            if (isset($data['booker']['company']))
                $paymentTransaction->company = $data['booker']['company'];
            if (isset($data['booker']['email']))
                $paymentTransaction->email = $data['booker']['email'];
            if (isset($data['booker']['phone']))
                $paymentTransaction->phone = $data['booker']['phone'];
            if (isset($data['booker']['birthday']))
                $paymentTransaction->birthday = $data['booker']['birthday'];
            if (isset($data['booker']['street']))
                $paymentTransaction->street = $data['booker']['street'];
            if (isset($data['booker']['zip']))
                $paymentTransaction->zip = $data['booker']['zip'];
            if (isset($data['booker']['city']))
                $paymentTransaction->city = $data['booker']['city'];
            if (isset($data['booker']['country']))
                $paymentTransaction->country = $data['booker']['country'];
            $paymentTransaction->amount = $totalAmount;
            $paymentTransaction->hash = $hash;
            $paymentTransaction->hotel = $hotelId;
            $paymentTransaction->paygate_transaction = '';
            $paymentTransaction->status = 0;
            $paymentTransaction->status_description = '';
            $paymentTransaction->save();

            return response()->json([
                'hash' => $hash,
                'url' => url('api/v1/payment/checkout'),
                'callType' => 'Post',
                'elements' => [
                    [
                        'name' => 'transaction',
                        'value' => $paymentTransaction->id
                    ]
                ]
            ]);
        }

        return response()->json([
            'errors' => ['Invalid request']
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function postPaymentCheckout(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'transaction' => 'required|exists:payment_transactions,id',
            'payment_method' => 'required|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentTransaction = PaymentTransaction::find($request->transaction);
        $paymentMethod = PaymentMethod::find($request->payment_method);

        //Construct the XML document header
        $XMLHeader = self::PAYGATE_XML_DECLARATION . self::PAYGATE_DTD_DEF;

        //Build the XML transaction string
        // - First,  build the transaction header
        $TransHeader = sprintf('<protocol ver="%s" pgid="%s" pwd="%s">', self::PAYGATE_XML_VER, env('PAYGATE_ID'), env('PAYGATE_PASSWORD'));

        // - Second, build the transaction detail
        $TransDetail = sprintf('<authtx cref="%s" cname="%s %s" cc="%s" exp="%02d%d" budp="0" amt="%d" cur="%s" cvv="%s" rurl="%s" nurl="%s" />',
            $paymentTransaction->reference,
            $paymentMethod->first_name, $paymentMethod->last_name, $paymentMethod->token, $paymentMethod->expiry_month, $paymentMethod->expiry_year,
            $paymentTransaction->amount, 'ZAR', $paymentMethod->cvv,
            url('api/v1/payment/final'),
            url('api/v1/payment/notify'));

        // - Third, build the transaction trailer
        $TransTrailer = "</protocol>";

        // - Then construct the full transaction XML
        $XMLTrans = $TransHeader . $TransDetail . $TransTrailer;

        // Construct the request XML by combining the XML header and transaction
        $Request = $XMLHeader . $XMLTrans;

        $client = new Client();
        $response = $client->request('POST', self::PAYGATE_SERVER_URL, ['body' => $Request]);
        if ($response->getStatusCode() != 200) {
            return response()->json([
                'errors' => ['Payment System is not available now. Please try again after a short time.']
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $parsed = Parser::xml($response->getBody());

        if (!array_key_exists('securerx', $parsed)) {
            if (array_key_exists('authrx', $parsed)) {
                return response()->json([
                    'errors' => [$parsed['authrx']['@attributes']['rdesc']]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else if (array_key_exists('errorrx', $parsed)) {
                return response()->json([
                    'errors' => [$parsed['errorrx']['@attributes']['edesc']]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            } else {
                return response()->json([
                    'errors' => ['Internal server error was occurred, please try again.']
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $user = Auth::user();
        $paymentTransaction->user_id = $user->id;
        $paymentTransaction->save();

        $securerx = $parsed['securerx']['@attributes'];
        return response()->json([
            'status' => 'ok',
            'url' => sprintf('%s?TRANS_ID=%s&PAYGATE_ID=%s&CHECKSUM=%s', $securerx['url'], $securerx['tid'], env('PAYGATE_ID'), $securerx['chk']),
            'reference' => $paymentTransaction->reference
        ]);
    }

    public function postPaymentValidate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'reference' => 'required|exists:payment_transactions,reference',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentTransaction = PaymentTransaction::where('reference', $request->reference)->first();
        return response()->json([
            'reference' => $paymentTransaction->reference,
            'transactionId' => $paymentTransaction->paygate_transaction,
            'hotel' => $paymentTransaction->hotel,
            'amount' => $paymentTransaction->amount,
            'success' => $paymentTransaction->status ? true : false,
            'error' => $paymentTransaction->status_description
        ]);
    }

    public function postPaymentNotify()
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

    public function postPaymentFinal(Request $request)
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

        $paymentTransaction = PaymentTransaction::where('reference', $REFERENCE)->first();
        if (!$paymentTransaction) {
            return response()->json([
                'errors' => ['Invalid request']
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $checksum_source = $PAYGATE_ID . "|" . $REFERENCE . "|" . $CARD_TYPE . "|" . $TRANSACTION_STATUS . "|" . $RESULT_CODE . "|" . $RESULT_DESC . "|" . $AUTH_CODE . "|" . $TRANSACTION_ID . "|" . $RISK_INDICATOR . "|" . env('PAYGATE_PASSWORD');
        $test_checksum = md5($checksum_source);

        if ($TRANSACTION_STATUS == 1 && $CHECKSUM == $test_checksum) {

            $paymentTransaction->paygate_transaction = $TRANSACTION_ID;
            $paymentTransaction->status = 1;
            $paymentTransaction->save();

            $client = new Client([
                'defaults' => [
                    'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
                ],
            ]);
            $url = env('PROTEL_SERVER') . '/basket/transaction?format=json';
            $params = [
                "reference" => $paymentTransaction->reference,
                "transactionId" => $TRANSACTION_ID,
                "hash" => $paymentTransaction->hash,
                "success" => true
            ];
            $response = $client->request('POST', $url, [
                'json' => $params,
                'http_errors' => false  // GuzzleClient option
            ]);

            // record log
            $requestLogger = app(\Prettus\RequestLogger\ResponseLogger::class);
            $requestLogger->log(
                Request::createFromBase(SymfonyRequest::create(
                    $url,
                    "POST",
                    $params
                )),
                Response::create(
                    $response->getBody(),
                    $response->getStatusCode(),
                    $response->getHeaders()
                )
            );

            if ($response->getStatusCode() == 200) {

                if (json_decode($response->getBody()->__toString())->success) {

                    return response()->json([
                        'status' => 'ok',
                        'reference' => $paymentTransaction->reference
                    ]);
                } else {

                    $paymentTransaction->paygate_transaction = $TRANSACTION_ID;
                    $paymentTransaction->status = 0;
                    $paymentTransaction->status_description = json_decode($response->getBody()->__toString())->message;
                    $paymentTransaction->save();

                    return response()->json([
                        'status' => 'failed',
                        'reference' => $paymentTransaction->reference
                    ]);
                }
            } else {

                $paymentTransaction->paygate_transaction = $TRANSACTION_ID;
                $paymentTransaction->status = 0;
                $paymentTransaction->status_description = $response->getBody();
                $paymentTransaction->save();

                return response()->json([
                    'status' => 'failed',
                    'reference' => $paymentTransaction->reference
                ]);
            }
        } else {

            $paymentTransaction->paygate_transaction = $TRANSACTION_ID;
            $paymentTransaction->status = 0;
            $paymentTransaction->status_description = $RESULT_DESC;
            $paymentTransaction->save();

            $client = new Client([
                'defaults' => [
                    'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
                ],
            ]);
            $url = env('PROTEL_SERVER') . '/basket/transaction?format=json';
            $params = [
                "reference" => $REFERENCE,
                "transactionId" => $TRANSACTION_ID,
                "hash" => $paymentTransaction->hash,
                "success" => false
            ];
            $response = $client->request('POST', $url, [
                'json' => $params,
                'http_errors' => false      // GuzzleClient option
            ]);

            // record log
            $requestLogger = app(\Prettus\RequestLogger\ResponseLogger::class);
            $requestLogger->log(
                Request::createFromBase(SymfonyRequest::create(
                    $url,
                    "POST",
                    $params
                )),
                Response::create(
                    $response->getBody(),
                    $response->getStatusCode(),
                    $response->getHeaders()
                )
            );

            return response()->json([
                'status' => 'failed',
                'reference' => $paymentTransaction->reference
            ]);

        }
    }

    public function postBookingInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|exists:payment_transactions,reference',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()->all()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = Auth::user();
        $paymentTransaction = PaymentTransaction::where('reference', $request->reference)->first();
        if ($paymentTransaction->user_id != $user->id) {
            return response()->json([
                'errors' => [
                    'You have not the permission of the selected reference'
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentTransaction->start_date = $request->start_date;
        $paymentTransaction->end_date = $request->end_date;
        $paymentTransaction->save();

        return response()->json([
            'status' => 'ok'
        ]);
    }

    public function getBookings()
    {
        $user = Auth::user();
        $paymentTransactions = PaymentTransaction::where('user_id', $user->id)->get(['reference', 'amount', 'hotel', 'start_date', 'end_date']);

        return response()->json($paymentTransactions);
    }

    public function test(Request $request)
    {
        $client = new Client([
            'defaults' => [
                'headers' => ['content-type' => 'application/json'],
            ],
        ]);
//        $response = $client->request('POST', env('PROTEL_SERVER') . '/basket/transaction?format=json', [
        $response = $client->request('POST', url('api/v1/test2'), [
            'json' => [
                "reference" => '63250',
                "transactionId" => '37326430',
                "hash" => '7b69844bbde93004b7150f99d55f500f',
                "success" => true
            ],
            'http_errors' => false  // GuzzleClient option
        ]);

//        $requestLogger = app(\Prettus\RequestLogger\ResponseLogger::class);
//        $requestLogger->log(
//            Request::createFromBase(SymfonyRequest::create(
//                "http://www.google.com",
//                "POST"
//            )),
//            Response::create(
//                $response->getBody(),
//                $response->getStatusCode(),
//                $response->getHeaders()
//            )
//        );

//        return $response->getHeaders();
//        $log = new HttpLoggerHandler();
//        $log = app('log')->getMonolog();
//        $log->info("##########", []);
//        var_dump($log);
//        return json_decode($response->getBody()->__toString())->success;
        return $response->getBody()->__toString();
//        echo $response->getBody()->__toString();
    }

    public function test2(Request $request)
    {
        return 'ok';
    }
}
