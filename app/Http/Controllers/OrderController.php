<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \auth()->id();
        $order = Order::where(['user_id' => $user])->get();
        $sum_quantities = Order::where(['user_id' => $user])->sum('quantity');
        $sum_price = 0;
        foreach ($order as $item) {
            $sum_price += $item['price_product'] * $item['quantity'];
        }

        return response([
            'order' => $order,
            'order_sum_quantities' => $sum_quantities,
            'order_sum_price' => $sum_price
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $user = \auth()->id();
        $cart = Cart::where(['user_id' => $user])->first();
        $order = Order::create([
            "user_id" => $user,
            "product_id" => $cart->product_id,
            "price_product" => $cart->price_product,
            "quantity" => $cart->quantity,
        ]);

        $response = [
            'id_order' => $order->id,
            'order' => $order
        ];

        Cart::destroy($user);

        return response($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
