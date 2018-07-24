<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class HouseorderController extends AppframeController{
	
	protected $house_model;
	protected $housetype_model;
	protected $housephoto_model;
	protected $housedetail_model;
	protected $houseorder_model;
    protected $city_model;
    protected $member_model;
    protected $memberCoupon_model;
    protected $coupon_model;
	protected $perpage = 20;
	
	public function _initialize() {
		parent::_initialize();
		$this->house_model=D("Common/House");
		$this->housetype_model=D("Common/Housetype");
		$this->housephoto_model=D("Common/Housephoto");
		$this->houseorder_model=D("Common/Houseorder");
		$this->housedetail_model=D("Common/Housedetail");
		$this->member_model=D("Common/Member");
		$this->city_model=D("Common/City");
		$this->memberCoupon_model=D("Common/MemberCoupon");
		$this->coupon_model=D("Common/Coupon");
	}

    public function createOrder(){
	    //1未支付,2已支付未确认,3已支付已确认,4已入住,5已退房,6已失效,7已退款
	    //考虑并发,两人同时下单
	    //isorder=1预定 0未预定
        $ordertime = time();
        $houseid = I('request.houseid');
        $uid = I('request.uid');
        $checkin_time = I('post.checkin_time');
        $checkout_time = I('post.checkout_time');
        $checkin_members = I('post.checkin_members');
        $staydays = I('post.staydays');
        $startweek = I('post.startweek');
        $endweek = I('post.endweek');
        $discount_cost = I('post.cost');

        $isOrder = 1;
        $insdata['mid'] = $uid;
        $insdata['houseid'] = $houseid;
        $insdata['ordernum'] = '';//唯一标识的订单号
        $insdata['housename'] = !empty($housename) ? $housename : '随风奔跑';
        $insdata['checkin_time'] = $checkin_time;
        $insdata['checkout_time'] = $checkout_time;
        $insdata['checkin_members'] = $checkin_members;
        $insdata['createtime'] = $ordertime;
        $insdata['staydays'] = $staydays;
        $insdata['orderstate'] = 2;
        $insdata['sum_cost'] = 0;
        $insdata['discount_cost'] = $discount_cost;
        $insdata['stayinfo'] = json_encode(array('startweek'=>$startweek,'endweek'=>$endweek));

        $userinfo = $this->member_model->getUserByUid('memberphone',array('mid'=>$uid));
        $insdata['orderphone'] = $userinfo[0]['memberphone'];

        $where = array('houseid'=>$houseid);
        $updata = array('isorder'=>$isOrder);
        if(empty($uid)){
            $data['code'] = -2;
            $data['msg'] = 'uid参数有误!';
            $this->ajaxData($data);
        }
        if(empty($checkin_time)){
            $data['code'] = -2;
            $data['msg'] = 'checkin_time参数有误!';
            $this->ajaxData($data);
        }
        if(empty($checkin_members)){
            $data['code'] = -2;
            $data['msg'] = 'checkin_members参数有误!';
            $this->ajaxData($data);
        }

        if(empty($houseid)){
            $data['code'] = -2;
            $data['msg'] = 'houseid参数有误!';
            $this->ajaxData($data);
        }else{
            $state = $this->house_model->where(array('houseid'=>$houseid))->field('isorder')->find();
            if($state['isorder'] == 1){
                $data['code'] = -3;
                $data['msg'] = '该房屋已被预定,请选择其他房屋!';
                $this->ajaxData($data);
            }
        }
        if(!empty($where)){
            $paystate = 0;//1支付成功,0支付失败
            //事务处理
            //如果未支付成功,则回滚插入的数据(即订单未入库=未生成)
            //如果支付成功则,生成一条订单数据,并且房源被标记已预订
            //开启事务
//            $this->housephoto_model->startTrans();
//            $this->housephoto_model->commit();
//            $this->housephoto_model->rollback();


            $this->housephoto_model->startTrans();
            //1.入订单库
            $insId = $this->houseorder_model->add($insdata);
            $upId = $this->house_model->where($where)->save($updata);
            //2.调用支付接口
            $paystate = 0;
            if($paystate){//成功
                $this->house_model->commit();
                $this->houseorder_model->commit();
            }else{
                $this->house_model->rollback();
                $this->houseorder_model->rollback();
            }
        }

        if($paystate && $insId && $upId){
            $this->ajaxData();
        }else{
            $data['code'] = -1;
            $data['msg'] = 'fail';
            $this->ajaxData($data);
        }
    }

    //我的订单列表
    public function orderList(){
        //1未支付,2已支付未确认,3已支付已确认,4已入住,5已退房,6已失效,7已退款
        $uid = I('get.uid',0,'intval');
        $state = I('get.orderstate',0,'intval');
        $page = I('get.page',1,'intval');
        $perpage = I('get.perpage',20,'intval');
        $limit = ($page - 1) * $perpage;
        if(!empty($state)){
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> $state
            );
        }
        else{
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> array('exp', 'IN (2,3)'),
            );
        }
        $order = $this->houseorder_model->getOrderByUid($where,'*',$limit,$perpage);

        foreach ($order as $or => $v){
            $order[$or]['checkin_time'] = date('Y-m-d',$order[$or]['checkin_time']);
            $order[$or]['checkout_time'] = date('Y-m-d',$order[$or]['checkout_time']);
            $order[$or]['stayinfo'] = json_decode($order[$or]['stayinfo'],true);
        }
        $data['code'] = 0;
        $data['msg'] = 'success';
        $data['data'] = $order;
        $this->ajaxData($data);
    }

    public function orderDetail(){
        $houseid = I('request.houseid',0,'intval');
        $orderid = I('request.orderid',0,'intval');
        $uid = I('request.uid',0,'intval');
//        $where['houseid'] = $houseid;
        $where['orderid'] = $orderid;
        $field = 'orderid,housename,checkin_time,checkout_time,staydays,discount_cost,stayinfo';
        $this->houseorder_model->where($where);
        $this->houseorder_model->field($field);
        $order = $this->houseorder_model->select();
        foreach ($order as $or=>$v){
            $order[$or]['checkin_time'] = date('H:i',$order[$or]['checkin_time']);
            $order[$or]['checkout_time'] = date('H:i',$order[$or]['checkout_time']);
            $order[$or]['stayinfo'] = json_decode($order[$or]['stayinfo'],true);
        }

        $field = 'bathroom,mindays,cash,price,maxmembers,housearea,bedtype,starttime,endtime';
        $where['houseid'] = $houseid;
        $housedetail = $this->housedetail_model->where($where)->field($field)->select();
        foreach($housedetail as $hd=>$v){
            $housedetail[$hd]['starttime'] = date('H:i',$housedetail[$hd]['starttime']);
            $housedetail[$hd]['endtime'] = date('H:i',$housedetail[$hd]['endtime']);
        }

        $field = 'houseaddress,houseposition,typeid,house_x,house_y';
        $where['houseid'] = $houseid;
        $house = $this->house_model->where($where)->field($field)->select();
        foreach ($house  as $h=>$v){
            $type = $this->housetype_model->where($v['typeid'])->field('housetype')->find();
            $house[$h]['housetype'] = $type['housetype'];
            //合并
            if($orderid){
                $house = array_merge($house[$h],$housedetail[$h],$order[$h]);
            }
        }
        $data['data']['orderdetail'] = $house;
        $this->ajaxData($data);
//        //有未过期未使用的优惠券
//        $haveCoupon  = $this->memberCoupon_model->getCouponGroupByConds(1,'cid','cid',array('mid'=>$uid,'state'=>1));
//        if($haveCoupon){
//            $cids = implode(',',$haveCoupon);
//            $where['cid'] = array('in',$cids);
//            $field = 'conditions';
//            $coupon = $this->coupon_model->where($where)->field($field)->select();
//        }
//        //此房子是否有折扣
//        $discount = $this->housedetail_model->where(array('houseid'=>$houseid))->field('discount,specialprice,price,cash')->select();
////        $this->houseorder_model->getHouseRent($houseid,$totalCost,$staydays,$discount=array(),$couponInfo = array());
////        print_r($coupon);
//        //先折扣,后减优惠
//        $this->houseorder_model->staydaysOldprice($detail,$discount);
//        $this->houseorder_model->getHouseRent($houseid,$totalCost=2000,$staydays=10,$discount=array(),$couponInfo = array());
//
//        //特殊价格

//        print_r($coupon);
    }
}