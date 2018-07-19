<?php
/**
 * member(会员管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MemberController extends AdminbaseController {

    protected $member_model;
    protected $coupon_model;

//    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->member_model = D("Common/Member");
        $this->coupon_model = D("Common/Coupon");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台会员列表
    public function index() {
        $keyword = I('post.keyword');
        if($keyword){
            $where['memberphone']  = array('like','%'.$keyword.'%');
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


    // 后台房源标签添加
    public function add() {
    	$this->display();
    }

    public function useDetail(){
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



    public function edit() {
        $id = I("get.id",0,'intval');
        $where['mid'] = $id;
        $this->member_model->where($where);
        $member = $this->member_model->field('*')->find();
        $couponList = $this->coupon_model->CouponList($field='cid,cname,endtime');
        $this->assign("data", $member);
        $this->assign("couponlist", $couponList);
        $this->display();
    }
    






}
