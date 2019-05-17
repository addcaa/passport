<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('api/reg','User\UserController@reg' );//注册

Route::post('api/log','User\UserController@log' );//登录

Route::get('api/index','User\UserController@index');//登录


Route::get('goods/cart','Goods\GoodsController@cart');//商品详情

Route::get('goods/car','Goods\GoodsController@car');//加入购物车

Route::get('goods/shopping','Goods\GoodsController@shopping');//商品购物车

Route::get('goods/buy','Goods\GoodsController@buy');//订单

Route::get('goods/drop','Goods\GoodsController@drop');//订单列表
