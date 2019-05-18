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
        $cart_id=$_GET['cart_id'];
        DB::beginTransaction();
        $cart_info=DB::table('cart')->where(['cart_id'=>$cart_id])->first();
        $goods_id=$cart_info->goods_id;
        $goods_info=DB::table('goods')->where(['goods_id'=>$goods_id])->first();
        $order=substr(md5(Str::random(10)),5,15);
        try {
            $order_info=[
                'on_order'=>$order,
                'totalprice'=>$goods_info->goods_price,
                'add_time'=>time(),
            ];
            $order_arr=DB::table('order')->insert($order_info);
            $oid = DB::getPdo('order')->lastInsertId();
            $order_goods=[
                'oid'=>$oid,
                'goods_id'=>$goods_id,
                'goods_name'=>$goods_info->goods_name,
                'goods_price'=>$goods_info->goods_price,
                'user_id'=>$cart_info->user_id
            ];
            $order_goods_info=DB::table('orders_detail')->insert($order_goods);
            DB::commit();
            $add=[
                'ser'=>200,
                'msg'=>"购买成功",
            ];
            return json_encode($add,JSON_UNESCAPED_UNICODE);
        } catch (\Exception $exception) {
            DB::rollBack();
            $add=[
                'ser'=>5000,
                'msg'=>"购买失败",
            ];
            return json_encode($add,JSON_UNESCAPED_UNICODE);
            // return $exception->getMessage();
        }
    }

    /**
     * 订单列表
     */
    public function drop(){
        $where=[
            'user_id'=>$_GET['u_id'],
            'goods_id'=>$_GET['goods_id']
        ];
//        dd($where);
        $arr=DB::table("orders_detail")
        ->join('order', 'orders_detail.oid', '=', 'order.oid')
        ->select("goods_name","goods_price","on_order")
        ->where($where)
        ->get();
        $data=[
            'goods_name'=>$arr[0]->goods_name,
            'goods_price'=>$arr[0]->goods_price,
            'on_order'=>$arr[0]->on_order,
        ];
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }



    public $app_id;
    public $gate_way;
    public $notify_url;
    public $return_url;
    public $rsaPrivateKeyFilePath;
    public $aliPubKey;
    public function __construct(){
        $this->app_id = env('ALIPAY_APPID');
        $this->gate_way = 'https://openapi.alipaydev.com/gateway.do';
        $this->notify_url =env('NOTIFY_URL');  //回调地址
        $this->return_url =env('RETURN_URL');
        $this->rsaPrivateKeyFilePath = storage_path('app/key/private.pem');   //应用私钥
        $this->aliPubKey = storage_path('app/key/public.pem'); //支付宝公钥
    }

    /**
     * 提交订单
     */
    public function submitorder(){
        $goods_price=$_GET['goods_price'];
        //echo $goods_price;
        $goods_price=$_GET['goods_price'];
        $on_order=$_GET['on_order'];
        $info=DB::table('order')->where(['on_order'=>$on_order])->first();
//        dd($u_id,$goods_price,$on_order);
        //判断订单是否已被支付
        if($info->pay_time>0){
            die("订单已支付，请勿重复支付");
        }
        //判断订单是否已被删除
        if($info->is_delete==1){
            die("订单已被删除，无法支付");
        }
        $oid=$info->oid;
        //业务参数
        $bizcont = [
            'subject'           => 'Lening-Order: ' .$oid, //订单标提
            'out_trade_no'      => $on_order,       //订单号
            'total_amount'      => $goods_price,    //订单价格
            'product_code'      => 'QUICK_WAP_WAY',
        ];
        //公共参数
        $data = [
            'app_id'   => $this->app_id,
            'method'   => 'alipay.trade.wap.pay',
            'format'   => 'JSON',
            'charset'   => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'   => '1.0',
            'notify_url'   => $this->notify_url,        //异步通知地址
            'return_url'   => $this->return_url,        // 同步通知地址
            'biz_content'   => json_encode($bizcont),
        ];

        //签名
        $sign = $this->rsaSign($data);
        $data['sign'] = $sign;
        $param_str = '?';
        foreach($data as $k=>$v){
            $param_str .= $k.'='.urlencode($v) . '&';
        }
        $url = rtrim($param_str,'&');
        $url = $this->gate_way . $url;
        return redirect($url, 302);
//        header("Location:$url");       // 重定向到支付宝支付页面
    }


    public function rsaSign($params) {
        return $this->sign($this->getSignContent($params));
    }
    protected function sign($data) {
        $priKey = file_get_contents($this->rsaPrivateKeyFilePath);
        $res = openssl_get_privatekey($priKey);
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
            openssl_free_key($res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, 'UTF-8');
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = 'UTF-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }
    /**
     * 支付宝异步通知
     */
    public function notify()
    {
        DB::beginTransaction();
//        $p = json_encode($_POST);
//        $log_str = "\n>>>>>> " .date('Y-m-d H:i:s') . ' '.$p . " \n";
//        file_put_contents('logs/alipay_notify.log',$log_str,FILE_APPEND);
        $a=file_get_contents('logs/alipay_notify.log');
        $data=json_decode($a,true);
        dd($data);
        $time=strtotime($data['notify_time']);
        if($data['trade_status']=='TRADE_SUCCESS'){
            $on_order=$data['out_trade_no'];
            //4079552d330d625
//            dd($on_order);
            $where=[
                'on_order'=>$on_order
            ];
            $arr=DB::table('order')->where($where)->update(['is_delete'=>1,'pay_time'=>$time]);
            $oid=DB::table("order")->where($where)->first('oid');
            $goods_id=DB::table("orders_detail")->where(['oid'=>$oid->oid])->first('goods_id');
            $cart_ser=DB::table('cart')->where(['goods_id'=>$goods_id->goods_id])->delete();
            if($arr && $oid && $goods_id && $cart_ser){
                DB::commit();
                return 1;
            }else{
                DB::rollBack();
                return 0;
            }
        }
    }
    /**
     * 支付宝同步通知
     */
    public function aliReturn()
    {
        echo '<pre>';print_r($_GET);echo '</pre>';
    }
}
