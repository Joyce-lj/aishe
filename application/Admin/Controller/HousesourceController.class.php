<?php
/**
 * slideshow(轮播图管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class HousesourceController extends AdminbaseController {

    protected $housesource_model;
//    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->housesource_model = D("Common/Housesource");
//        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台房源列表
    public function index() {
        $keyword = I('request.keyword');
        $where['typename']  = array('like','%'.$keyword.'%');
        $where['state']  = array('eq',1);
        //$where['_logic'] = 'OR';
        $count=$this->housesource_model->where($where)->count();
        $page = $this->page($count, 5);
        $this->housesource_model->where($where);
        $this->housesource_model->limit($page->firstRow , $page->listRows);

        $housesource = $this->housesource_model->order('id desc')->select();

        $this->assign("page", $page->show('Admin'));
        $this->assign('housesource',($housesource));
        $this->assign('keyword',$keyword);
//        $this->assign('downloadDir',$downloadDir);
        $this->display();
    }


    // 后台房源标签添加
    public function add() {
    	$this->display();
    }

    // 后台房源添加提交
    public function add_post() {
    	if (IS_POST) {
    		if ($this->housesource_model->create()!==false) {
    			    $data['typename'] = I('post.typename');
    			    $data['createtime'] = time();
    			    $data['updatetime'] = time();
                    $this->housesource_model->add($data);
    				$this->success("添加成功！", U('housesource/index'));
    		} else {
    			$this->error($this->housesource_model->getError());
    		}}
    }

    // 后台菜单删除
    public function delete() {
        $id = I("get.id",0,'intval');
        $map['id']  = $id;
        $attach = $this->housesource_model->where($map)->delete();
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
        $rs = $this->housesource_model->where(array("id" => $id))->find();
        $this->assign("data", $rs);
        $this->display();
    }
    
    // 后台轮播图编辑提交
    public function edit_post() {
    	if (IS_POST) {
    	    $id = I('post.id',0,'intval');
    		if ($this->housesource_model->create()!==false) {
    			if ($this->housesource_model->save() !== false) {
    				$typename=I("post.typename");
                    $updatetime = time();
    		        $this->housesource_model->where(array('id'=>$id))->save(array(
    		            'typename'=> $typename,
    		            'updatetime'=> $updatetime,
                        )
                    );
    				$this->success("更新成功！",U('housesource/index'));
    			} else {
    				$this->error("更新失败！");
    			}
    		} else {
    			$this->error($this->housesource_model->getError());
    		}
    	}
    }

   public function check_name(){
        $typename = I('get.typename');
        $where['typename'] = $typename;
        $eff_num = $this->housesource_model->where($where)->count();
        echo $eff_num;
   }
    



}
