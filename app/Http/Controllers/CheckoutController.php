<?php

namespace App\Http\Controllers;

use App\Models\Order;

class CheckoutController extends Controller
{
    public function index(){

        $order=Order::create([

            'total_price' => 1000
        ]);
        $PaymentKey = PayMobController::pay($order->total_price,$order->id);

//        $url = "https://accept.paymobsolutions.com/api/acceptance/iframes/825413?payment_token=" . $PaymentKey->token;
//
        return redirect($PaymentKey);

    }
}
