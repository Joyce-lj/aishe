<?php
/**
 * houseorder(订单管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class HouseorderController extends AdminbaseController {

    protected $houseorder_model;
    protected $memberCoupon_model;
    protected $coupon_model;
    public function _initialize() {
        parent::_initialize();
        $this->houseorder_model = D("Common/Houseorder");
        $this->memberCoupon_model = D("Common/MemberCoupon");
        $this->coupon_model = D("Common/Coupon");
    }

    //订单列表
    public function index() {
        $keyword = I('request.keyword');
        $orderstate = I('request.orderstate');
        if($keyword){
            $where['housename']  = array('like','%'.$keyword.'%');
            $where['orderphone']  = array('like','%'.$keyword.'%');
            $where['_logic']  = 'OR';
            $map['_complex'] = $where;
        }

        if($orderstate){
            $map['orderstate'] = $orderstate;
        }
        $count=$this->houseorder_model->where($map)->count();
        $page = $this->page($count, 5);

        $order = $this->houseorder_model
            ->where($map)
            ->limit($page->firstRow , $page->listRows)
            ->order("createtime DESC")
            ->select();
//echo $this->houseorder_model->getLastSql();
        $state = array(
            '1' => '已支付,未确认',
            '2' => '已支付,已确认',
            '3' => '已入住',
            '4' => '已退房,未退押金',
            '5' => '已退房,已退押金',
            '6' => '已失效',
            '7' => '已退款',
        );
        $this->assign('houseorder',$order);
        $this->assign("page", $page->show('Admin'));
        $this->assign('keyword',$keyword);
        $this->assign('orderstate',$orderstate);
        $this->assign('state',$state);
        $this->assign('statecount',count($state));
        $this->display();
    }

    //订单详情
    public function orderDetail(){
        $data = $this->someoneOrderInfo();
        $this->assign('orderdetail',$data);
        $this->assign('index',$data['index']);
        $this->assign('realmoney',$data['realmoney']);
        $this->assign('template','orderDetail');
        $this->display();
    }


    public function checkinList(){
        //服务器时间
        $thrDaysBeforeDate = date("Y-m-d",strtotime("-3 day"));
        $twoDaysBeforeDate = date("Y-m-d",strtotime("-2 day"));
        $thrDaysBeforeTime = strtotime($thrDaysBeforeDate);
        $twoDaysBeforeTime = strtotime($twoDaysBeforeDate);

        //入住提醒=入住的前3天提醒
        $field = '*';
        $where['checkin_time'] = array(array('egt',$thrDaysBeforeTime), array('elt',$twoDaysBeforeTime));

        $count=$this->houseorder_model->where($where)->count();
        $page = $this->page($count, 5);

        $checkin = $this->houseorder_model->checkinNotice($field,$where,$page);
//        echo $this->houseorder_model->getLastSql();
        $this->assign('checkin',$checkin);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    public function checkoutList(){
        $oneDaysBeforeDate = date("Y-m-d",strtotime("-1 day"));
        $currentDate = date("Y-m-d",time());
        $oneDaysBeforeTime = strtotime($oneDaysBeforeDate);
        $currentTime = strtotime($currentDate);

        //退房提醒=退房的前1天提醒
        $field = '*';
        $where['checkout_time'] = array(array('egt',$oneDaysBeforeTime), array('elt',$currentTime));

        $count=$this->houseorder_model->where($where)->count();
        $page = $this->page($count, 5);

        $checkout = $this->houseorder_model->checkinNotice($field,$where,$page);
//        echo $this->houseorder_model->getLastSql();
        $this->assign('checkout',$checkout);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }


    //退还押金
    public function cashBack(){
        $this->updateStayinfo();
    }
    //退款
    public function refund(){
        $data = $this->someoneOrderInfo();
        $this->assign('orderdetail',$data);
        $this->assign('index',$data['index']);
        $this->assign('realmoney',$data['realmoney']);
        $this->assign('template','refund');
        $this->display('orderDetail');

//        $this->updateStayinfo();
    }

    public function updateStayinfo(){
        $memberIDS = I('post.memberID');
        $orderid = I('post.orderid',0,'intval');
        $updata['stayinfo'] = json_encode($memberIDS);
        $where['orderid'] = $orderid;
        $this->houseorder_model->where($where)->save($updata);
    }


    public function confirmOrder(){
        $data = $this->someoneOrderInfo();
        $this->assign('orderdetail',$data);
        $this->assign('index',$data['index']);
        $this->assign('realmoney',$data['realmoney']);
        $this->assign('template','confirmOrder');
        $this->display('orderDetail');
    }


    public function someoneOrderInfo(){
        $orderid = I('get.orderid',0,'intval');
        $where['orderid'] = $orderid;
        $this->houseorder_model->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSEORDER__.houseid = __HOUSEDETAIL__.houseid');
        $this->houseorder_model->where($where);
        $detail = $this->houseorder_model->find();
        if($detail['isdiscount']){
            $detail['discount2'] = json_decode($detail['discount'],true);
        }
        $discount2 = $detail['discount2'];
        $discount3= arraySort($discount2,'days');
        $detail['discount2'] = array_values($discount3);

        //用户优惠券
        $where = array();
        $where['mid'] = $detail['mid'];
        $cid = $this->memberCoupon_model->getCouponGroupByConds(1,'cid','cid',$where);
        if(!empty($cid)){
            $cwhere['cid'] = array('in',$cid);
            $coupon = $this->coupon_model->CouponList('cname,conditions',$cwhere);

            foreach ($coupon as $k=>$v){
                $conditions = json_decode($coupon[$k]['conditions'],true);
                $detail['yh'][$k] = array_merge($conditions,array('cname'=>$coupon[$k]['cname']));
                $detail['yhlevel'][$k] = $conditions['reach'];
            }
            $youhui= arraySort($detail['yh'],'reach');
            $detail['yh'] = array_values($youhui);
            //租金
            $rentdays = floor(intval(($detail['checkout_time'] - $detail['checkin_time']))/86400);
            $totalmoney = $rentdays * intval($detail['price']);
            //总租金和满减金额最做对比
            array_unshift($detail['yhlevel'],intval($totalmoney));
            sort($detail['yhlevel']);
            $index= array_search($totalmoney,$detail['yhlevel']);//租金所在的位置[下标]
            //应付金额
            $realmoney = $totalmoney - $detail['yh'][$index-1]['discount'];
            $detail['totalmoney'] = $totalmoney;
            $detail['index'] = $index;
            $detail['realmoney'] = $realmoney;

        }else{
            $detail['index'] = 0;
            $detail['realmoney'] = 0;
        }
        return $detail;
    }

    public function orderlist(){
       $this->display();
    }










}
