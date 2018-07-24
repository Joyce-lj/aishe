<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class CouponController extends AppframeController{
	
	protected $memberCoupon_model;
	protected $coupon_model;

	
	public function _initialize() {
		parent::_initialize();
		$this->memberCoupon_model=D("Common/MemberCoupon");
		$this->coupon_model=D("Common/Coupon");
	}

	/**
     * 用户所有的优惠券(除过期的)
	*/
    public function userCoupon(){
        $page = I('get.page',1,'intval');
        $perpage = I('get.perpage',20,'intval');
        $limit = ($page - 1) * $perpage;
	    $uid = I('get.uid');
	    $state = I('get.state',0,'intval');
	    if(!isset($uid) || !isset($state)){
	        $data['code'] = -1;
	        $data['msg'] = '参数有误';
	        $this->ajaxData($data);
        }
	    $where['mid'] = $uid;
	    $where['state'] = $state;
	    if(empty($state)){//state=1未使用,2已使用,3已过期
            $where['state'] = array('neq',3);
        }else{
            $where['state'] = $state;
        }
//        $this->memberCoupon_model->where($where)->count();

        $coupon = $this->memberCoupon_model->getUsercoupon($field='*',$where,$limit);
        foreach ($coupon as $c => $v){
            $coupon[$c]['starttime'] = date('Y-m-d',$coupon[$c]['starttime']);
            $coupon[$c]['endtime'] = date('Y-m-d',$coupon[$c]['endtime']);
            $coupon[$c]['usestate'] = $coupon[$c]['state'];
            $coupon[$c]['conditions'] = json_decode($coupon[$c]['conditions'],true);

        }
        $data['data']['coupon'] = array_values($coupon);
        $this->ajaxData($data);
    }
}