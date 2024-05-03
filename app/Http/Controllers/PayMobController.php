<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use PayMob\Facades\PayMob;

class PayMobController extends Controller
{
    public static function pay(float $total_price , int $order_id)
    {
        $auth = PayMob::AuthenticationRequest();

        $order = PayMob::OrderRegistrationAPI([
            'auth_token' => $auth->token,
            'amount_cents' => $total_price * 100, //put your price
            'currency' => 'EGP',
            'delivery_needed' => false, // another option true
            'merchant_order_id' => $order_id, //put order id from your database must be unique id
            'items' => [] // all items information or leave it empty
        ]);
        $PaymentKey = PayMob::PaymentKeyRequest([
            'auth_token' => $auth->token,
            'amount_cents' => $total_price * 100, //put your price
            'currency' => 'EGP',
            'order_id' => $order->id,
            "billing_data" => [ // put your client information
                "apartment" => "803",
                "email" => "claudette09@exa.com",
                "floor" => "42",
                "first_name" => "Clifford",
                "street" => "Ethan Land",
                "building" => "8028",
                "phone_number" => "+86(8)9135210487",
                "shipping_method" => "PKG",
                "postal_code" => "01898",
                "city" => "Jaskolskiburgh",
                "country" => "CR",
                "last_name" => "Nicolas",
                "state" => "Utah"
            ]
        ]);
        $url = "https://accept.paymobsolutions.com/api/acceptance/iframes/825413?payment_token=" . $PaymentKey->token;

        return $url;
    }


    public function checkout_processed(Request $request)
    {
        try {
            $request_hmac = $request->hmac;

            // Extract the relevant data from the request
            $orderData = [
                'order' => [
                    'merchant_order_id' => $request->obj['order']['merchant_order_id'],
                    // Add other relevant fields from the request object
                ],
                'amount_cents' => $request->obj['amount_cents'],
                'id' => $request->obj['id']
                // Add other relevant fields from the request object
            ];

            // Convert the data to JSON
            $jsonData = json_encode($orderData);

            // Calculate HMAC using the JSON data
            $calc_hmac = PayMob::calcHMAC($jsonData);

            if ($request_hmac === $calc_hmac) {
                $order_id = $orderData['order']['merchant_order_id'];
                $amount_cents = $orderData['amount_cents'];
                $transaction_id = $orderData['id'];

                $order = Order::find($order_id);

                if ($request->obj['success'] === true && ($order->total_price * 100) === $amount_cents) {
                    $order->update([
                        'transaction_status' => 'finished',
                        'transaction_id' => $transaction_id
                    ]);
                } else {
                    $order->update([
                        'transaction_status' => 'failed',
                        'transaction_id' => $transaction_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Handle any exceptions here
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} //end of class
