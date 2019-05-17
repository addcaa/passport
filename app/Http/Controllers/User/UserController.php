<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    /**
     * 注册
     */
    public function reg(){
        $res=DB::table('users')->insert($_POST);
        if($res){
            $arr=[
                'res'=>200,
                'msg'=>'注册成功'
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $arr=[
                'res'=>11000,
                'msg'=>'注册失败'
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     *
     * 登录
     */
    public function log(){
        $u_pwd=$_POST['u_pwd'];
        $info=DB::table('users')->where(['u_emali'=>$_POST['u_emali']])->first();
        if($info){
            if(password_verify($u_pwd,$info->u_pwd)){
                $token=$this->token($info->u_id);
                $key='login_token'.$info->u_id;
                Redis::set($key,$token);
                Redis::expire($key,604800);
                $arr=[
                    'res'=>200,
                    'msg'=>'登陆成功',
                    'data'=>[
                        'u_id'=>$info->u_id,
                        'token'=>$token
                    ]
                ];
                return json_encode($arr,JSON_UNESCAPED_UNICODE);
            }else{
                $arr=[
                    'res'=>50001,
                    'msg'=>'登陆失败'
                ];
                return json_encode($arr,JSON_UNESCAPED_UNICODE);
            }
        }else{
            $arr=[
                'res'=>50000,
                'msg'=>'没有这个用户'
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }
    }

    //获得token
    function token($id){
        $range=Str::random(10);
        $token=substr(sha1(time().$id.$range),5,15); //sha1 计算字符串散值 （加密差不多）
        return $token;
    }

    /**
     * 个人中心
     *
     */
    public function index(){
        $u_id=$_GET['u_id'];
        $res=DB::table('users')->where(['u_id'=>$u_id])->first();
        if($res){
            $arr=[
                'res'=>200,
                'msg'=>'获得用户信息成功',
                'data'=>[
                    'u_name'=>$res->u_name,
                    'u_emali'=>$res->u_emali,
                    'u_time'=>$res->u_time
                ]
            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }else{
            $arr=[
                'res'=>40000,
                'msg'=>'授权失败',

            ];
            return json_encode($arr,JSON_UNESCAPED_UNICODE);
        }
    }

}
