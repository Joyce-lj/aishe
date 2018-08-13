<?php

/* * 
 * 菜单
 */
namespace Common\Model;
use Common\Model\CommonModel;
class HouseorderModel extends CommonModel {

    //自动验证
    protected $_validate = array(
//        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
//        array('name', 'require', '菜单名称不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH ),
//        array('filename', 'require', '应用不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH ),
//        array('savepath', 'require', '模块名称不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH ),
//        array('savename', 'require', '方法名称不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH ),
//        array('app,model,action', 'checkAction', '同样的记录已经存在！', 1, 'callback', CommonModel:: MODEL_INSERT   ),
//    	array('id,app,model,action', 'checkActionUpdate', '同样的记录已经存在！', 1, 'callback', CommonModel:: MODEL_UPDATE   ),
//        array('parentid', 'checkParentid', '菜单只支持四级！', 1, 'callback', 1),
    );
    //自动完成
    protected $_auto = array(
            //array(填充字段,填充内容,填充条件,附加规则)
    );




    /**
     * 入住提醒列表
     * @param $field(查找的字段) , $where(查询条件)
     * @return array
    */
    public function checkinNotice($field='*',$where = array(),$page){
        if(!empty($page)){
            $this->join('LEFT JOIN __HOUSE__ ON __HOUSE__.houseid = __HOUSEORDER__.houseid');
            $this->where($where);
            $this->limit($page->firstRow , $page->listRows);
            $this->order("as_houseorder.createtime DESC");
            $res = $this->field($field)->select();
        }else{
            $this->join('LEFT JOIN __HOUSE__ ON __HOUSEORDER__.houseid = __HOUSE__.houseid');
            $res = $this->where($where)->order('as_houseorder.createtime DESC')->field($field)->select();
        }
        return $res;
    }

    public function getOrderByUid($where,$field='*',$limit=0,$perpage=20){
        if(isset($where['where']) && !empty($where['where'])){
            $this->where($where['where']);
        }
        $this->limit($limit,$perpage);
        $this->field($field);
        $res = $this->select();
        return $res;
    }

    /**
     * 应支付的房屋金额(先折后减)
     * @params houseid,totalCost,staydays,discount(满天数打折),couponinfo(满金额优惠)
     * @return array
    */
    public function getHouseRent($houseid,$totalCost,$staydays,$discount=array(),$couponInfo = array()){

        //用户优惠券
//        $where = array();
//        $where['mid'] = $detail['mid'];
//        $cid = $this->memberCoupon_model->getCouponGroupByConds(1,'cid','cid',$where);
//        if(!empty($cid)){
//            $cwhere['cid'] = array('in',$cid);
//            $coupon = $this->coupon_model->CouponList('cname,conditions',$cwhere);
//
//            foreach ($coupon as $k=>$v){
//                $conditions = json_decode($coupon[$k]['conditions'],true);
//                $detail['yh'][$k] = array_merge($conditions,array('cname'=>$coupon[$k]['cname']));
//                $detail['yhlevel'][$k] = $conditions['reach'];
//            }
//            $youhui= arraySort($detail['yh'],'reach');
//            $detail['yh'] = array_values($youhui);
//            //租金
//            $rentdays = floor(intval(($detail['checkout_time'] - $detail['checkin_time']))/86400);
//            $totalmoney = $rentdays * intval($detail['price']);
//            //总租金和满减金额最做对比
//            array_unshift($detail['yhlevel'],intval($totalmoney));
//            sort($detail['yhlevel']);
//            $index= array_search($totalmoney,$detail['yhlevel']);//租金所在的位置[下标]
//            //应付金额
//            $realmoney = $totalmoney - $detail['yh'][$index-1]['discount'];
//            $detail['totalmoney'] = $totalmoney;
//            $detail['index'] = $index;
//            $detail['realmoney'] = $realmoney;


            if(empty($discount)){
            if(empty($couponInfo)){
                $house['totalcost'] = $totalCost;

            }
        }
    }

    /**
     * 入住天数原价(包括特殊价格,不包括折扣和优惠券部分)
     * @params $staydate array,$specialprice array
     * @return
    */
    public function staydaysOldprice($staydate,$specialprice){
        print_r($staydate);
    }


    /**
     * 房源已租日期
     * return array
     */
    public function getOrderedHouseTime($houseid,$lock=false){
        $field='houseid,checkin_time,checkout_time';
//        max(score)
        $where['houseid'] = $houseid;
        if($lock){
            $this->lock(true);
        }else{
            $this->lock(false);
        }
        $orderTime = $this->where($where)->field($field)->select();
        foreach ($orderTime as $k=>$v){
            $a[] = dateList($orderTime[$k]['checkin_time'],$orderTime[$k]['checkout_time']);
        }
        $n = count($a);
        $b = array();
        for ($i = 0;$i<$n;$i++){
            $num = count($a[$i]);
            unset($a[$i][$num-1]);
            $a[$i]['houseid'] = $houseid;
        }

        for ($i = 0;$i<1;$i++){
            for($j=$n-1;$j>=0;$j--){
                if($a[$j]['houseid'] == $a[$i]['houseid'] ){
                    $b = array_merge($b,$a[$j]);
                }
            }
            unset($b['houseid']);
        }
        return $b;
    }
}