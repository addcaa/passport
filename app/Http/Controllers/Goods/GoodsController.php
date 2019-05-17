<?php

namespace App\Http\Controllers\Goods;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class GoodsController extends Controller
{
    //商品详情
    public function cart(){
        if($_GET['goods_id']){
            $info=DB::table("goods")->where(['goods_id'=>$_GET['goods_id']])->first();
            $arr=[
                'res'=>200,
                'data'=>[
                    'goods_id'=>$info->goods_id,
                    'goods_name'=>$info->goods_name,
                    'goods_price'=>$info->goods_price
                ],
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $arr=[
                'res'=>00000,
                'msg'=>"没有此商品",
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }
    }

    //购物车
    public function car(){
        $info=DB::table("goods")->where(['goods_id'=>$_GET['goods_id']])->first();
        $data=[
            'goods_id'=>$info->goods_id,
            'goods_price'=>$info->goods_price,
            'cart_num'=>1,
            'cart_time'=>time(),
            'user_id'=>$_GET['u_id'],
            'goods_name'=>$info->goods_name,

        ];
        $arr=DB::table("cart")->insert($data);
        if($arr){
            $ser=[
                'res'=>200,
                'msg'=>"添加成功",
            ];

            return json_encode($ser,JSON_UNESCAPED_UNICODE);
        }else{
            $ser=[
                'res'=>50000,
                'msg'=>"添加购物车失败",
            ];
            return json_encode($ser,JSON_UNESCAPED_UNICODE);
        }
    }

    //购物车
    public function shopping(){
        $arr_all=DB::table("cart")->where(['user_id'=>$_GET['u_id']])->get();
        return json_encode($arr_all);
    }
    //立即购买
    public function buy(){
        DB::beginTransaction();
        $goods_price=$_GET['goods_price'];
        $cart_id=$_GET['cart_id'];
        $cart_info=DB::table('cart')->where(['cart_id'=>$cart_id])->first();
        $goods_id=$cart_info->goods_id;
        $goods_info=DB::table('goods')->where(['goods_id'=>$goods_id])->first();
        $order=substr(md5(Str::random(10)),5,15);
        // try {
        //     $order_info=[
        //         'on_order'=>$order,
        //         'totalprice'=>$goods_info->goods_price,
        //         'add_time'=>time(),
        //     ];
        //     $order_arr=DB::table('order')->insert($order_info);
        //     $oid = DB::getPdo('order')->lastInsertId();
        //     $order_goods=[
        //         'oid'=>$oid,
        //         'goods_id'=>$goods_id,
        //         'goods_name'=>$goods_info->goods_name,
        //         'goods_price'=>$goods_info->goods_price,
        //         'user_id'=>$cart_info->user_id
        //     ];
        //     $order_goods_info=DB::table('orders_detail')->insert($order_goods);
        //     $cart_ser=DB::table('cart')->where(['cart_id'=>$cart_id])->delete();
        //     DB::commit();
        //     $add=[
        //         'ser'=>200,
        //         'msg'=>"购买成功",
        //     ];
        //     return json_encode($add,JSON_UNESCAPED_UNICODE);
        // } catch (\Exception $exception) {
        //     DB::rollBack();
        //     $add=[
        //         'ser'=>5000,
        //         'msg'=>"购买失败",
        //     ];
        //     return json_encode($add,JSON_UNESCAPED_UNICODE);
        //     // return $exception->getMessage();
        // }
        // if($order_arr && $order_goods_info ){
        //     　DB::commit();
        //     return  1;
        // }else{
        //     DB::rollback();
        //     return  0;
        // }
    }

    /**
     * 订单列表
     */
    public function drop(){
        $u_id=$_GET['u_id'];
        $arr=DB::table("orders_detail")
        ->join('order', 'orders_detail.oid', '=', 'order.oid')
        ->select("goods_name","goods_price","on_order")
        ->where(['user_id'=>$_GET['u_id']])
        ->get();
        return json_encode($arr,JSON_UNESCAPED_UNICODE);
    }
}
