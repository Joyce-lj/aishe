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

}