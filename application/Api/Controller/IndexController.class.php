<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class IndexController extends AppframeController{
	
	protected $house_model;
	protected $housetype_model;
	protected $housephoto_model;
	protected $housedetail_model;
	protected $slideshow_model;
    protected $city_model;
	protected $perpage = 20;
	
	public function _initialize() {
		parent::_initialize();
		$this->house_model=D("Common/House");
		$this->housetype_model=D("Common/Housetype");
		$this->housephoto_model=D("Common/Housephoto");
		$this->housephoto_model=D("Common/Housephoto");
		$this->slideshow_model=D("Common/Slideshow");
		$this->city_model=D("Common/City");
	}

	public function index(){
	    //单价,名称,两室一厅,图片

        $where = array();
        $cityid = I('get.cityid');
        $keyword = I('get.keyword');
        if($keyword){
            $where['housename'] = array('like','%'.$keyword.'%');
//            $where['housetype'] = array('like','%'.$keyword.'%');
            $where['price'] = array('like','%'.$keyword.'%');
            $where['_logic'] = 'OR';
            $map['_complex'] = $where;
        }
        if($cityid){
            $map['housecity'] = intval($cityid);
        }
        //房源状态:上线
        $map['online'] = 1;
        $field = 'as_house.houseid,as_house.housename,as_housedetail.price,as_house.tagid,as_house.housecity,as_housesource.typename,as_house.houseposition';
        //满足条件的个数
        $this->house_model->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSE__.houseid = __HOUSEDETAIL__.houseid');
        $this->house_model->join('LEFT JOIN __HOUSESOURCE__ ON __HOUSESOURCE__.id = __HOUSE__.tagid');
        $count=$this->house_model->where($map)->count();
//        echo $this->house_model->getLastSql();die;
        $perpage = I('request.perpage') ? I('request.perpage') : 20;
        $currentPage = I('request.page') ? I('request.page') : 1;
        $totalPages = ceil($count/$perpage);
        $limit = ($currentPage - 1)*$perpage;
        //house信息
        $this->house_model->join('LEFT JOIN __HOUSEDETAIL__ ON __HOUSE__.houseid = __HOUSEDETAIL__.houseid');
        $this->house_model->join('LEFT JOIN __HOUSESOURCE__ ON __HOUSESOURCE__.id = __HOUSE__.tagid');
        $this->house_model->where($map);
        $this->house_model->limit($limit , $perpage);
        $house = $this->house_model->order('as_house.houseid desc')->field($field)->select();
//echo $this->house_model->getLastSql();die;
        foreach ($house as $k=>$v){
            $cityids[] = $house[$k]['housecity'];
            $photoinfo =  $this->housephoto_model->getPhotoByHouseid($house[$k]['houseid'],$field = 'savename,photopath,weight',1);
            $photoinfo =  array_values($photoinfo);
            foreach ($photoinfo as $p=>$v){
                $uploadDir = 'http://192.168.0.105'.__ROOT__.'/data/upload/';
//                $house[$k]['housephoto'] = $uploadDir.$photoinfo[$p]['photopath'].$photoinfo[$p]['savename'];
                $house[$k]['housephoto'] = $uploadDir.$photoinfo[$p]['photopath'];
            }
        }

        $data['data']['house'] = $house;
        //page=1时加载banner和city
        if($currentPage == 1 && !empty($cityids)){
            $cityids = implode(',',$cityids);
            $city = $this->city_model->getCityByCityid($cityids,'id,cityname');
            $banner = $this->slideshow_model->getSlideshow();
            $data['data']['city'] = $city;
            $data['data']['banner'] = $banner;
        }
        $data['data']['perpage'] = intval($perpage);
        $data['data']['currentpage'] = $currentPage;
        $data['data']['totalpages'] = $totalPages;
        $this->ajaxData($data);
    }


}