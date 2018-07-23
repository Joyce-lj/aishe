<?php

/* * 
 * 菜单
 */
namespace Common\Model;
use Common\Model\CommonModel;
class HouseModel extends CommonModel {

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
     * 房屋详情
    */
    public function houseDetail($where=array(),$field='*',$orderby='',$limit=0,$perpage=20){
        $this->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSE__.houseid = __HOUSEDETAIL__.houseid');
        $this->join('LEFT JOIN __HOUSETYPE__ ON __HOUSETYPE__.id = __HOUSE__.typeid');
        $this->where($where);
        $this->limit($limit , $perpage);
        if($orderby){
            $house = $this->order($orderby)->field($field)->select();
        }else{
            $house = $this->field($field)->select();
        }
        return $house;
    }
}