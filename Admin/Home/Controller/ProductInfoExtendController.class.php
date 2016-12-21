<?php
namespace Home\Controller;
use Think\Controller\RestController;
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:POST,GET');
header('Access-Control-Allow-Credentials:true'); 
header("Content-Type: application/json;charset=utf-8");
/**
* 产品资料扩展控制器
*/
class ProductInfoExtendController extends RestController{
	protected $dt   = "/^([1][7-9]{1}[0-9]{1}[0-9]{1}|[2][0-9]{1}[0-9]{1}[0-9]{1})(-)([0][1-9]{1}|[1][0-2]{1})(-)([0-2]{1}[1-9]{1}|[3]{1}[0-1]{1})*$/";
    protected $dt1  = "/^([1][7-9][0-9][0-9]|[2][0][0-9][0-9])(\.)([0][1-9]|[1][0-2])(\.)([0-2][1-9]|[3][0-1])*$/";
    protected $dt2  = "/^([1][7-9][0-9][0-9]|[2][0][0-9][0-9])([0][1-9]|[1][0-2])([0-2][1-9]|[3][0-1])*$/";
    protected $dt3  = "/^([1][7-9][0-9][0-9]|[2][0][0-9][0-9])(\/)([0][1-9]|[1][0-2])(\/)([0-2][1-9]|[3][0-1])*$/";
	
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

    /*
	 * 获取模板的数据格式
	 */
	public function getTemFormat(){
		$template_id = I('post.template_id');
		$type_code = I('post.type_code');
		if($type_code != 'info' && $type_code !='batch'){
			$arr['status'] = 119;
			$arr['msg'] = "系统错误";
			$this->response($arr,'json');
			exit();
		}
		if(empty($template_id)){
			$arr['status'] = 102;
			$arr['msg'] = "模板信息错误";
			$this->response($arr,'json');
			exit();
		}
		$res = \Think\Product\ProductInfoExtend::GetTemplateFormat($template_id,$type_code);
		$this->response($res,'json');
	}

	/*
	 * 改版的数据提交与暂存
	 */
	public function dataCommit(){
        set_time_limit(0);
		$form_id = I('post.form_id');
		$template_id = I('post.template_id');
		$category_id = I('post.category_id');
		$type_code = I('post.type_code');
		$type  = I('post.type');     // 暂存或者提交
		$max = I('post.max');
        $gridColumns = I('post.gridColumns');
		$text = file_get_contents("php://input");
        $textdata    = urldecode($text);
		
		if($type_code != 'info' && $type_code !='batch'){
			$arr['status'] = 119;
			$arr['msg'] = "系统错误";
			$this->response($arr,'json');
			exit();
		}
		if(empty($template_id)){
			$arr['status'] = 102;
			$arr['msg'] = "模板信息错误";
			$this->response($arr,'json');
			exit();
		}
		if(empty($form_id)){
			$arr['status'] = 102;
			$arr['msg'] = "表格信息错误";
			$this->response($arr,'json');
			exit();
		}
		if(empty($category_id)){
			$arr['status'] = 102;
			$arr['msg'] = "类目信息错误";
			$this->response($arr,'json');
			exit();
		}

		                               
		if($type_code == 'info'){
			$item = M('product_item_template');
			$info = M('product_information');
			$form = M('product_form_information');
			$types = M('product_form');
            $code = 'product_information_record';//应用代码，将用于获取全局产品记录id
            $n = 10;
        }else {
        	$item = M('product_batch_item_template');
        	$info = M('product_batch_information');
        	$form = M('product_batch_form_information');
        	$types = M('product_batch_form');
            $code = 'product_batch_information_record';
            $n = 1;
        }
        $num  = ceil( $max / $n );

        $j = 0;
        for($z = 0; $z < $num; $z ++) {                     // 分包获取传的产品数量
            $b = stripos($textdata, 'gridData[' . $j . ']');
            $j = $j + $n;
            $c = stripos($textdata, 'gridData[' . $j . ']');
            if (empty($c)) {
                $g = substr($textdata, $b);
            } else {
                $g = substr($textdata, $b, $c - $b - 1);
            }
            parse_str($g);
            $pro_data[] = $gridData;
            $gridData = array();
        }

        $info->startTrans();
       	$sql = $item->field("en_name,no,data_type_code,length,precision")->where("template_id=%d",array($template_id))->select();
       	foreach ($sql as $key => $value) {
       		$data_style[$value['en_name']]['no'] = $value['no'];
       		$data_style[$value['en_name']]['data_type_code'] = $value['data_type_code'];
       		$data_style[$value['en_name']]['length'] = $value['length'];
       		$data_style[$value['en_name']]['precision'] = $value['precision'];
       	}

        // $array['status'] = 100;
        // $array['te'] = $data_style;
        // $this->response($array,'json');exit();
       	$m = 0;
       	foreach ($pro_data as $k => $va) {
            foreach ($va as $vkey => $v_data) {
                if($v_data[array_search('types',$gridColumns)] == 'yes'){
                    $m++;
                }
            }
       		
       	}
       	$newdata = $m*count($data_style);
       	if($newdata > 0){
       		$id = GetSysId($code,$newdata);
       	}

       	$i = 0;
       	
        foreach ($pro_data as $keys => $values) {
        	foreach ($values as $k => $valu) {
                $product_id = $valu[array_search('product_id',$gridColumns)];
                $parent_id = $valu[array_search('parent_id',$gridColumns)];
                $ty = $valu[array_search('types',$gridColumns)];
                foreach ($valu as $ke => $val) {
                    $value_key = $gridColumns[$ke];
                    if(!array_key_exists($value_key, $data_style)){
                        continue;
                    }
                    switch ($data_style[$value_key]['data_type_code']) {
                        case 'int':  
                            $data_type = 'interger_value';
                            if(!empty($val)){
                                if(!preg_match("/^[0-9]*$/", $val)){
                                    $info->rollback();
                                    $array['status'] = 103;
                                    $array['msg']    = '整数数据类型填写错误';
                                    $this->response($array, 'json');
                                    exit();
                                }
                            }
                             
                          break;
                        case 'char': 
                            $data_type = 'char_value'; 
                            if(!empty($val)){
                                $nums = strlen(trim($val));
                                if ($nums > $data_style[$value_key]['length']) {
                                    $info->rollback();
                                    $array['status'] = 106;
                                    $array['msg']    = '字符数据类型填写错误';
                                    $this->response($array, 'json');
                                    exit();
                                }
                            }
                          break;
                        case 'dc':   
                            $data_type = 'decimal_value';
                            if(!empty($val)){
                                if (!preg_match("/^(\d*\.)?\d+$/", $val)) { 
                                    $info->rollback();
                                    $array['status'] = 104;
                                    $array['msg'] = '小数数据类型填写错误';
                                    $this->response($array, 'json');
                                    exit();
                                }
                            }
                          break;
                        case 'dt':   
                            $data_type = 'date_value'; 
                            if(!empty($val)){
                                if (preg_match($this->dt, $val) || preg_match($this->dt1, $val) || 
                                    preg_match($this->dt2, $val) || preg_match($this->dt3, $val)) {
                                    $info->rollback();
                                    $array['status'] = 105;
                                    $array['msg']    = '日期数据类型填写错误';
                                    $this->response($array, 'json');
                                    exit();
                                }
                            }
                          break;
                        case 'bl':   
                            $data_type = 'boolean_value'; 
                          break;
                        case 'upc_code': 
                            $data_type = 'char_value';
                          break;
                        case 'pic': 
                            $data_type = 'char_value';
                          break;
                    }
                    if(empty($val)){
                        $valss = null;
                    }else{
                        $valss = $val;
                    }
                    $data[$data_type] = $valss;
                    $data['modified_time'] = date('Y-m-d H:i:s',time());
                    if(empty($ty) || $ty != 'yes'){
                        $where['product_id'] = $product_id;
                        $where['title'] = $value_key;
                        $query = $info->data($data)->where($where)->save();
                        if($query === 'flase'){
                            $info->rollback();
                            $arr['status'] = 101;
                            $arr['msg'] = "提交或者暂存失败";
                            $this->response($arr,'json');
                            exit();
                        }
                        $data = array();
                    }else{
                        $data['id'] = $id[$i];
                        $data['category_id']    = $category_id;
                        $data['template_id']    = $template_id;
                        $data['product_id']     = $product_id;
                        $data['parent_id']      = $parent_id;
                        $data['no'] = $data_style[$value_key]['no'];
                        $data['title'] = $value_key;
                        $data['data_type_code'] = $data_style[$value_key]['data_type_code'];
                        $data['length'] = $data_style[$value_key]['length'];
                        $data['precision'] = $data_style[$value_key]['precision'];
                        $data['created_time'] = date('Y-m-d H:i:s',time());
                        $query = $info->data($data)->add();
                        if($query === 'flase'){
                            $info->rollback();
                            $arr['status'] = 101;
                            $arr['msg'] = "提交或者暂存失败";
                            $this->response($arr,'json');
                            exit();
                        }
                        $i++;
                        $data = array();
                    }
                    
                }
                if($pro_data[$keys][$k][array_search('types',$gridColumns)] == 'yes'){
                    $datas['form_id'] = $form_id;
                    $datas['product_id'] = $product_id;
                    $datas['created_time'] = date('Y-m-d H:i:s',time());
                    $oper = $form->data($datas)->add();
                    if(!$oper){
                        $info->rollback();
                        $arr['status'] = 101;
                        $arr['msg'] = "提交或者暂存失败";
                        $this->response($arr,'json');
                        exit();
                    }
                }
            }		
        }
        if($type == 'submit'){
            $status_code['status_code'] = 'editing';
            $types->where('id=%d',array($form_id))->data($status_code)->save();
        }
        $info->commit();
        $arr['status'] = 100;
       	$this->response($arr,'json');
	}
    
}