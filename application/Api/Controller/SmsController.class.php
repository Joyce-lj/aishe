<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class SmsController extends AppframeController{

    protected $member_model;
    protected $accessKeyId = 'LTAIde4hhCEYq5rn'; //LTAIde4hhCEYq5rn
    protected $accessKeySecret = 'P2pEtkR5rui5QPrsaqQuWRhSVWgUzr';//P2pEtkR5rui5QPrsaqQuWRhSVWgUzr
    protected $SignName = '爱舍';
    protected $CodeId = 'SMS_141955014';

    public function __construct(){
        $this->member_model = D('Common/Member');
        Vendor("SignatureHelper",'./simplewind/Core/Library/Vendor/sms',".php");
    }

//	public function _initialize() {
//		parent::_initialize();
//	}

    /**
     * 发送验证码
     * @param phone,msg
     */
    public function code($phone,$time){
        $sendDate = date('Y-m-d',time());

        if(!$this->isphone($phone)){
            $msg = "手机号不正确";
            echo json_encode(array('code'=> -1,'msg'=>$msg,'phone'=>$phone));die;
        }
        $params["PhoneNumbers"] = $phone;
        $params["TemplateCode"] = $this->CodeId; //模板

        //记录存储验证码
        $cache = S(array('type'=>'file','prefix'=>'code_','expire'=>60));//'expire'=>60
        $key = $phone;
        $verifycode = $cache->$key; // 获取缓存
        if(!$verifycode){
            $code = rand(100000,999999);
            $cache->$key = $code;
            $verifycode = $cache->$key;
        }

//        $params['TemplateParam'] = ["code" => $verifycode]; //验证码
        $params['TemplateParam'] = array('code'=>$verifycode); //验证码
        return $this->send($params,$verifycode,$time);
    }


    //发送短信消息
    private function send($params=array(),$verifycode,$time){

        //验证发送限制
//        $this->sendLimit($time,$counts);
        $params["SignName"] = $this->SignName;

        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
//print_r($params);die;
        $helper = new \SignatureHelper();
        $content = $helper->request(
            $this->accessKeyId,
            $this->accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        );


        if($content===false){
            $msg = "发送异常";
            echo json_encode(array('code'=>-2,'msg'=>$msg));die;
        }else{
            $data = (array)$content;
            if($data['Code']=="OK"){

                $affnums = $this->member_model->where(array('memberphone'=>$params['PhoneNumbers']))->count();
                if($affnums){
                    $updata = array('verifycode'=>$verifycode);
                    $this->member_model->where(array('memberphone'=>$params['PhoneNumbers']))->setInc('counts',1);
                    $this->member_model->where(array('memberphone'=>$params['PhoneNumbers']))->save($updata);
                }
                if(empty($affnums)){
                    $ins = array('memberphone'=>$params['PhoneNumbers'],'counts'=>1,'verifycode'=>$verifycode);
                    $this->member_model->add($ins);
                }
                $msg = "发送成功";//,'a'=>$verifycode,'b'=>$params['PhoneNumbers']
                echo json_encode(array('code'=>0,'msg'=>$msg));die;
            }else{
                $msg = "发送失败 ".$data['Message'];

                $res = $this->errorMsg($data['Code'],$data['Message']);
                echo json_encode($res);die;
            }
        }
    }


    public function isphone($phone){
        if (!is_numeric($phone)) {
            return false;
        }elseif(preg_match("/^1[34578]{1}\d{9}$/", $phone)){
            return true;
        }else{
            return false;
        }
    }


    protected function errorMsg($code = '',$msg){
        if($code == 'isv.BUSINESS_LIMIT_CONTROL'){  //isv.BUSINESS_LIMIT_CONTROL
            $data['code'] = -9;
            $data['msg'] = '发送太频繁,请稍后再发哦';
            return $data;
        }else{

//            $message = array (
//                'InvalidDayuStatus.Malformed' => '账户短信开通状态不正确',
//                'InvalidSignName.Malformed' => '短信签名不正确或签名状态不正确',
//                'InvalidTemplateCode.MalFormed' => '短信模板Code不正确或者模板状态不正确',
//                'InvalidRecNum.Malformed' => '目标手机号不正确，单次发送数量不能超过100',
//                'InvalidParamString.MalFormed' => '短信模板中变量不是json格式',
//                'InvalidParamStringTemplate.Malformed' => '短信模板中变量与模板内容不匹配',
//                'InvalidSendSms' => '触发业务流控',
//                'InvalidDayu.Malformed' => '变量不能是url，可以将变量固化在模板中'
//            );
//            if (isset ( $message [$status] )) {
//                return $message [$status];
//            }

            return $data = array('code'=>$code,'msg'=>$msg);
        }

    }



    public function getErrorMessage($status) {
        // 阿里云的短信 乱八七糟的(其实是用的阿里大于)
        // https://api.alidayu.com/doc2/apiDetail?spm=a3142.7629140.1.19.SmdYoA&apiId=25450
        $message = array (
            'InvalidDayuStatus.Malformed' => '账户短信开通状态不正确',
            'InvalidSignName.Malformed' => '短信签名不正确或签名状态不正确',
            'InvalidTemplateCode.MalFormed' => '短信模板Code不正确或者模板状态不正确',
            'InvalidRecNum.Malformed' => '目标手机号不正确，单次发送数量不能超过100',
            'InvalidParamString.MalFormed' => '短信模板中变量不是json格式',
            'InvalidParamStringTemplate.Malformed' => '短信模板中变量与模板内容不匹配',
            'InvalidSendSms' => '触发业务流控',
            'InvalidDayu.Malformed' => '变量不能是url，可以将变量固化在模板中'
        );
        if (isset ( $message [$status] )) {
            return $message [$status];
        }
        return $status;
    }



    /**
     * 查询发送短信详情
     * eg某个手机号每天发送的短信数量
     */
    public function sendDetails($phone,$senddate,$pagesize=10){
        $params = array ();

        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $params["PhoneNumber"] = $phone;
        $params["SendDate"] = $senddate;
        $params["PageSize"] = !empty($pagesize) ? $pagesize : 10;
        $params["CurrentPage"] = 1;
        // fixme 可选: 设置发送短信流水号
//        $params["BizId"] = "yourBizId";

        $helper = new \SignatureHelper();

        // 此处可能会抛出异常，注意catch
        try {
            $content = $helper->request(
                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "QuerySendDetails",
                    "Version" => "2017-05-25",
                ))
            // fixme 选填: 启用https
            // ,true
            );
            return $content;

        }catch(Exception $e){

            print $e->getMessage();
        }



    }

    /**
     * 针对同一手机号,同一天,发送短信的限制
     */
    public function sendLimit($timestamp,$counts){
        $tommTime = strtotime(date('Y-m-d',strtotime('+1days')));

        if($counts['counts'] > 10){
            if($timestamp < $tommTime ){
                $data['code'] = -8;
                $data['msg'] = '发送太频繁,一天内最多发送10条哦';
                echo json_encode($data);die;
            }
        }
        if($counts['counts'] > 5){
            if(intval(time() - $timestamp) < 3600 ){
                $data['code'] = -7;
                $data['msg'] = '发送太频繁,一小时内最多发送5条哦';
                echo json_encode($data);die;
            }
        }
        if($counts['counts'] > 1){
            if(intval(time() - $timestamp) < 60 ){
                $data['code'] = -6;
                $data['msg'] = '发送太频繁,一分钟内最多发送1条哦';
                echo json_encode($data);die;
            }
        }
    }

}