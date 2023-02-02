<?php

namespace App\Http\Controllers;
use App\Models\Cart;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \auth()->id();
        $cart = Cart::where(['user_id' => $user])->get();
        $sum_quantities = Cart::where(['user_id' => $user])->sum('quantity');
        $sum_price = Cart::where(['user_id' => $user])->sum('price_product');

        return response([
           'cart' => $cart,
           'sum_quantities' => $sum_quantities,
            'sum_price' => $sum_price
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
     * @param  int  $id
     */
    public function store($id)
    {
        $user = \auth()->id();
        $product = Product::findOrFail($id);
        if ($cart = Cart::where(['user_id' => $user,'product_id' => $product->id])->first()) {
            $cart->quantity++;
            $cart->save();
        }
        else {
            $cart = Cart::create([
                "user_id" => $user,
                "product_id" => $product->id,
                "price_product" => $product->price,
                "quantity" => 1,
            ]);
        }

        $response = [
            'cart' => $cart,
        ];
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
        Cart::destroy($id);
        return response([
            'message' => 'Deleted!'
        ]);
    }
}
