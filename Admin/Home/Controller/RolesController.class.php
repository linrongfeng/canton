<?php
namespace Home\Controller;
use Think\Controller\RestController;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Credentials:true'); 
header("Content-Type: application/json;charset=utf-8");

/**
* 角色-权限控制器
*/
class RolesController extends RestController{

	public function _initialize()
    {
        // 没登录
        $auth = new \Think\Product\PAuth();
        $key = I('key');
        $uid = I('user_id');
        $uids = $auth->checkKey($uid, $key);
        if(!$uids){
            $this->response(['status' => 1012,'msg' => '您还没登陆或登陆信息已过期'],'json');
        }
        // 读取访问的地址
        $url = CONTROLLER_NAME . '/' . ACTION_NAME;
        if(!$auth->check($url , $uids)){
            $this->response(['status' => 1011,'msg' => '抱歉，权限不足'],'json');
        }
    }

	//获取角色列表
	public function getRoles(){
		$vague = I('post.vague');
		$id = I('post.id');
		if(!empty($vague)){
			$where['_string'] = '(name like "%'.$vague.'%")';
		}
		if(!empty($id)){
			$where['id'] = $id;
		}
		$role = M('auth_role');
		$role_org = M('auth_role_org');
		$org = M('auth_org');
		$role->startTrans();
		$sql = $role->where($where)->select();
		foreach ($sql as $key => $value) {
			$query = $role_org->field("org_id")->where("role_id=%d",array($value['id']))->select();
			foreach ($query as $keys => $values) {
				$arr[] = $values['org_id'];
			}
			$ids = implode(',',$arr);
			if(!empty($ids)){
				$get = $org->field("id,name")->where("id in (".$ids.")")->select();
				foreach ($get as $ks => $val) {
					$array[$ks]['id'] = $val['id'];
					$array[$ks]['name'] = $val['name'];
					$names[] = $val['name'];
				}
			}
			$datas[$key] = $value;
			$datas[$key]['org'] = $array;
			$datas[$key]['org_name'] = implode(',',$names);
			$arr = array();
			$array = array();
			$ids = null;
			$names = array();
		}
		$role->commit();
		if($sql){
			$data['status'] = 100;
			$data['value'] = $datas;
		}else{
			$data['status'] = 101;
			$data['msg'] = "没有数据";
		}
		$this->response($data,'json');
	}

	//修改角色信息
	public function updateRoles(){
		$name = I('post.name');
		$remark = I('post.remark');
		$enabled = I('post.enabled');
		$org_ids = I('post.org_ids');
		$id = I('post.role_id');
		if(empty($name)){
			$arr['status'] = 102;
			$arr['msg'] = "角色名称不能为空";
			$this->response($arr,'json');
			exit();
		}
		if(empty($enabled) && $enabled!=0 && $enabled !='0'){
			$arr['status'] = 102;
			$arr['msg'] = "角色状态不能为空";
			$this->response($arr,'json');
			exit();
		}
		$role = M('auth_role');
		$org = M('auth_role_org');
		$role->startTrans();
		$data['name'] = $name;
		$data['remark'] = $remark;
		$data['enabled'] = $enabled;
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$sql = $role->data($data)->where("id=%d",array($id))->save();
		$query = $org->where("role_id=%d",array($id))->delete();
		$das['role_id'] = $id;
		foreach ($org_ids as $key => $value) {
			if(!empty($value['id'])){
				$das['org_id'] = $value['id'];
				$das['created_time'] = date('Y-m-d H:i:s',time());
				$query = $org->data($das)->add();
				if(empty($query)){
					$role->rollback();
					$arr['status'] = 101;
					$arr['msg'] = "修改失败";
					$this->response($arr,'json');
					exit();
				}
			}
			
		}
		if($sql !== 'flase'){
			$role->commit();
			$arr['status'] = 100;
		}else{
			$role->rollback();
			$arr['status'] = 101;
			$arr['msg'] = "修改失败";
		}
		$this->response($arr,'json');
	}

	//添加角色
	public function addRoles(){
		$name = I('post.name');
		$remark = I('post.remark');
		$enabled = I('post.enabled');
		$org_ids = I('post.org_id');
 		if(empty($name)){
			$arr['status'] = 102;
			$arr['msg'] = "角色名称不能为空";
			$this->response($arr,'json');
			exit();
		}
		if(empty($enabled)){
			$arr['status'] = 102;
			$arr['msg'] = "角色状态不能为空";
			$this->response($arr,'json');
			exit();
		}
		$creator_id = I('post.creator_id');
		if(empty($creator_id)){
			$arr['status'] = 1012;
			$this->response($arr,'json');
			exit();
		}
		$role = M('auth_role');
		$org = M('auth_role_org');
		$role->startTrans();
		$data['name'] = $name;
		$data['remark'] = $remark;
		$data['enabled'] = $enabled;
		$data['creator_id'] = $creator_id;
		$data['created_time'] = date('Y-m-d H:i:s',time());
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$sql = $role->data($data)->add();
		$das['role_id'] = $sql;
		$das['creator_id'] = $creator_id;
		foreach ($org_ids as $key => $value) {
			$das['org_id'] = $value;
			$das['created_time'] = date('Y-m-d H:i:s',time());
			$query = $org->data($das)->add();
			if(empty($query)){
				$role->rollback();
				$arr['status'] = 101;
				$arr['msg'] = "修改失败";
				$this->response($arr,'json');
				exit();
			}
		}
		if($sql){
			$role->commit();
			$arr['status'] = 100;
		}else{
			$role->rollback();
			$arr['status'] = 101;
			$arr['msg'] = "修改失败";
		}
		$this->response($arr,'json');
	}

	//删除角色
	public function delRoles(){
		$id = I('post.role_id');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择角色";
			$this->response($arr,'json');
			exit();
		}
		$role = M('auth_role');
		$role_user = M('auth_role_user');
		$role_org = M('auth_role_org');
		$query = $role_user->field("id")->where("role_id=%d",array($id))->find();
		if(empty($query['id'])){
			$sql = $role->where("id=%d",array($id))->delete();
			$sql = $role_org->where("role_id=%d",array($id))->delete();
			if($sql !== 'flase'){
				$arr['status'] = 100;
			}else{
				$arr['status'] = 101;
				$arr['msg'] = "删除失败";
			}
		}else{
			$arr['status'] = 103;
			$arr['msg'] = "角色下有关联用户";
		}
		$this->response($arr,'json');
	}

	//读取角色的权限
	public function getRule2Role(){
		$role_id = I('post.role_id');
		if(empty($role_id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择角色";
			$this->response($arr,'json');
			exit();
		}
		// 列出所拥有的权限
		$role = M('auth_role');
		$sql = $role->field("permissions")->where("id=%d",array($role_id))->find();
		$arr = explode(",",$sql['permissions']);
		// 列出所有权限 
		$rule = M('auth_rule');
		$query = $rule->field("id,name,p_id")->select();
		foreach ($query as $key => $value) {
			$data[$key] = $value;
			if(in_array($value['id'], $arr)){
				$data[$key]['have'] = (boolean)true;
			}else{
				$data[$key]['have'] = (boolean)'';
			}
		}
		$datas = pre($data,0);
		$array['status'] = 100;
		$array['value'] = $datas;
		$this->response($array,'json');
	}

	//给角色分配权限
	public function allotRule2Role(){
		$role_id = I('post.role_id');
		$rule_ids = I('post.rule_ids');
		$role = M('auth_role');
		$data['permissions'] = implode(",",$rule_ids);
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$sql = $role->data($data)->where("id=%d",array($role_id))->save();
		if($sql !== 'flase'){
			$arr['status'] = 100;
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "分配失败";
		}
		$this->response($arr,'json');
	}
}
