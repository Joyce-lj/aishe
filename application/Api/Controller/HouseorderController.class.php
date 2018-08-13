<?php
namespace Api\Controller;

use Common\Controller\AppframeController;
use Couchbase\Document;

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
        $cmid = I('request.cmid');
        $checkin_time = strtotime(I('request.checkin_time'));
        $checkout_time = strtotime(I('request.checkout_time'));
        $checkin_members = I('request.checkin_members');
        $staydays = I('request.staydays');
        $discount_cost = I('request.cost');
        //存储于stayInfos
        $startweek = I('request.startweek');
        $endweek = I('request.endweek');
        $discount = I('request.discount');

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
        }

        //同时下单
        $this->houseorder_model->startTrans();
        $alreadyDate = $this->houseorder_model->getOrderedHouseTime($houseid,$lock=true);
        $pickDate =  dateList($checkin_time,$checkout_time);
        $interDate = array_intersect($alreadyDate,$pickDate);
        if($interDate){//有交集
            $data['code'] = -1;
            $data['msg'] = 'fail';//下单日期与已租日期有交集
            //写入log
            $failReason = '用户'.$uid.'房源'.$houseid.'下单日期'.$checkin_time.'至'.$checkout_time.'与已租日期有交集';
            $logfile = dirname(dirname(dirname(__DIR__))).'\/data\/errorlog\/order\/';
            if(!is_dir($logfile)){echo 1;
                mkdir($logfile, 0777,true);
            }
            $filename = 'order.txt';
            addLog($failReason,$logfile,$filename);
        }else{
            //房屋名称
            $isOrder = 1;
            $insdata['mid'] = $uid;
            $insdata['houseid'] = $houseid;
            $housename =  $this->getHousenameById($houseid);
            //所使用的优惠券
            $cid = $this->memberCoupon_model->where(array('cmid'=>$cmid))->field('cid')->find();
            $cup = $this->coupon_model->CouponList('conditions',array('cid'=>$cid['cid']));
            if(!empty($cup)){
                $cond = json_decode($cup[0]['conditions'],true);
                $coupon = $cond['discount'];
            }else{
                $coupon = 0;
            }

            $lastorder = $this->houseorder_model->limit(1)->order('createtime DESC')->field('orderid')->find();

            $insdata['ordernum'] = $this->getOrderNum($lastorder['orderid']);//唯一标识的订单号
            $insdata['housename'] = !empty($housename['housename']) ? $housename['housename'] : '房屋名称未知';
            $insdata['checkin_time'] = $checkin_time;
            $insdata['checkout_time'] = $checkout_time;
            $insdata['checkin_members'] = $checkin_members;
            $insdata['createtime'] = $ordertime;
            $insdata['staydays'] = $staydays;
            $insdata['orderstate'] = 2;   //已支付未确认
            $insdata['sum_cost'] = $discount_cost;
            $insdata['discount_cost'] = $discount_cost;
            $insdata['stayinfo'] = json_encode(array(
                'startweek'=>$startweek,
                'endweek'=>$endweek,
                'discount'=>$discount,
                'coupon'=>$coupon,
            ));

            $userinfo = $this->member_model->getUserByUid('memberphone',array('mid'=>$uid));
            $insdata['orderphone'] = $userinfo[0]['memberphone'];

            $where = array('houseid'=>$houseid);
            $updata = array('isorder'=>$isOrder);

        }


        if(!empty($where)){
            $paystate = 0;//1支付成功,0支付失败
            //事务处理
                //如果未支付成功,则回滚插入的数据(即订单未入库=未生成)
                //如果支付成功则,生成一条订单数据,并且房源被标记已预订
            //开启事务
//            $this->houseorder_model->startTrans();
            //1.入订单库
            $insId = $this->houseorder_model->add($insdata);
            //修改用户优惠券状态=已过期
            $upstate['state'] = 2;
            $cstate = $this->memberCoupon_model->where(array('cmid'=>$cmid,'mid'=>$uid))->save($upstate);
//            echo $this->memberCoupon_model->getLastSql();die;
            //2.调用支付接口
            $paystate = 1;
            if($paystate){//成功
                $this->houseorder_model->commit();
                $this->memberCoupon_model->commit();

                $this->ajaxData();
            }else{
                $this->houseorder_model->rollback();
                $this->memberCoupon_model->rollback();

                $data['code'] = -1;
                $data['msg'] = 'fail';
                $this->ajaxData($data);
            }
        }

//        if($paystate && $insId && $upId){
//        if($paystate){
//            $this->ajaxData();
//        }else{
//            $data['code'] = -1;
//            $data['msg'] = 'fail';
//            $this->ajaxData($data);
//        }
    }

    //我的订单列表
    public function orderList(){
        //1未支付,2已支付未确认,3已支付已确认,4已入住,5已退房,6已失效,7已退款8未入住
        $uid = I('get.uid',0,'intval');
        $state = I('get.orderstate',0,'intval');
        $page = I('get.page',1,'intval');
        $perpage = I('get.perpage',20,'intval');
        $limit = ($page - 1) * $perpage;
        if($state == 4){//已入住
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> $state
            );
        }
        if($state == 8){//未入住
            $where['where'] = array(
                'mid'=> $uid,
                'orderstate'=> array('exp', 'IN (2,3)'),
            );
        }
        if(empty($state)){
            $where['where'] = array(
                'mid'=> $uid,
                //'orderstate'=> $state
            );
        }
        $order = $this->houseorder_model->getOrderByUid($where,'*',$limit,$perpage);

        foreach ($order as $or => $v){
            $order[$or]['checkin_time'] = date('n',$order[$or]['checkin_time']).'月'.date('j',$order[$or]['checkin_time']).'日';
            $order[$or]['checkout_time'] = date('n',$order[$or]['checkout_time']).'月'.date('j',$order[$or]['checkout_time']).'日';
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
            $order[$or]['checkin_time'] = date('n',$order[$or]['checkin_time']).'月'.date('j',$order[$or]['checkin_time']).'日';
            $order[$or]['checkout_time'] = date('n',$order[$or]['checkout_time']).'月'.date('j',$order[$or]['checkout_time']).'日';
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

    public function getHousenameById($houseid=0){
	    $name = $this->house_model->where(array('houseid'=>$houseid))->field('housename')->find();
	    return $name;
    }

    public function getOrderNum($orderid=0){
	    if($orderid){
	        $num = $this->houseorder_model->where(array('orderid'=>$orderid))->field('ordernum')->find();
	        if(!empty($num['ordernum'])){
                $st = substr($num['ordernum'], -1);
                $or = intval($st +1);
                $orderNum = date('Ymd').'00000'.$or;
            }else{
                $orderNum = date('Ymd').'000001';
            }
	        return $orderNum;
        }else{
	        $orderNum = date('Ymd').'000001';
	        return $orderNum;
        }
    }
}