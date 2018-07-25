<?php
/**
 * house(房源管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class HouseController extends AdminbaseController {

    protected $house_model;
    protected $housesource_model;
    protected $housetype_model;
    protected $housedetail_model;
    protected $housephoto_model;
    protected $houseorder_model;
    protected $city_model;
//    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->house_model = D("Common/House");
        $this->housesource_model = D("Common/Housesource");
        $this->housetype_model = D("Common/Housetype");
        $this->housedetail_model = D("Common/Housedetail");
        $this->housephoto_model = D("Common/Housephoto");
        $this->houseorder_model = D("Common/Houseorder");
        $this->city_model = D("Common/City");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台房源列表
    public function index() {
        $keyword = I('request.keyword');
        $field = 'as_house.houseid,as_house.housename,as_house.typeid,as_housedetail.price,as_house.houseposition,as_house.housecity,as_house.online,as_house.isorder
        ,as_housedetail.discount,as_housedetail.specialprice,as_housedetail.isdiscount,as_housedetail.isspecial';
        if($keyword){
            $where['housename']  = array('like','%'.$keyword.'%');
        }
        if(I('request.housetype')){
            $where['typeid']  = I('request.housetype');
        }
        if(I('request.isorder')){
            $where['isorder']  = I('request.isorder');
        }
        if(I('request.online')){
            $where['online']  = I('request.online');
        }
//        //$where['_logic'] = 'OR';
        $count=$this->house_model->where($where)->count();
        $page = $this->page($count, 5);
        $this->house_model->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSEDETAIL__.houseid = __HOUSE__.houseid');
        $this->house_model->where($where);
        $this->house_model->limit($page->firstRow , $page->listRows);
        $house = $this->house_model->order('as_house.houseid desc')->field($field)->select();
        $housetype = $this->housetype_model->select();
        foreach($housetype as $v){
            $type[$v['id']] = $v['housetype'];
        }
        foreach ($house as $k=>$v){
            $house[$k]['typename'] = $type[$house[$k]['typeid']];
        }
        $this->assign('housetype',$housetype);
        $this->assign('house',$house);
        $this->assign("page", $page->show('Admin'));
        $this->assign('typeid',$where['typeid']);
        $this->assign('isorder',$where['isorder']);
        $this->assign('online',$where['online']);
        $this->assign('keyword',$keyword);
        $this->display();
    }


    // 后台房源标签添加
    public function add() {
        $housetag = $this->housesource_model->where(array('state'=> 1))->field('id,typename')->select();
        $housetype = $this->housetype_model->select();
        $provinces = $this->city_model->select();
        $this->assign('housetag',$housetag);
        $this->assign('housetype',$housetype);
        $this->assign('provinces',$provinces);
    	$this->display();
    }

    public function upload_license(){
        $config = array(
            'maxSize'    =>    314572800,
            'rootPath'   =>    './uploads/license/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg','doc','docx','pdf','zip','txt','tif'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );
        $upload = new \Think\Upload($config);// 实例化上传类
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $upload->getError();
        }else{// 上传成功
            return $info;
        }
    }
    public function upload() {
        $config = array(
            'maxSize'    =>    314572800,
            'rootPath'   =>    './uploads/house/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg','doc','docx','pdf','zip','txt','tif'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );
        $upload = new \Think\Upload($config);// 实例化上传类
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $failReason = $upload->getError();
            $msg = array(
//                'filename'=> $file['name'],
                'message'=> $failReason,
                'code'=> -1,
                'status'=> 0,
            );
//            //将错误信息写入到.TXT文件中
//            $failinfos = $_FILES['file']['name'].'------'.$failReason.'------'.date('Y-m-d H:i:s',time());
//            $filepath = 'D:\/wamp\/'.__ROOT__.'/uploads/log/filefail.txt';
//            chmod($filepath,0644);
//            file_put_contents($filepath, $failinfos.PHP_EOL, FILE_APPEND);
            $this->error($msg);
        }else{// 上传成功
            $attachment['photoname'] = $info['file']['name'];
            $attachment['savename'] = $info['file']['savename'];
            $attachment['photopath'] = $info['file']['savepath'];
            $attachment['createtime'] = time();
            //创建附件数据
            $this->housephoto_model->create($attachment);
            if($this->housephoto_model->create()){
                $result = $this->housephoto_model->add(); // 写入数据到数据库
            }else{
                $result = $this->housephoto_model->add($attachment);
            }
            if($result){
                // 如果主键是自动增长型 成功后返回值就是最新插入的值
                $insertId = $result;
            }
            $msg = array('message'=>'success','code'=>200,'aid'=>$insertId,'status'=>1);
            return $this->ajaxReturn($msg,'json');
        }

    }

    // 后台房源添加提交
    public function add_post() {
    	if (IS_POST) {
    		$this->formData('add');
    	}
    }

    /**
     * 当前时间之后的两个月的订单详情
    */
    public function orderlist(){
        $houseid = I('get.houseid',0,'intval');
        $where['as_houseorder.houseid'] = intval($houseid);
        $field = 'as_houseorder.houseid,as_houseorder.housename,as_houseorder.checkin_time,as_houseorder.checkout_time,as_houseorder.mid
                   ,as_member.memberphone';
        $this->houseorder_model->join("LEFT JOIN __MEMBER__ ON __HOUSEORDER__.mid = __MEMBER__.mid");
        $this->houseorder_model->where($where);
        $this->houseorder_model->field($field);
        $order = $this->houseorder_model->select();
        $zeroTimestamp = strtotime(date('Y-m-d',time()));
        foreach ($order  as $or=>$v){
            $order[$or]['orderdate'] = $this->dateList($order[$or]['checkin_time'],$order[$or]['checkout_time']);
            $count = count($order[$or]['orderdate']);
            unset($order[$or]['orderdate'][$count-1]);
            for ($i=0;$i<$count-1;$i++){
                //预定时间小于当天凌晨时间则不记录
                if(strtotime($order[$or]['orderdate'][$i]) > $zeroTimestamp || strtotime($order[$or]['orderdate'][$i]) == $zeroTimestamp){
                    $orderDate[$order[$or]['orderdate'][$i]] = $order[$or];
                }
            }
        }

        //重新整理$orderdate数据
        foreach ($orderDate as $date=>$v){
            $reorder[$date]['date'] = $date;
            $reorder[$date]['memberphone'] = $orderDate[$date]['memberphone'] ;
            $reorder[$date]['isorder'] = 1 ;
        }

        //2个月之后的时间戳
        $afterTwoMonths = strtotime('+2 months');

        $currentTime = time();
        $twoMonthsDate= $this->dateList($currentTime,$afterTwoMonths);
        for($i=0;$i<count($twoMonthsDate);$i++){
            $res[$twoMonthsDate[$i]]['date'] = $twoMonthsDate[$i];
            $res[$twoMonthsDate[$i]]['memberphone'] = '';
            $res[$twoMonthsDate[$i]]['isorder'] = 0;
        }
        $res = array_merge($res,$reorder);
        $housename = $this->house_model->where(array('houseid'=>$houseid))->field('housename')->find();
        $this->assign('orderlist',array_values($res));
        $this->assign('housename',$housename['housename']);
        $this->display();
    }
    public function delete_attach(){
        $error = array();
        $allids = I('get.allids');
        $allids = explode(',',$allids);
        $undeleids = I('get.undeleids');
        $undeleids = explode(',',$undeleids);
        $delids = array_diff($allids,$undeleids);
        //删除数据库数据1.删除附件表2,修改新闻表关联的附件id,3.删除上传目录的文件
        if(!empty($delids)){
            $map['id']  = array('IN',$delids);
            //1.首先查找所删id的附件名称
            $attachInfo = $this->attachment_model->where($map)->select();
//            echo $this->attachment_model->getLastSql();

            //2删除数据库信息
            $attach = $this->attachment_model->where($map)->delete();
            if($attach){
                //3.删除服务器存在的文件
                $dir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/';
                foreach ($attachInfo as $k => $v ){
                    $delfile = $dir.$v['file_path'].$v['save_name'];
                    if(is_file($delfile)){
                        if(!unlink($delfile)){
                            $error['msg'] = '删除文件出错!';
                            $error['code'] = '-1';
                        }else {
                            $error['msg'] = '文件删除成功!';
                            $error['code'] = '0';
                        }
                    }else{
                        $error['msg'] = '文件不存在!';
                        $error['code'] = '-2';
                    }
                }
            }else{
                $error['msg'] = '删除失败!';
                $error['code'] = '-3';
            }

        }
        echo json_encode($error);
    }

    // 后台菜单删除
    public function delete() {
        $id = I("get.id",0,'intval');
        $type = I("get.type",0,'intval');
        if($type == 1){//删除房源照片
            $where['photoid'] = $id;
            $info = $this->housephoto_model->where($where)->find();
            //开启事务
            $this->housephoto_model->startTrans();
            $photoid = $this->housephoto_model->where($where)->delete();
            if($photoid){
                $basedir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/house/';
                $fullpath = $basedir.$info['photopath'].$info['savename'];
                if(is_file($fullpath)){
                    if(!unlink($fullpath)){
                        $msg['msg'] = '删除文件出错!';
                        $msg['code'] = '-1';
                    }else {
                        $msg['msg'] = '删除成功!';
                        $msg['code'] = '0';
                        $msg['id'] = $id;
                        $msg['type'] = $type;
                        $this->housephoto_model->commit();
                    }
                }else{
                    $msg['msg'] = '文件不存在!';
                    $msg['code'] = '-2';
                    $this->housephoto_model->rollback();
                }
            }else{
                $msg['msg'] = '数据不存在!';
                $msg['code'] = '-3';
            }
        }
        if($type == 2){//删除证件
            $where['houseid'] = $id;
            $updata['houselicense'] = '';
            $info = $this->house_model->where($where)->field('houselicense')->find();
            $houseid = $this->house_model->where($where)->save($updata);
            if($houseid){
                $basedir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/license/';
                $fullpath = $basedir.$info['houselicense'];
                if(is_file($fullpath)){
                    if(!unlink($fullpath)){
                        $msg['msg'] = '删除文件出错!';
                        $msg['code'] = '-1';
                    }else {
                        $msg['msg'] = '删除成功!';
                        $msg['code'] = 0;
                        $msg['id'] = $id;
                        $msg['type'] = $type;
                    }
                }else{
                    $msg['msg'] = '文件不存在!';
                    $msg['code'] = '-2';
                }
            }else{
                $msg['msg'] = '数据不存在!';
                $msg['code'] = '-3';
            }
        }
        echo json_encode($msg);die;
    }

    // 后台房源管理编辑
    public function edit() {
        $id = I("get.houseid",0,'intval');
        $online = I("get.online",0,'intval');
        $where['as_house.houseid'] = $id;
        $this->house_model->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSEDETAIL__.houseid = __HOUSE__.houseid');
        $this->house_model->where($where);
        $house = $this->house_model->order('as_house.houseid desc')->field('*')->find();
        $photos = $this->housephoto_model->where(array('houseid'=>$id))->select();
        $housetype= $this->HouseType(array('id'=>$house['typeid']));
        $sourcetype= $this->HouseSourceType(array('id'=>$house['tagid']));
        $housecity= $this->HouseCity(array('id'=>$house['housetype']));
        foreach ($housetype as $k => $v){
            $house['housetype'] = $housetype[$k]['housetype'];
            $house['city'] = $housecity[$k]['cityname'];
            $house['sourcetype'] = $sourcetype[$k]['typename'];
        }

        foreach ($house as $k=>$v){
            $house['discount2'] = json_decode($house['discount'],true);
            $house['specialprice2'] = !empty($house['specialprice']) ? json_decode($house['specialprice'],true) : '';
        }


        $housetag = $this->housesource_model->where(array('state'=> 1))->field('id,typename')->select();
        $type = $this->housetype_model->select();
        $provinces = $this->city_model->select();

        //房源照片id
        $pids = $this->housephoto_model->where(array('houseid'=>$id))->getField('photoid',true);
        $aids = implode(',',$pids);
        $this->assign('housetag',$housetag);
        $this->assign('housetype',$type);
        $this->assign('housecity',$provinces);
        $this->assign('aids',$aids);

        $this->assign("data", $house);
        $this->assign("houseid", $id);
        $this->assign("online", $online);
        $this->assign("photos", $photos);
        $this->display();
    }
    
    // 后台轮播图编辑提交
    public function edit_post() {
    	if (IS_POST) {
    	    $id = I('post.id',0,'intval');
    	    $this->formData('save',$id);
    	}
    }

    //表单数据
    public function formData($action='add',$id=0){
        //house数据
        if ($this->house_model->create()!==false) {
            $house['housename'] = I('post.housename');
            $house['tagid'] = I('post.housetag');
            $house['houseintro'] = I('post.houseintro');
            $house['houseorder'] = I('post.houseorder');
            $house['housecity'] = I('post.housecity');
            $house['houseaddress'] = I('post.houseaddress');
            $house['houseposition'] = I('post.houseposition');
            $house['ownerID'] = I('post.ownerID');
            $house['ownername'] = I('post.ownername');
            $house['ownerphone'] = I('post.ownerphone');
            $house['houselicense'] = I('post.houselicense');
            $house['typeid'] = I('post.housetype');
            $house['createtime'] = time();
            $house['online'] = I('post.online');
            if($action == 'add'){
                $houseid = $this->house_model->add($house);
            }else{
                if($id){
                    $where['houseid'] = $id;
                    $uphouseid = $this->house_model->where($where)->save($house);
                }
            }
        } else {
            $this->error($this->house_model->getError());
        }


        //house_detail数据
        if ($this->housedetail_model->create()!==false && ($houseid || $uphouseid)) {
            $detail['houseid'] = $houseid ? $houseid : $id;
            $detail['bathroom'] = I('post.bathroom');
            $detail['mindays'] = I('post.mindays');
            $detail['maxmembers'] = I('post.maxmembers');
            $detail['housearea'] = I('post.housearea');
            $detail['bedtype'] = I('post.bedtype');
            $detail['starttime'] = strtotime(I('post.starttime'));
            $detail['endtime'] = strtotime(I('post.endtime'));
            $detail['cash'] = I('post.cash');
            $detail['price'] = I('post.price');
            //长租折扣
            $discount = I('post.discount');
            $reach = I('post.reach');
            foreach ($reach as $k=>$v){
                if($reach[$k]){
                    $longrent[] = array('days'=>$reach[$k],'discount'=>$discount[$k]);
                }
            }
            $detail['discount'] = json_encode($longrent);
            //特殊价格设定
            $sprent = I('post.specialrent');
            $sptime= I('post.specialtime');
            foreach ($sprent as $k=>$v){
                if(!empty($sprent[$k])){
                    $spprice[] = array('money'=>$sprent[$k],'time'=> strtotime($sptime[$k]));
                }
            }
            $detail['specialprice'] = json_encode($spprice);

            $detail['createtime'] = time();
            $detail['isdiscount'] = !empty($longrent) ? 1 : 0;
            $detail['isspecial'] = !empty($spprice) ? 1 : 0;

            if($action == 'add'){
                $housedetailid = $this->housedetail_model->add($detail);
            }else{
                if($id){
                    $where['houseid'] = $id ;
                    $count = $this->housedetail_model->where(array('houseid'=>$id))->count();
                    if($count){
                        $uphousedetailid = $this->housedetail_model->where($where)->save($detail);
                    }else{
                        $uphousedetailid = $this->housedetail_model->where($where)->add($detail);
                    }

//                    echo $this->housedetail_model->getLastSql();die;
                }
            }
        } else {
            $this->error($this->housedetail_model->getError());
        }

        //上传房源图片+权重(ajax上传图片)
        $aid = I('post.aids');
        $weights = I('post.weight');
        $aids = explode(',',$aid);
        $houseid = $action == 'add' ? $houseid : $id ;
        if ($houseid) {
            foreach($aids as $id => $v){
                $updata['houseid'] = $houseid;
                $updata['weight'] = $weights[$id];
                $photoids[] = $this->housephoto_model->where(array('photoid'=>$v))->save($updata);
            }
        } else {
            $this->error("房源图片添加失败！");
        }

        //上传许可证
        if($houseid){
            if($action == 'save' && I('post.oldlicense')){
                $updata['houselicense'] = I('post.oldlicense');
            }else{
                $license= $this->upload_license();
                $savepath = $license['ownerlicense']['savepath'];
                $savename = $license['ownerlicense']['savename'];
                $updata['houselicense'] = $savepath.$savename;
            }
            $licenseID = $this->house_model->where(array('houseid'=>$houseid))->save($updata);
        }else {
            $this->error("营业执照添加失败！");
        }
//echo 3;die;
        $houseid = $action == 'add' ? $houseid : $id ;
        if($action == 'add'){
            if($houseid){
                $this->success('添加成功',U('house/index'));
            }
        }
        if($action == 'save'){
            if($uphousedetailid && $uphouseid ){
                $this->success('更新成功',U('house/index'));
            }
        }

    }


    public function check_online(){
        $online = I('get.online');
        $houseid = I('get.houseid');
        $where['houseid'] = $houseid;
        $updata['online'] = $online;
        $onl = $this->house_model->where($where)->save($updata);
        $this->redirect('house/index');
    }
   public function check_name(){
        $typename = I('get.typename');
        $where['typename'] = $typename;
        $eff_num = $this->housesource_model->where($where)->count();
        echo $eff_num;
   }

   public function HouseType($where=array()){
        if(!empty($where)){
            $housetype = $this->housetype_model->where($where)->select();
        }else{
            $housetype = $this->housetype_model->select();
        }
        return $housetype;
   }

   public function HouseSourceType($where=array()){
        if(!empty($where)){
            $source = $this->housesource_model->where($where)->select();
        }else{
            $source = $this->housesource_model->select();
        }
        return $source;
   }

    public function HouseCity($where=array()){
        if(!empty($where)){
            $city = $this->city_model->where($where)->select();
        }else{
            $city = $this->city_model->select();
        }
        return $city;
    }

    public function dateList($starttime,$endtime){

        $days = (intval($endtime)- intval($starttime))/86400 + 1;
        for($i=0; $i<$days; $i++){
            $dates[] = date('Y-m-d', intval($starttime)+(86400*$i));
        }
        return $dates;
    }
}
