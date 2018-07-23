<?php

/* * 
 * 菜单
 */
namespace Common\Model;
use Common\Model\CommonModel;
class MemberCouponModel extends CommonModel {

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
     * 优惠券列表
     * @param $field(查找的字段) , $where(查询条件)
     * @return array(二维数组)
    */
    public function CouponList($field='*',$where = array()){
        $res = $this->where($where)->field($field)->select();
        return $res;
    }

    /**
     * 不同状态的优惠券数量
     * @param $field(查找的字段) ,,$where(查询条件)
     * @return array
     */
    public function getCouponcountByState($where = array()){
        $res = $this->where($where)->count();
        return $res;
    }

    /**
     * 优惠券
     * @param $field(查找的字段) ,,$where(查询条件)
     * @return array(一维数组)
     */
    public function getCouponByCondition($field='cid',$where = array()){
        $res = $this->where($where)->getField($field,true);
        return $res;
    }

    /**
     * 分组查找优惠券信息
     * @param $sz,(1=一维数组,2=二维数组),$field(查找的字段) ,$where(查询条件)
     * @return array(数组)
     */
    public function getCouponGroupByConds($sz=2,$field='cid',$groupby='cid',$where=array()){
        if($sz == 1){
            $res = $this->where($where)->group($groupby)->getField($field,true);
        }
        if($sz == 2){
            $res = $this->field($field)->where($where)->group($groupby)->select();
        }
        return $res;
    }

    /**
     * 两表联查
    */
    public function getUsercoupon($field='*',$where,$limit=0,$perpage=20){
        $this->join('RIGHT JOIN __COUPON__ ON __COUPON__.cid = __MEMBER_COUPON__.cid');
        $this->where($where);
        $this->limit($limit,$perpage);
        $this->field($field);
        $res = $this->select();
        return $res;
    }
}