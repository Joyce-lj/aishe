<?php
/**
 * member(会员管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MemberCouponController extends AdminbaseController {

    protected $memberCoupon_model;
    protected $member_model;
    protected $coupon_model;

//    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->memberCoupon_model = D("Common/MemberCoupon");
        $this->member_model = D("Common/Member");
        $this->coupon_model = D("Common/Coupon");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台会员列表
    public function index() {
        $keyword = I('post.keyword');
        $state = I('post.state',0,'intval');
        if($keyword){
            $where['memberphone']  = array('like','%'.$keyword.'%');
        }
        if($state){
            $where['state']  = $state;
        }
        $count=$this->member_model->where($where)->count();
        $page = $this->page($count, 5);
        $this->member_model->where($where);
        $this->member_model->limit($page->firstRow , $page->listRows);
        $member = $this->member_model->order('mid')->field('*')->select();
        $this->assign('member',$member);
        $this->assign("page", $page->show('Admin'));
        $this->assign('keyword',$keyword);
        $this->display();
    }


    public function add() {
        if (IS_POST) {
            if ($this->memberCoupon_model->create()!==false) {
                $num = intval(I('post.num'));//发放优惠券的数量
                $mc['mid'] = I('post.mid');
                $mc['cid'] = I('post.cid');
                $mc['createtime'] = time();
                for($i=0; $i < $num; $i++){
                    $this->memberCoupon_model->add($mc);
                }

                //见num添加到coupon表
                $result = $this->coupon_model->where(array('cid'=>$mc['cid']))->setInc("sum",$num);
                $this->success("更新成功！",U('member/index'));
            } else {
                $this->error($this->memberCoupon_model->getError());
            }
        }
    }

    public function mcList(){
        $searchtimes = 1;
        $keyword = I('post.keyword');
        $state = I('post.state',0,'intval');
        if($keyword){
            $where['cname']  = array('like','%'.$keyword.'%');
        }
        if($state){
            $where['state']  = $state;
        }

        //get传参
        $mid = I("get.id",0,'intval');
        if($mid){
            $where['mid'] = $mid;
        }
        $memberphone = I('get.phone');

        //搜素的条件
        $memberid = I("post.memberid",0,'intval');
        if($memberid){
            $searchtimes = 2;
            $where['mid'] = $memberid;
            $phone = $this->member_model->where($where)->field('memberphone')->find();
        }
        $memberphone = $memberphone ? $memberphone : $phone['memberphone'];

        $this->memberCoupon_model->join('LEFT JOIN __COUPON__ ON __COUPON__.cid = __MEMBER_COUPON__.cid');
        $count=$this->memberCoupon_model->where($where)->count();
        $page = $this->page($count, 5);


        $this->memberCoupon_model->join('LEFT JOIN __COUPON__ ON __COUPON__.cid = __MEMBER_COUPON__.cid');
        $this->memberCoupon_model->where($where);
        $this->memberCoupon_model->limit($page->firstRow , $page->listRows);
        $mclist = $this->memberCoupon_model->select();
//        echo $this->memberCoupon_model->getLastSql();die;
        $this->assign('mclist',$mclist);
        $this->assign("page", $page->show('Admin'));
        $this->assign('keyword',$keyword);
        $this->assign('mid',$mid);//get
        $this->assign('memberid',$memberid);
        $this->assign('memberphone',$memberphone);
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

    public function edit() {
        $id = I("get.id",0,'intval');
        $where['mid'] = $id;
        $this->member_model->where($where);
        $member = $this->member_model->field('*')->find();
        $couponList = $this->coupon_model->CouponList($field='cid,cname');
        $this->assign("data", $member);
        $this->assign("couponlist", $couponList);
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
