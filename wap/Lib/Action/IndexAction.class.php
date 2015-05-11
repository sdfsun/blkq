<?php
class IndexAction extends Action
{

	public function index()
	{
		/*new list*/
		$news_list = M ( "page_catelog" )->where ( 'father="医院动态" and is_delt=0' )->order ( 'updatetime desc' )->limit ( 5 )->select ();
		$this->assign ( 'news_list', $news_list );

		/*doctor list*/
		$list = M('doctor')->order ( 'age' )->limit ( 4 )->select ();
		//dump($list);
		$this->assign ( 'list_d', $list ); // 赋值数据集

		$this->display();

	}


	public function newslist()
	{
		/*new list*/
		$news_list = M ( "page_catelog" )->where ( 'father="医院动态" and is_delt=0' )->order ( 'updatetime desc' )->limit ( 9 )->select ();
		$this->assign ( 'news_list', $news_list );
		$this->display();
	}


	public function newscontent()
	{
		/*news content*/
		$id = $_REQUEST['id'];
		$info = selectRow('page_catelog', $id);
		$c = stripslashes ( $info['content'] );
		$info['content'] = html_entity_decode ( $c );
		$this->assign('info',$info);
		//dump($info);
		$this->display();
	}


	public function doclist()
	{
		/*doctor list*/
		$list = M('doctor')->order ( 'age' )->select ();
		//dump($list);
		$this->assign ( 'list', $list ); // 赋值数据集
		$this->display();
	}


	public function doccontent()
	{
		/*get doctor info */
		$id = $_REQUEST['id'];
		$info = selectRow('doctor', $id);
		$this->assign('info',$info);
		$this->display();
	}

	public function appoint()
	{
		$this->display();
	}

	public function content()
	{
		$child = $_REQUEST ['child'];
		$father = $_REQUEST ['father'];
		if ($child == null or $father == null)
		{
			$this->show ( "呃，发生了一点小错误了呢~~" );
		}

		$info = M ( "page_catelog" )->where ( "father='" . $father . "' and child='" . $child . "' and is_delt=0" )->order ( 'updatetime desc' )->limit ( 1 )->find ();
		if ($info == null)
		{
			$this->show ( "呃，该栏目下还没有文章呢，等待管理员添加哟~~" );
		}

		$content = stripslashes ( $info ['content'] );
		$info ['content'] = html_entity_decode ( $content );

		$this->assign ( 'info', $info );
		/*read num + 1*/
		setInc ( 'page', $info ['id'], 'read_num', 1 );
		/* display */
		$this->display ();
	}

	public function map()
	{
		$info = mc_option ( 'map' );
		$content = stripslashes ( $info );
		$content = html_entity_decode ( $content );
		$this->assign ( 'info', $content );
		$this->display ();
	}

	public function liucheng()
	{
		$info = mc_option ( 'liucheng' );
		$content = stripslashes ( $info );
		$content = html_entity_decode ( $content );
		$this->assign ( 'info', $content );
		$this->display ();
	}


	/* the blow is interface for outside */
	//==============================================================

	/**
	 * chang pass by weixin
	 * Enter description here ...
	 * @param unknown_type $weixin_id
	 * @param unknown_type $new_pass
	 */
	public function change_pass111($weixin_id=null, $new_pass=null)
	{
		if (null ==$weixin_id || null == $new_pass) {
			$data['code']=0;
			$data['msg']="param is not set right";
			$this->ajaxReturn($data);
		}else
		{
			$id=M('doctor')->where('weixin_id="'.$weixin_id.'"')->getField('id');
			if (null == $id)
			{
				$data['code']=0;
				$data['msg']="the doctor is not exists";
				$this->ajaxReturn($data);
			}else{
				$pass=M('doctor')->where('weixin_id="'.$weixin_id.'"')->getField('password');
				if($pass==md5($new_pass))
				{
					$data['code']=0;
					$data['msg']="the new pass is same as old pass";
					$this->ajaxReturn($data);
				}else {
					$thedata['password']=md5($new_pass);
					$ok = updateRow('doctor', $id, $thedata);
					if ($ok)
					{
						/* success */
						$data['code']=1;
						$data['msg']="change password success";
						$this->ajaxReturn($data);
					}
					else {
						/* faild */
						$data['code']=0;
						$data['msg']="unknow error";
						$this->ajaxReturn($data);
					}
				}
			}
		}

	}


	public function get_order_info_old($weixin_id=null)
	{
		if (null ==$weixin_id ) {
			$data['code']=0;
			$data['msg']="param is not set right";
			$this->ajaxReturn($data);
		}else
		{
			$id=M('doctor')->where('weixin_id="'.$weixin_id.'"')->getField('id');
			if (null == $id)
			{
				$data['code']=0;
				$data['msg']="the doctor is not exists";
				$this->ajaxReturn($data);
			}else{
				/* get  last 3 days order */
				$current_time = date ( "Y-m-d" );
				$end_c_time = date('Y-m-d',strtotime('+3 day'));
				//dump($current_time.$end_c_time);
				$list=selectList("order", "doctor_id=".$id." and is_chuli =1 and order_time >= '".$current_time."' and order_time <='".$end_c_time."'", 'id desc');
				//dump($list);
				if (!$list) {
					/* send weixin */
					$content="您最近三天暂时没有预约";
					send_weixin($weixin_id, $content);

					/* if no order*/
					$data['code']=1;
					$data['msg']='no order';
					$this->ajaxReturn($data);
				}else {

					/* send weixin */
					$weixin_content = "【最近3天预约记录】\n\n";
					foreach ($list as $key=>$value)
					{
						$weixin_content=$weixin_content."------------\n预约号：".$value['id']."\n患者姓名：".$value['name']."\n电话：".$value['tel']."\n预约时间:".$value['order_time']."-".$value['order_time2']."\n预约类别:".$value['yuyue_type']."\n描述：".$value['desc']."\n\n";
					}
					$weixin_content=urlencode($weixin_content);
					send_weixin($weixin_id, $weixin_content);

					/* return data */
					$data['code']=1;
					$data['msg']='send weixin success';
					$this->ajaxReturn($data);
				}

			}
		}
	}

	public function day_appoint(){
		$obj = M ( 'doc' );
		$sql = "select name,tbid from tb_member where permission_id = 1";
		$list = $obj->query($sql);
		$this->assign ( 'list', $list ); // 赋值数据集
		$this->display();		
	}

	public function get_order_info($weixin_id=null, $datetime=null)
	{
		$zhushou_weixin = mc_option("admin_weixin_id");
		$yuanzhang_weixin = mc_option("yuanzhang_weixin_id");
		if (null ==$weixin_id ) {
			$data['code']=0;
			$data['msg']="param is not set right";
			$this->ajaxReturn($data);
		}else if($zhushou_weixin == $weixin_id || $yuanzhang_weixin == $weixin_id)
		{
			$list=selectList("order", "is_chuli =1 and order_time = '".$datetime."'", 'order_time desc, dottime asc',0);
			$data['code']=1;
			$data['msg']=$list;
			$this->ajaxReturn($data);
		}
		else
		{
			$id=M('doctor')->where('weixin_id="'.$weixin_id.'"')->getField('id');

			if (null == $id)
			{
				$data['code']=0;
				$data['msg']="the doctor is not exists";
				$this->ajaxReturn($data);
			}else{
				/* get  last 3 days order */
				if($datetime==null)
				{
					$datetime = date ( "Y-m-d" );
				}
				//$end_c_time = date('Y-m-d',strtotime('+3 day'));
				//dump($current_time.$end_c_time);
				$list=selectList("order", "doctor_id=".$id." and is_chuli =1 and order_time = '".$datetime."'", 'order_time desc, dottime asc',0);
				//dump($list);
				if (!$list) {
					/* send weixin */
					//$content="您暂时没有预约\n\n<a href='".U('index/day_appoint')."'>全部预约记录</a>";
					//send_weixin($weixin_id, $content);

					/* if no order*/
					$data['code']=1;
					$data['msg']='no order';
					$this->ajaxReturn($data);
				}else {

					/* send weixin */
					//$weixin_content = "【最近预约记录】\n";
					//foreach ($list as $key=>$value)
					//{
					//	$weixin_content=$weixin_content."预约号：".$value['id']."\n患者姓名：".$value['name']."\n电话：".$value['tel']."\n预约时间:".$value['order_time']."-".$value['order_time2']."\n预约类别:".$value['yuyue_type']."\n描述：".$value['desc']."\n\n";
					//}
					//$weixin_content =$weixin_content."<a href='http://www.baidu.com'>全部预约记录</a>";
					//$weixin_content=urlencode($weixin_content);
					//send_weixin($weixin_id, $weixin_content);

					/* return data */
					$data['code']=1;
					$data['msg']=$list;
					$this->ajaxReturn($data);
				}

			}
		}
	}


	public function get_doc_duty( $datetime=null,$doc_id = null)
	{
			$time1 = strtotime($datetime);
			$time2 = strtotime("2015-01-01");
			$days = ceil(($time1-$time2)/86400) - 1;
			
			$model = new Model();
			if($doc_id){
				//指定了某个医生
				$sql = "select name,tbid from tb_member where tbid = " . $doc_id;
			}else{
				$sql = "select name,tbid from tb_member where permission_id = 1";
			}
			$list = $model->query($sql);
			$sql1 = "SELECT * FROM `blkq_duty` where days = " . $days;
			$duty = $model->query($sql1);	
			for($i=0;$i<count($list);$i++){
				$list[$i]['am'] = "";
				$list[$i]['pm'] = "";
			}		
			for($i=0;$i<count($list);$i++){
				foreach($duty as $dt){
					if($list[$i]['tbid'] == $dt['mem_id']){
						$list[$i]['am'] = $dt["moring"];
						$list[$i]['pm'] = $dt["afternoon"];
					}
				}
			}
			
			$this->ajaxReturn($list);
	}
	
	public function get_duty_message( $datetime=null,$doc_id = null)
	{
		if($doc_id){
			$sql = "select * from blkq_order where order_time = '". $datetime ."' and doctor_id = '" . $doc_id ."' and isconfirm = 1";
		}else{
			$sql = "select * from blkq_order where order_time = '". $datetime ."' and isconfirm = 1";
		}
		$model = new Model();
		$list = $model->query($sql);
		$this->ajaxReturn($list);
	}
	

	public function  change_pass()
	{
		$tag = $_REQUEST['tag'];
		if($tag=="tag")
		{
			$tel = $_REQUEST['tel'];
			$old_pass = $_REQUEST['old_pass'];
			$new_pass = $_REQUEST['new_pass'];
			$sid = $_REQUEST['sid'];
			/* judge the old_pass is right? */
			$obj = new Model();
			//$doctor = M("tb_member")->where("tbid='".$sid."' and password='".sha1($old_pass)."'")->find();
			$doctor = $obj->query("select * from tb_member where tbid = '".$sid."' and password = '".sha1($old_pass)."'");
			if ($doctor[0]==null) {
				/* wrong pass or wrong name */
				$this->error("账号或密码错误");
			}
			else
			{
				/* update the pass */
				$data['password']=sha1($new_pass);
				//$ok = updateRow('doctor', $doctor['id'], $data);				
				$ok = $obj->query("update tb_member set password = '".$data['password']. "' where tbid = ".$sid);		
				$this->success("修改密码成功");			
			}

		}elseif (null==$tag){

			$this->display();
		}
	}

	/**
	 * 根据order id 获取预约的详细信息
	 * Enter description here ...
	 */
	public function get_order_info_by_id()
	{
		$order_id = $_REQUEST['order_id'];
		if (null == $order_id) {
			$data['code']=0;
			$data['msg']='param is wrong.';
			$this->ajaxReturn($data);
		}
		else
		{
			$list = selectRow('order', $order_id);
			if (null == $list) {
				$data['code']=0;
				$data['msg']='no this order.';
				$this->ajaxReturn($data);
			}
			else
			{
				$data['code']=1;
				$data['msg']=$list;
				$this->ajaxReturn($data);
			}
		}
	}
	

}