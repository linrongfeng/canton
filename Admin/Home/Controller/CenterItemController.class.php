<?php
namespace Home\Controller;
use Think\Controller\RestController;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Credentials:true');
header("Content-Type: application/json;charset=utf-8");
/**
* 词库项目控制器
*/
class CenterItemController extends RestController{
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
    
	//添加项目
	public function addCenterItem(){
		$state = I('post.state');
		$creator_id = I('post.creator_id');
		if(empty($creator_id)){
			$arr['status'] = 1012;
			$this->response($arr,'json');
			exit();
		}
		if($state == 'single'){
			$name = I('post.name');
			$enabled = I('post.enabled');
			$remark = I('post.remark');
			$data['name'] = $name;
			if(empty($enabled)){
				$enabled = 1;
			}
			if(empty($name)){
				$arr['status'] = 102;
				$arr['msg'] = "项目名称不能为空";
				$this->response($arr,'json');
				exit();
			}
			$data['enabled'] = $enabled;
			$data['remark'] = $remark;
			$data['creator_id'] = $creator_id;
			$data['created_time'] = date('Y-m-d H:i:s',time());
			$data['modified_time'] = date('Y-m-d H:i:s',time());
			$res = \Think\Product\CenterItem::AddCenterItem($state,$data);
			if($res == 1){
				$arr['status'] = 100;
			}else{
				$arr['status'] = 101;
				$arr['msg'] = "添加失败";
			}
		}elseif ($state == 'many') {
			//上传文件添加
			$xlsx = 'xlsx';
			$xls = 'xls';
    		if($_FILES){
    			$type = strtolower(substr(strrchr($_FILES['file']['name'], '.'), 1)); //获取文件类型
    			if($type == $xlsx || $type == $xls){
					$size = (($_FILES['file']['size'])/1024)/1024; // mb兆
					if($size < 3){
						if($type == 'xls'){
               			    $types = 'Excel5';
               			}elseif($type == 'xlsx'){
               			    $types = 'Excel2007';
               			}
						$file_name = $this->file."centeritem_".date("Ymd-His", time()). '.' .$type;
						move_uploaded_file($_FILES['file']['tmp_name'], $file_name);
						$array=read_excel($file_name,$types,0);
						if(empty($array)){
							$arr['status'] = 101;
							$arr['msg'] = "没有数据";
							$this->response($arr,'json'); 
							exit();
						}else{
							foreach ($array as $key => $value) {
								list($name,$enabled,$remark)=$value;
								$data[$key]['name'] = $name;
								$data[$key]['enabled'] = !empty($enabled)?$enabled:1;
								$data[$key]['remark'] = $remark;
			 					$data[$key]['creator_id'] = $creator_id;
			 					$data[$key]['created_time'] = date('Y-m-d H:i:s',time());
			 					$data[$key]['modified_time'] = date('Y-m-d H:i:s',time());
							}
						}
						$res = \Think\Product\CenterItem::AddCenterItem($state,$data);
						unlink($file_name);
						$arr['status'] = 100;
						$arr['value'] = $res;
					}else{
						$data['status'] = 104;
        	            $data['msg']    = '文件大小超负荷';
						$this->response($data,'json');exit();
					}
    			}else{
    				$data['status'] = 103;
        	        $data['msg']    = '文件格式不被允许';
    				$this->response($data,'json');exit();
    			}
    		}else{
    			$data['status'] = 105;
        	    $data['msg']    = '没有文件被上传';
    			$this->response($data,'json');exit();
    		}
		}else {
			$arr['status'] = 102;
			$arr['msg'] = "未定义的操作";
		}
		
		$this->response($arr,'json');
	}

	//修改词库项目
	public function updaCenterItem(){
		$id = I('post.id');
		$name = I('post.name');
		$enabled = I('post.enabled');
		$remark = I('post.remark');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		if(empty($name)){
			$arr['status'] = 102;
			$arr['msg'] = "项目名称不能为空";
			$this->response($arr,'json');
			exit();
		}
		if(empty($enabled)){
			$enabled = 1;
		}
		$data['name'] = $name;
		$data['enabled'] = $enabled;
		$data['remark'] = $remark;
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$res = \Think\Product\CenterItem::UpdaCenterItem($id,$data);
		if($res == 1){
			$arr['status'] = 100;
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "修改失败";
		}
		$this->response($arr,'json');
	}

	//获取词库项目列表
	public function getAllCenterItem(){
		$enabled = I('post.enabled');
		$vague = I('post.vague');
		$pages = I('post.pages');
		$num = I('post.num');
		if(empty($pages)){
			$pages = 1;
		}
		if(empty($num)){
			$num = 25;
		}
		$res = \Think\Product\CenterItem::GetAllCenterItem($enabled,$vague,$pages,$num);
		if($res == -1){
			$arr['status'] = 101;
			$arr['msg'] = "没有数据";
		}else{
			foreach ($res['value'] as $key => $value) {
				$res['value'][$key]['enabled'] = (int)$res['value'][$key]['enabled'];
			}
			$arr['status'] = 100;
			$arr['count'] = $res['count'];
			$arr['pages'] = $res['pages'];
			$arr['nowpages'] = $res['nowpages'];
			$arr['value'] = $res['value'];
		}
		$this->response($arr,'json'); 
	}

	//获取词库项目信息
	public function getCenterItemInfo(){
		$id = I('post.id');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		$res = \Think\Product\CenterItem::GetCenterItemInfo($id);
		if($res == -1){
			$arr['status'] = 101;
			$arr['msg'] = "没有数据";
		}else{
			$arr['status'] = 100;
			$arr['value'][] = $res;
		}
		$this->response($arr,'json');
	}

	//删除词库项目
	public function delCenterItem(){
		$id = I('post.id');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		$res = \Think\Product\CenterItem::DelCenterItem($id);
		if($res == 1){
			$arr['status'] = 100;
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "删除失败";
		}
		$this->response($arr,'json');
	}

	//添加词库内容
	public function addCenterItemValue(){
		$id = I('post.id');
		$state = I('post.state');
		$creator_id = I('post.creator_id');
		if(empty($creator_id)){
			$arr['status'] = 1012;
			$this->response($arr,'json');
			exit();
		}
		if($state == 'single'){
			$value = I('post.text');
			if (empty($value)) {
				$arr['status'] = 102;
				$arr['msg'] = "词库内容不能为空";
				$this->response($arr,'json');
				exit();
			}
			$data['item_id'] = $id;
			$data['value'] = __str_replace($value);
			$data['creator_id'] = $creator_id;
			$data['created_time'] = date('Y-m-d H:i:s',time());
			$data['modified_time'] = date('Y-m-d H:i:s',time());
			$res = \Think\Product\CenterItem::AddCenterItemValue($state,$data);
			if($res == 1){
				$arr['status'] = 100;
			}else{
				$arr['status'] = 101;
				$arr['msg'] = "添加失败";
			}
		}elseif ($state == 'many') {
			//上传文件添加
			$xlsx = 'xlsx';
			$xls = 'xls';
    		if($_FILES){
    			$type = strtolower(substr(strrchr($_FILES['file']['name'], '.'), 1)); //获取文件类型
    			if($type == $xlsx || $type == $xls){
					$size = (($_FILES['file']['size'])/1024)/1024; // mb兆
					if($size < 3){
						if($type == 'xls'){
               			    $types = 'Excel5';
               			}elseif($type == 'xlsx'){
               			    $types = 'Excel2007';
               			}
						$file_name = $this->file."centervalue_".date("Ymd-His", time()). '.' .$type;
						move_uploaded_file($_FILES['file']['tmp_name'], $file_name);
						$array=read_excel($file_name,$types,0);
						if(empty($array)){
							$arr['status'] = 101;
							$arr['msg'] = "没有数据";
							$this->response($arr,'json'); 
							exit();
						}else{
							foreach ($array as $key => $value) {
								list($text)=$value;
								$data[$key]['item_id'] = $id;
								$data[$key]['value'] = __str_replace($text);
			 					$data[$key]['creator_id'] = $creator_id;
			 					$data[$key]['created_time'] = date('Y-m-d H:i:s',time());
			 					$data[$key]['modified_time'] = date('Y-m-d H:i:s',time());
							}
						}
						$res = \Think\Product\CenterItem::AddCenterItemValue($state,$data);
						unlink($file_name);
						$arr['status'] = 100;
						$arr['value'] = $res;
					}else{
						$data['status'] = 104;
        	            $data['msg']    = '文件大小超负荷';
						$this->response($data,'json');exit();
					}
    			}else{
    				$data['status'] = 103;
        	        $data['msg']    = '文件格式不被允许';
    				$this->response($data,'json');exit();
    			}
    		}else{
    			$data['status'] = 105;
        	    $data['msg']    = '没有文件被上传';
    			$this->response($data,'json');exit();
    		}
		}else {
			$arr['status'] = 102;
			$arr['msg'] = "未定义的操作";
		}
		
		$this->response($arr,'json');
	}

	//修改词库内容
	public function updaCenterItemValue(){
		$id = I('post.id');
		$value = I('post.text');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		if(empty($value)){
			$arr['status'] = 102;
			$arr['msg'] = "项目内容不能为空";
			$this->response($arr,'json');
			exit();
		}
		if(empty($enabled)){
			$enabled = 1;
		}
		$data['value'] = $value;
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$res = \Think\Product\CenterItem::UpdaCenterItemValue($id,$data);
		if($res == 1){
			$arr['status'] = 100;
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "修改失败";
		}
		$this->response($arr,'json');
	}

	//获取词库全部信息
	public function getCenterItemValue(){
		$id = I('post.id');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		$res = \Think\Product\CenterItem::GetCenterItemValue($id);
		if($res == -1){
			$arr['status'] = 101;
			$arr['msg'] = "没有数据";
		}else{
			$arr['status'] = 100;
			$arr['value'] = $res;
		}
		$this->response($arr,'json');
	}

	//删除词库内容
	public function delCenterItemValue(){
		$id = I('post.id');
		if(is_array($id)){
			$res = \Think\Product\CenterItem::DelCenterItemValue($id);
			$arr['status'] = 100;
			$arr['value'] = $res;
		}else{
			if(empty($id)){
				$arr['status'] = 102;
				$arr['msg'] = "请选择项目";
				$this->response($arr,'json');
				exit();
			}
			$res = \Think\Product\CenterItem::DelCenterItemValue($id);
			if($res == 1){
				$arr['status'] = 100;
			}else{
				$arr['status'] = 100;
				$arr['msg'] = "删除失败";
			}
		}
		$this->response($arr,'json');
	}

	//添加词库与产品的关系
	public function addCenter2Good(){
		$item_id = I('post.item_id');
		$good_id = I('post.good_id');
		if(empty($item_id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		if(empty($good_id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择产品";
			$this->response($arr,'json');
			exit();
		}
		$creator_id = I('post.creator_id');
		if(empty($creator_id)){
			$arr['status'] = 1012;
			$this->response($arr,'json');
			exit();
		}
		$data['item_id'] = $item_id;
		$data['good_id'] = $good_id;
		$data['creator_id'] = $creator_id;
		$data['created_time'] = date('Y-m-d H:i:s',time());
		$data['modified_time'] = date('Y-m-d H:i:s',time());
		$res = \Think\Product\CenterItem::AddCenter2Good($data);
		if($res == 1){
			$arr['status'] = 100;
		}elseif($res == -2){
			$arr['status'] = 103;
			$arr['msg'] = "关系已经存在不用重复添加";
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "添加失败";
		}
		$this->response($arr,'json');
	}

	//删除词库与产品的关系
	public function delCenter2Good(){
		$id = I('post.id');
		if(is_array($id)){
			$res = \Think\Product\CenterItem::DelCenter2Good($id);
			$arr['status'] = 100;
			$arr['value'] = $res;
		}else{
			if(empty($id)){
				$arr['status'] = 102;
				$arr['msg'] = "请选择想要删除的关系";
				$this->response($arr,'json');
				exit();	
			}
			$res = \Think\Product\CenterItem::DelCenter2Good($id);
			if($res == 1){
				$arr['status'] = 100;
			}else{
				$arr['status'] = 100;
				$arr['msg'] = "删除失败";
			}
		}
		$this->response($arr,'json');
	}

	//获取词库与产品的关系
	public function getCenter2Good(){
		$id = I('post.id');
		if(empty($id)){
			$arr['status'] = 102;
			$arr['msg'] = "请选择项目";
			$this->response($arr,'json');
			exit();
		}
		$res = \Think\Product\CenterItem::GetCenter2Good($id);
		if($res){
			$arr['status'] = 100;
			$arr['value'] = $res;
		}else{
			$arr['status'] = 101;
			$arr['msg'] = "没有数据";
		}
		$this->response($arr,'json');
	}

	//获取产品所关联的词库内容
	public function getGood2CenterValue(){
		$good_ids = I('post.good_id');
		$num = I('post.num');
		foreach ($good_ids as $k => $valu) {
			if(empty($valu)){
				$arr['status'] = 102;
				$arr['msg'] = "请选择产品";
				$this->response($arr,'json');
				exit();
			}
		}
		$good_id = array_unique($good_ids);
		$i = 0;
		$a = 0;
		$max = 0;
		$kes = 0;

		$result = [];
		$results = [];
		foreach ($good_id as $key => $value) {
			$res = \Think\Product\CenterItem::GetGood2CenterValue($value);
			foreach ($res as $key => $va) {
				$count = count($va);
				if($count > $max){
					$max = $count;
				}
			}
			foreach ($res as $keys => $values) {
				for ($m=0; $m < $max; $m++) { 
					if(empty($values[$kes]['value'])){
						$kes = 0;
					}
					$arrs[$i]['产品名称'] = $values[$kes]['cn_name'].$values[$kes]['en_name'];
					$arrs[$i][$keys] = $values[$kes]['value'];
					$i++;
					$kes++;
				}
				$b = $i;
				$i = 0 + $a;
				$tow[] = $keys;
			}
			$i = $i+$b;
			$a = $i;			
		}
		
		$count = count($arrs);
		$z = 0;
		$va = array();
		for ($j=0; $j < $num; $j++) { 
			$va[$j] = $arrs[$z];
			if($z == $count-1){
				$z = 0;
			}else{
				$z++;
			} 
			
		}
		$tow= array_unique($tow);
		$one = array('产品名称');
		$array = array_merge($one,$tow);
		$arr['status'] = 100;
		$arr['header'] = $array;
		$arr['value'] = $va;
		
		$this->response($arr,'json');
	}
}