<?php
namespace Api\Controller;

use Common\Controller\AppframeController;

class GuestbookController extends AppframeController{
	
	protected $guestbook_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->guestbook_model=D("Common/Guestbook");
	}
	
	// 留言提交
	public function addmsg(){
		if(!sp_check_verify_code()){
			$this->error("验证码错误！");
		}
		
		if (IS_POST) {
			if ($this->guestbook_model->create()!==false) {
				$result=$this->guestbook_model->add();
				if ($result!==false) {
					$this->success("留言成功！");
				} else {
					$this->error("留言失败！");
				}
			} else {
				$this->error($this->guestbook_model->getError());
			}
		}
		
	}

	public function test(){
//        /portal/lists/getCategoryPostLists
        $this->success("Hello API");
//	    echo json_encode($data = array('msg'=>'就分手就分手六块腹肌','code'=>9));
    }
}