<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class HousedetailController extends AppframeController{
	
	protected $house_model;
	protected $housetype_model;
	protected $housephoto_model;
	protected $housedetail_model;
	protected $houseorder_model;
    protected $city_model;
	protected $perpage = 20;
	
	public function _initialize() {
		parent::_initialize();
		$this->house_model=D("Common/House");
		$this->housetype_model=D("Common/Housetype");
		$this->housedetail_model=D("Common/Housedetail");
		$this->housephoto_model=D("Common/Housephoto");
        $this->houseorder_model=D("Common/Houseorder");
		$this->city_model=D("Common/City");
	}

	public function index(){
	    //单价,名称,两室一厅,图片,入住人数,折扣,地图坐标,具体位置,优惠
        $field = 'as_house.houseid,as_house.housename,as_housedetail.price,as_house.typeid,as_house.housecity,as_housedetail.maxmembers,
                  as_housedetail.bedtype,as_housedetail.housearea,as_housedetail.discount,as_housedetail.bathroom,as_housedetail.mindays,as_housedetail.cash,
                  as_housedetail.starttime,as_housedetail.endtime,
                  as_housetype.housetype,as_house.houseintro,as_house.houseorder,as_house.houseaddress,as_house.house_x,as_house.house_y';
        $where = array();
        $houseid = I('request.houseid');
        if($houseid){
            $where['as_house.houseid'] = intval($houseid);
        }
        $houseDetail = $this->house_model->houseDetail($where,$field);
        foreach ($houseDetail as $k=>$v){
            $discount = json_decode($v['discount'],true);
            $houseDetail[$k]['starttime'] = date('H:i',$v['starttime']);
            $houseDetail[$k]['endtime'] = date('H:i',$v['endtime']);
            $houseDetail[$k]['housediscount'] = $discount;
            $photoinfo =  $this->housephoto_model->getPhotoByHouseid($houseDetail[$k]['houseid'],$field = 'savename,photopath,weight');
            $photoinfo =  array_values($photoinfo);
            foreach ($photoinfo as $p=>$v){
                $uploadDir = 'http://192.168.0.105'.__ROOT__.'/uploads/house/';
                $houseDetail[$k]['housephoto'][] = $uploadDir.$photoinfo[$p]['photopath'].$photoinfo[$p]['savename'];
            }
        }
        $data['data']['housedetail'] = $houseDetail[0];
        $this->ajaxData($data);
    }


    /**
     *下单页面
     * 未使用
     */
    public function houseOrder(){
	    //考虑并发,两人同时下单
	    //isorder=1预定 0未预定
        $houseid = I('request.houseid');
        $isOrder = 1;
        $where = array('houseid'=>$houseid);
        $updata = array('isorder'=>$isOrder);
        if(empty($houseid)){
            $data['code'] = -2;
            $data['msg'] = '参数有误!';
            $this->ajaxData($data);
        }else{
            $state = $this->house_model->where(array('houseid'=>$houseid))->field('isorder')->find();
            if($state['isorder'] == 1){
                $data['code'] = -3;
                $data['msg'] = '该房屋已被预定,请选择其他房屋!';
                $this->ajaxData($data);
            }
        }
        if(!empty($where)){
            $res = $this->house_model->where($where)->save($updata);
        }
        if(!empty($res)){
            $this->ajaxData();
        }else{
            $data['code'] = -1;
            $data['msg'] = 'fail';
            $this->ajaxData($data);
        }
    }



    /**
     * 选择预定时间
    */
    public function pickOrderTime(){
        $houseid = I('request.houseid');
        $where['houseid'] = $houseid;
        $field = 'houseid,price,isdiscount,discount,starttime,endtime,specialprice';
        $data = $this->housedetail_model->where($where)->field($field)->select();
        foreach ($data as $k=>$v){
            $days = (intval($data[$k]['endtime'])- intval($data[$k]['starttime']))/86400 + 1;
            $data[$k]['starttime'] = date('Y-m-d',$data[$k]['starttime']);
            $data[$k]['endtime'] = date('Y-m-d',$data[$k]['endtime']);
            $data[$k]['housediscount'] = json_decode($data[$k]['discount'],true);
            //特殊价格
            $data[$k]['specialprice'] = json_decode($data[$k]['specialprice'],true);
            foreach ($data[$k]['specialprice'] as $sp => $v){
                $spdate = date('Y-m-d',$data[$k]['specialprice'][$sp]['time']);
                $spsplit = explode('-',$spdate);
                $specialprice[$spdate] = array(
                    'special'=>1,
                    'date'=> $spdate,
                    'year'=> $spsplit[0],
                    'month'=> $spsplit[1],
                    'day'=> $spsplit[2],
                    'price'=> $data[$k]['specialprice'][$sp]['money'],
                );
            }

            //所有可预约时间
            for($i=0; $i<$days; $i++){
                $dates[] = date('Y-m-d', strtotime($data[$k]['starttime'])+(86400*$i));
            }

            for($i=0;$i< count($dates); $i++){
                $dsplit = explode('-',$dates[$i]);
                $ordertime[$dates[$i]] = array(
                    'using'=>0,
                    'special'=> 0,
                    'date'=> $dates[$i],
                    'year'=> $dsplit[0],
                    'month'=> $dsplit[1],
                    'day'=> $dsplit[2],
                    'price'=> $data[$k]['price'],
                );
            }
            $ordertime = array_merge($ordertime,$specialprice);

            //对已预约到日期做标注
            $usingTime = $this->getOrderedHouseTime($houseid);
            foreach ($usingTime as $use => $v){
                $checkin = $usingTime[$use]['checkin_time'];
                $checkout = $usingTime[$use]['checkout_time'];
                $usingDate = $this->dateList($checkin,$checkout);
            }
            $usingCount = count($usingDate);
            unset($usingDate[$usingCount-1]);
            for($i=0;$i<count($usingDate);$i++){
                $dsplit = explode('-',$usingDate[$i]);
                $ordertime2[$usingDate[$i]] = array(
                    'using'=>1,
                    'special'=> 0,
                    'date'=> $usingDate[$i],
                    'year'=> $dsplit[0],
                    'month'=> $dsplit[1],
                    'day'=> $dsplit[2],
                    'price'=> $data[$k]['price'],
                );
            }
            $ordertime2 = array_merge($ordertime2,$specialprice);
            $finalDate = array_merge($ordertime,$ordertime2);
            //标注结束
            $data[$k]['ordertime'] = array_values($finalDate);
        }

        $data['data']['houseorder'] = $data;
        $this->ajaxData($data);

    }


    public function getOrderedHouseTime($houseid){
        $field='houseid,min(checkin_time) as checkin_time ,max(checkout_time) as checkout_time';
//        max(score)
        $where['houseid'] = $houseid;
        $orderTime = $this->houseorder_model->where($where)->field($field)->select();
        return $orderTime;
    }

    /**
     * 时间都是时间戳
    */
    public function dateList($starttime,$endtime){

        $days = (intval($endtime)- intval($starttime))/86400 + 1;
        for($i=0; $i<$days; $i++){
            $dates[] = date('Y-m-d', intval($starttime)+(86400*$i));
        }
        return $dates;
    }


}