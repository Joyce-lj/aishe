<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class LoginController extends AppframeController{

    protected $member_model;
    protected $appsecret;
	public function _initialize() {
		parent::_initialize();
//        Vendor("Sms",'./simplewind/Core/Library/Vendor/sms',".php");
        $this->member_model = D('Common/Member');
        $this->appsecret =  md5('asdepartment0801'.MODULE_NAME .CONTROLLER_NAME .ACTION_NAME);
	}


	public function test(){
        $cache = S(array('type'=>'file','prefix'=>'lasttime_'));//'expire'=>60
        $key = '18518756787';
        $lasttime = $cache->$key;
        $timestamp = I('request.time');
        echo intval($timestamp-$lasttime);
        echo '<br>';
        echo intval($lasttime);
        echo '<br>';
        echo $this->appsecret;
        echo '<br>';
        echo array_sum(array(0,1,2,3,4));
        echo '<br>';
	    echo time();die;
    }


    public function sendCode(){
//        $code = new \Sms();            //此类存放在simplewind\Core\Library\Vendor\sms\Sms.class.php
        $sms = new SmsController();
        $phone = I('request.phone');
        $timestamp = I('request.time');
        $token = I('request.token'); //md5(currDate+秘钥串)
        $res = $this->checkToken($timestamp,$token);
        if($res['code'] == 0){
            $this->sendLimit($sms,$phone,$timestamp);
        }
    }



    /***
     * 注册or登录
     */
    public function checkUser(){  //只有请求登录时,未注册的用户才入库,发验证码不入库
        $phone = I('post.phone');
        $code = I('post.code');
        $affnums = $this->member_model->where(array('memberphone'=>$phone))->count();

        if(empty($affnums)){
            $ins = array('memberphone'=>$phone,'counts'=>1);
            $this->member_model->add($ins);
        }
        //验证手机号和验证码
        $cache = S(array('type'=>'file','prefix'=>'code_'));//'expire'=>60
        $key = $phone;
        $verifycode = $cache->$key;
        if(!$verifycode){
            $data['code'] = -1;
            $data['msg'] = '验证码失效,请重新发送!';
            $data['verifycode'] = $verifycode;
            $data['data'] = $data;
            $this->ajaxData($data);
        }else{
            if($code == $verifycode){
                $data['code'] = 0;
                $data['msg'] = 'success';
                $res = $this->member_model->where(array('memberphone'=>$phone))->field('mid')->find();
                $data['data'] = array('uid'=>$res['mid'],'memberphone'=>$phone);
                $this->ajaxData($data);
            }else{
                $data['code'] = -2;
                $data['msg'] = '验证码错误!';
                $this->ajaxData($data);
            }
        }
    }


    public function checkToken($timestamp,$token){
        $currDate = date('Y-m-d',time());
        $timestampDate = date('Y-m-d',$timestamp);
        $apitoken  = md5($this->appsecret.$currDate);

        if(empty($token)){
            $data['code'] = -4;
            $data['msg'] = 'token不存在';
            $this->ajaxData($data);
        }else{
            if(md5($token.$timestampDate) != $apitoken){
                $data['code'] = -5;
                $data['msg'] = 'token错误';
                $this->ajaxData($data);
            }else{
                $data['code'] = 0;
                $data['msg'] = 'success';
                return $data;
            }
        }
    }

    public function sendLimit($sms,$phone,$timestamp){
        $cache = S(array('type'=>'file','prefix'=>'lasttime_'));//'expire'=>60
        $key = $phone;
        $lasttime = $cache->$key;
        //cacheCounts=lastcounts
        $cacheCounts = S(array('type'=>'file','prefix'=>'lastcount_'));
        $key = $phone;
        $lastcounts = $cacheCounts->$key;
        if(!$lastcounts){
            $cacheCounts->$key = 0;
            $lastcounts = $cacheCounts->$key;
        }

        //dayCounts(24小时内总共的条数)
        $daycacheCounts = S(array('type'=>'file','prefix'=>'daycount_'));
        $key = $phone;
        //daycounts
        $dayCounts = $daycacheCounts->$key;
        $totalCounts = array_sum($dayCounts);
        if(!$totalCounts){
            $daycacheCounts->$key = array(0);
            $dayCounts = $daycacheCounts->$key;
        }
//        echo $lastcounts;die;
//        print_r($dayCounts) ;
//        echo $timestamp - $lasttime;echo '---';
//        echo $lastcounts;echo '--';echo date('y-m-d H:i',$lasttime);die;
        if($lasttime){
            //24小时内
            if(intval($timestamp) > intval($lasttime + 60*60*24)){ //24小时内只能发10条
                $cache->$key = $timestamp;
                $lasttime = $cache->$key;
                $cacheCounts->$key = 0;//lastcount
                $daycacheCounts->$key = 0;//daycount
                //发验证码程序
                $sms->code($phone,$lasttime);
//            }elseif(intval($timestamp) > intval($lasttime + 60*60) && ($timestamp < intval($lasttime + 60*60*24) || $timestamp == intval($lasttime + 60*60*24))){
            }elseif(intval($timestamp) > intval($lasttime + 60) && ($timestamp < intval($lasttime + 60*60*24) || $timestamp == intval($lasttime + 60*60*24))){
                $dayCounts = $daycacheCounts->$key;
                $lastcounts = $cacheCounts->$key;
                $totalCounts = array_sum($dayCounts);
                if($lastcounts > 10 || $lastcounts == 10){
                    $data['code'] = -10;
                    $data['msg'] = '24小时之内最多发送10条哦!';
                    $this->ajaxData($data);
                }else{
                    $cache->$key = $timestamp;
                    $lasttime = $cache->$key;
                    $cacheCounts->$key = $lastcounts+1;
                    $sms->code($phone,$lasttime);
                }
            }elseif(intval($timestamp) > intval($lasttime + 60) && ($timestamp < intval($lasttime + 60*60) || $timestamp == intval($lasttime + 60*60))){
                $lastcounts = $cacheCounts->$key;
                if(($lastcounts > 5 || $lastcounts == 5) && (count($dayCounts) == 1 || count($dayCounts)==3)){//因为第二次过来count仍然>5
                    $data['code'] = -11;
                    $data['msg'] = '1小时之内最多发送5条哦!';
                    //重置为0,为了第二个5出现
                    $dayCounts = $daycacheCounts->$key;
                    if(array_sum($dayCounts) == 5){
                        $cacheCounts->$key = 5;
                    }
                    $this->ajaxData($data);
                }else{
                    $cache->$key = $timestamp;//时间
                    $lasttime = $cache->$key;
                    $cacheCounts->$key = $lastcounts+1;//数量
                    //5
                    $lastcounts = $cacheCounts->$key;
                    if($lastcounts == 5 || $lastcounts == 10){
                        $dayCounts[] = $lastcounts;
                        $daycacheCounts->$key = $dayCounts;
                    }
                    $sms->code($phone,$lasttime);
                }
            }elseif(intval($timestamp) < intval($lasttime + 60)){
                $data['code'] = -12;
                $data['msg'] = '发送太频繁,请稍后再发!(1分钟之内最多发送1条哦!)';
                $this->ajaxData($data);
            }

        }else{ //如果第一次发送
            $cache->$key = $timestamp;
            $lasttime = $cache->$key;
            $cacheCounts->$key = $lastcounts+1;
            $sms->code($phone,$lasttime);
        }
    }

}