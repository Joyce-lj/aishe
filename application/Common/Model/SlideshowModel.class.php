<?php

/* * 
 * 菜单
 */
namespace Common\Model;
use Common\Model\CommonModel;
class SlideshowModel extends CommonModel {

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
     * 轮播图列表(默认5张)
    */
    public function getSlideshow($where=array(),$limit=5,$field='*'){
        $res = $this->where($where)->field($field)->limit($limit)->select();
        $uploadDir = 'http://192.168.0.105'.__ROOT__.'/uploads/slideshow/';
        foreach ($res as $b=>$v){
            $res[$b]['banner'] = $uploadDir.$res[$b]['savepath'].$res[$b]['savename'];
        }
        return $res;
    }
}