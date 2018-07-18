<?php
/**
 * slideshow(轮播图管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class SlideshowController extends AdminbaseController {

    protected $slideshow_model;
    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->slideshow_model = D("Common/Slideshow");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台轮播图列表
    public function index() {
        $host = $_SERVER['HTTP_HOST'];
        $keyword = I('request.keyword');
//        $downloadDir= http_type().$host.__ROOT__.'/uploads/slideshow/';

        $where['name']  = array('like','%'.$keyword.'%');
        $where['position']  = array('eq',1);
        //$where['_logic'] = 'OR';
        $count=$this->slideshow_model->where($where)->count();
        $page = $this->page($count, 5);

//        $this->slideshow_model->join('LEFT JOIN __ATTACHMENT__ ON __ATTACHMENT__.nid = __NEWS__.nid');
        $this->slideshow_model->where($where);
        $this->slideshow_model->limit($page->firstRow , $page->listRows);

        $slideshow = $this->slideshow_model->order('id desc')->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('slideshow',($slideshow));
        $this->assign('keyword',$keyword);
//        $this->assign('downloadDir',$downloadDir);
        $this->display();
    }


    // 后台菜单添加
    public function add() {
    	$this->display();
    }


    //文件上传
    public function upload(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './uploads/slideshow/'; // 设置附件上传根目录
        $upload->savePath  =     ''; // 设置附件上传（子）目录
        $upload->autoSub = true;
        $upload->subName = array('date','Ymd');
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $upload->getError();
        }else{// 上传成功
            return $info;
        }
    }
    // 后台轮播图添加提交
    public function add_post() {
    	if (IS_POST) {
    		if ($this->slideshow_model->create()!==false) {
    			    $files = $this->upload();
    			    $data['name'] = I('post.name');
    			    $data['filename'] = $files['filename']['name'];
    			    $data['savename'] = $files['filename']['savename'];
    			    $data['savepath'] = $files['filename']['savepath'];
    			    $data['link'] = I('post.link');
    			    $data['weight'] = I('post.weight');
    			    $data['createtime'] = time();
    			    $data['updatetime'] = time();
                    $this->slideshow_model->add(array(
                                                "name"=>I('post.name'),
                                                "filename"=>$files['filename']['name'],
                                                "savename"=>$files['filename']['savename'],
                                                "savepath"=>$files['filename']['savepath'],
                                                "link"=>I('post.link'),
                                                "weight"=>I('post.weight'),
                                                "createtime"=>time(),
                                                "updatetime"=>time())
                    );

    				$this->success("添加成功！", U('Slideshow/index'));

    		} else {
    			$this->error($this->slideshow_model->getError());
    		}}
    }


    // 后台菜单删除
    public function delete() {
        $id = I("get.id",0,'intval');
        $map['id']  = $id;
        $attach = $this->slideshow_model->where($map)->delete();
        if ($attach) {
            echo 1;
//            $this->success("删除成功！");
        } else {
            echo 0;
//            $this->error("删除失败！");
        }
    }

    // 后台轮播图编辑
    public function edit() {
        $id = I("get.id",0,'intval');
        $rs = $this->slideshow_model->where(array("id" => $id))->find();
        $this->assign("data", $rs);
        $this->display();
    }
    
    // 后台轮播图编辑提交
    public function edit_post() {
    	if (IS_POST) {
    	    $id = I('post.id',0,'intval');
    		if ($this->slideshow_model->create()!==false) {
    			if ($this->slideshow_model->save() !== false) {
    				$name=I("post.name");
    				$weight=I("post.weight");
    				$link=I("post.link");
    				$updatetime = time();

    				//photo
                    $files = $this->upload();
                    $filename = $files['filename']['name'];
                    $savename = $files['filename']['savename'];
                    $savepath = $files['filename']['savepath'];

    		        $this->slideshow_model->where(array('id'=>$id))->save(array(
    		            'name'=> $name,
    		            'weight'=> $weight,
    		            'link'=> $link,
    		            'updatetime'=> $updatetime,
    		            'filename'=> $filename,
    		            'savename'=> $savename,
    		            'savepath'=> $savepath,
                        )
                    );
    				$this->success("更新成功！",U('slideshow/index'));
    			} else {
    				$this->error("更新失败！");
    			}
    		} else {
    			$this->error($this->slideshow_model->getError());
    		}
    	}
    }




    



}
