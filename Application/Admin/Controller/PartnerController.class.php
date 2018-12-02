<?php 
	namespace Admin\Controller;
	use Think\Controller;
	class PartnerController extends Controller{
		public function index(){
			$this->display();
		}

		//列出所有合作商
		public function partnerList(){
			$id = session("id");
			if (!isset($id)) {
				redirect(U('User/index'));
			}

			$partnerData = M('platform')->select();
			 if (is_array($partnerData)&&!empty($partnerData)) {
		           $array = array(
		            'result' =>1 ,
		            'data' => $partnerData,
		             );
		           $this->ajaxReturn(json_encode($array),'json');
		          }else if (empty($partnerData)) {
		            $this->ajaxReturn("{'result':'3'}",'json');//没有返回数据
		          }else{
		            $this->ajaxReturn("{'result':'3'}",'json');
		          }
		}

		//添加合作商
		public function partnerCheck(){
			$id = session("id");
			if (!isset($id)) {
				redirect(U('User/index'));
			}

			$name=I("name");

			$where = array(
				'name' => $name,
				);
			$ret=M("platform")->where($where)->field('name')->find();
			if (is_array($ret)&&count($ret)>0) {
				//数据库中含有该合作商
				$this->ajaxReturn("{'result':'2'}",'json');
			}else{
				$pardata = M('platform');
				$data=array(
					'name'=>$name,
					);
				$pardata->add($data);
			}
			$this->ajaxReturn("{'result':'3'}",'json');
		}




		         



	}
 ?>