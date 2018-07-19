<?php
/**
 * coupon(优惠券管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CouponController extends AdminbaseController {

    protected $coupon_model;
    protected $memberCoupon_model;

//    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->coupon_model = D("Common/Coupon");
        $this->memberCoupon_model = D("Common/MemberCoupon");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台优惠券列表
    public function index() {
        $keyword = I('request.keyword');
        if($keyword){
            $where['cname']  = array('like','%'.$keyword.'%');
        }
        $count=$this->coupon_model->where($where)->count();
        $page = $this->page($count, 5);
        $this->coupon_model->where($where);
        $this->coupon_model->limit($page->firstRow , $page->listRows);
        $coupon = $this->coupon_model->order('cid')->field('*')->select();
//        $couponCount = $this->memberCoupon_model->getCouponcountByConds('cid,count(*) as num','cid',array('state'=>2));
        $this->assign('coupon',$coupon);
        $this->assign("page", $page->show('Admin'));
        $this->assign('keyword',$keyword);
        $this->display();
    }


    // 后台房源标签添加
    public function add() {
    	$this->display();
    }

    public function useDetail(){
        $searchtimes = 1;
        $keyword = I('request.keyword');
        if($keyword){
            $where['memberphone']  = array('like','%'.$keyword.'%');
        }
        $cid = I('get.id',0,'intval');
        $where['as_coupon.cid'] = $cid;
        $couponid = I('request.couponid');
        if($couponid){
            $searchtimes = 2;
            $where['as_coupon.cid'] = $couponid;
            $phone = I('post.phone');
//            $where['as_member.memberphone'] = $phone;
        }
        $this->coupon_model->join('LEFT JOIN __MEMBER_COUPON__ ON __COUPON__.cid = __MEMBER_COUPON__.cid RIGHT JOIN __MEMBER__ ON __MEMBER__.MID = __MEMBER_COUPON__.mid');
        $count=$this->coupon_model->where($where)->count();
        $page = $this->page($count, 5);

        $this->coupon_model->join('LEFT JOIN __MEMBER_COUPON__ ON __COUPON__.cid = __MEMBER_COUPON__.cid RIGHT JOIN __MEMBER__ ON __MEMBER__.MID = __MEMBER_COUPON__.mid');
        $this->coupon_model->limit($page->firstRow , $page->listRows);
        $useDetail= $this->coupon_model->where($where)->select();

        $this->assign('useDetail',$useDetail);
        $this->assign('page',$page->show('Admin'));
        $this->assign('cid',$cid);
        $this->assign('couponid',$couponid);
        $this->assign('phone',$phone);
        $this->assign('keyword',$keyword);
        $this->assign('searchtimes',$searchtimes);
        $this->display();
    }


    // 后台房源添加提交
    public function add_post() {
    	if (IS_POST) {
            if ($this->coupon_model->create() !== false) {
                $detail['cname'] = I('post.cname');
                $detail['cintro'] = I('post.cintro');
                $detail['starttime'] = time();
                $detail['endtime'] = strtotime(I('post.endtime'));

                $discount = I('post.discount');
                $reach = I('post.reach');
                $cond =  array('reach' => $reach, 'discount' => $discount);
                $detail['conditions'] = json_encode($cond);
                $couponid = $this->coupon_model->add($detail);
                if($couponid){
                    $this->success('',U('coupon/index'));
                }
            } else {
                $this->error($this->housedetail_model->getError());
            }

        }
    }

    public function orderlist(){
        $this->display();
    }



    // 后台房源管理编辑
    public function edit() {
        $id = I("get.id",0,'intval');
        $where['cid'] = $id;
        $this->coupon_model->where($where);
        $coupon = $this->coupon_model->field('*')->find();
        foreach($coupon as $k=>$v){
            $coupon['cond'] = json_decode($coupon['conditions'],true);
            $coupon['dateline'] = date('Y-m-d H:i',$coupon['endtime']);
        }
        $this->assign("data", $coupon);
        $this->display();
    }
    
    public function edit_post() {
    	if (IS_POST) {
    	    $id = I('post.id',0,'intval');
    		if ($this->coupon_model->create()!==false) {
    			if ($this->coupon_model->save() !== false) {
                    $detail['cname'] = I('post.cname');
                    $detail['cintro'] = I('post.cintro');
//                    $detail['starttime'] = time();
                    $detail['endtime'] = strtotime(I('post.endtime'));

                    $discount = I('post.discount');
                    $reach = I('post.reach');
                    $cond =  array('reach' => $reach, 'discount' => $discount);
                    $detail['conditions'] = json_encode($cond);
    		        $this->coupon_model->where(array('id'=>$id))->save($detail);
    				$this->success("更新成功！",U('coupon/index'));
    			} else {
    				$this->error("更新失败！");
    			}
    		} else {
    			$this->error($this->coupon_model->getError());
    		}
    	}
    }







}
