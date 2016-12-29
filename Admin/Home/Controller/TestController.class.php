<?php
namespace Home\Controller;
use Think\Controller;


class TestController extends BaseController{
	public function test()
	{
		set_time_limit(0);
        
        $table_info = I('post.table_info');
        $template_id = $table_info['template_id'];//模板id
        $category_id = $table_info['category_id'];//类目id
        $form_no = $table_info['form_no'];//表单编码
        $form_id = $table_info['id'];//表单ID
        $product_count =$table_info['product_count'];//产品总数
        $variant_num = $table_info['variant_num'];//变体数量

        $hc_data=S($form_no.'_data');//图片与词库匹配数据
        $creator_id = I('post.creator_id');
        if(empty($creator_id)){
            $arr['status'] = 1012;
            $this->response($arr,'json');
            exit();
        }
        
        if(empty($variant_num)){
            $num = $product_count;
        }else{
            $num = $product_count + ceil($product_count / $variant_num);//产品数量+主体数量
        }
        
        $getdata = I('post.getdata');//填写的默认值的数据
        $reludata = I('post.reludata');
        $SKUprefix = $reludata['sku_front'];//SKU前缀

        //SKU编码
        $sku_num1 = (int)$reludata['sku_num1'];
        $sku_num2 = (int)$reludata['sku_num2'];

        //Quantity on hand
        $quantity1 = (int)$reludata['quantity1'];
        $quantity2 = (int)$reludata['quantity2'];

        //Price (USD)
        $priceUsd1 = (float)$reludata['priceUsd1'];
        $priceUsd2 = (float)$reludata['priceUsd2'];

        //Price (GBP)
        $priceGbp1 = (int)$reludata['priceGbp1'];
        $priceGbp2 = (int)$reludata['priceGbp2'];

        //Weight  (ounce)
        $weight1  = (int)$reludata['weight1'];
        $weight2  = (int)$reludata['weight2'];

        //Size
        $size1 = (int)$reludata['size1'];
        $size2 = (int)$reludata['size2'];

        $tem_data = \Think\Product\Product_Item_Template::get('info',$template_id,"no,en_name,data_type_code,length,default_value");
        $z = 0;
        $j = 0;


        $code = $sku_num1;
        $size_start = $size1;
        $form_info = M('product_form_information');
        $info = M('product_information');
        
        $is_count = $form_info->field("product_id")->where("form_id=%d",array($form_id))->select();
        $f = 1;
        $s = 0;
        $id = GetSysId('product_information',$num);
        $ids = GetSysId('product_information_record',count($tem_data['value'])*$num);
        $info->startTrans();
        $myfile = fopen("./public/data/newfile.txt", "w") or die("Unable to open file!");
        if(empty($variant_num)){//没有变体的自动填表
            
            for($i = 0 ;$i < $product_count; $i++){
                $price = rand($priceUsd1,$priceUsd2);
                $decimal  = rand(1,99) / 100;
                if(empty($hc_data[$s])){
                    $s =0;
                }
                foreach ($tem_data['value'] as $keys => $values ) {
                    $data['id'] = $ids[$z];
                    $data['category_id'] = $category_id;
                    $data['template_id'] = $template_id;
                    $data['product_id'] = $id[$i];
                    $data['parent_id'] =  0;
                    $data['no'] = $values['no'];
                    $data['title'] = $values['en_name'];
                    $data['data_type_code'] = $values['data_type_code'];
                    $data['length'] = $values['length'];
                    $data['creator_id'] = $creator_id;
                    $data['created_time'] = date('Y-m-d H:i:s',time());
                    $data['modified_time'] = date('Y-m-d H:i:s',time());
                    switch ($data['data_type_code']) {
                        case 'int':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['interger_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['interger_value'] = $getdata['variant'][$values['en_name']][0];
                                }elseif($values['en_name'] == 'Quantity on hand'){
                                    if(!empty($quantity1) && !empty($quantity2)){
                                        $data['interger_value'] = rand($quantity1,$quantity2);
                                    }else{
                                		$data['interger_value'] = '';
                                	}                  
                                }elseif($values['en_name'] == 'Weight (ounce)'){
                                    if(!empty($weight1) && !empty($weight2)){
                                        $data['interger_value'] = rand($weight1,$weight2);
                                    }else{
                                		$data['interger_value'] = '';
                                	}    
                                }elseif($values['en_name'] == 'Weight  (ounce)'){
                                    if(!empty($weight1) && !empty($weight2)){
                                        $data['interger_value'] = rand($weight1,$weight2);
                                    }else{
                                		$data['interger_value'] = '';
                                	}  
                                }elseif($values['en_name'] == 'Size'){
                                    if(!empty($size1) && !empty($size2)){
                                        $data['interger_value'] = $size_start;
                                    }else{
                                		$data['interger_value'] = '';
                                	}
                                }else{
                                	$data['interger_value'] = '';
                                }
                            }else{
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['interger_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['interger_value'] = $getdata['variant'][$values['en_name']][0];
                                }else{
                                    $data['interger_value'] = $values['default_value'];
                                }
                                
                            }
                            $data['char_value'] = '';
                            $data['decimal_value'] = '';
                            $data['date_value'] = '';
                            break;
                        case 'char':
                        	$data['interger_value'] = '';
                            if($values['en_name'] == 'SKU'){
                                if(!empty($SKUprefix) && !empty($sku_num1) && !empty($sku_num2)){
                                    $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT);
                                }else{
                                	$data['char_value'] = '';
                                }  
                            }else{
                                if(empty($values['default_value'])){
                                    if(array_key_exists($values['en_name'], $getdata['default'])){
                                        $data['char_value'] = $getdata['default'][$values['en_name']];
                                    }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                        $data['char_value'] = $getdata['variant'][$values['en_name']][0];
                                    }elseif(array_key_exists($values['en_name'], $hc_data[$s])){
                                        $data['char_value'] = __str_replace($hc_data[$s][$values['en_name']]);
                                    }elseif($values['en_name'] == 'Quantity on hand'){
                                        if(!empty($quantity1) && !empty($quantity2)){
                                            $data['char_value'] = rand($quantity1,$quantity2);
                                        }else{
                                			$data['char_value'] = '';
                                		}                                   
                                    }elseif($values['en_name'] == 'Weight (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                            $data['char_value'] = rand($weight1,$weight2);
                                        }else{
                                			$data['char_value'] = '';
                                		}    
                                    }elseif($values['en_name'] == 'Weight  (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                            $data['char_value'] = rand($weight1,$weight2);
                                        }else{
                                			$data['char_value'] = '';
                                		}  
                                    }elseif($values['en_name'] == 'Size'){
                                        $data['char_value'] = $size_start;
                                    }elseif($values['en_name'] == 'Price (USD)'){
                                        if(!empty($priceUsd1) && !empty($priceUsd2)){
                                            $data['char_value'] = $price+$decimal;
                                        }else{
                                			$data['char_value'] = '';
                                		} 
                                    }elseif($values['en_name'] == 'Price (GBP)'){
                                        if(!empty($priceGbp1) && !empty($priceGbp2)){
                                            $data['char_value'] = $price-1+$decimal;
                                        }else{
                                			$data['char_value'] = '';
                                		} 
                                    }else{
                                		$data['char_value'] = '';
                                	}
                                }else{
                                    if(array_key_exists($values['en_name'],$getdata['default'])){
                                        $data['char_value'] = $getdata['default'][$values['en_name']];
                                    }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                        $data['char_value'] = $getdata['variant'][$values['en_name']][0];
                                    }elseif(array_key_exists($values['en_name'], $hc_data[$s])){
                                        $data['char_value'] = __str_replace($hc_data[$s][$values['en_name']]);
                                    }elseif($values['en_name'] == 'Quantity on hand'){
                                        if(!empty($quantity1) && !empty($quantity2)){
                                            $data['char_value'] = rand($quantity1,$quantity2);
                                        }else{
                                			$data['char_value'] = '';
                                		}                                   
                                    }elseif($values['en_name'] == 'Weight (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                            $data['char_value'] = rand($weight1,$weight2);
                                        }else{
                                			$data['char_value'] = '';
                                		}   
                                    }elseif($values['en_name'] == 'Weight  (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                            $data['char_value'] = rand($weight1,$weight2);
                                        }else{
                                			$data['char_value'] = '';
                                		}  
                                    }elseif($values['en_name'] == 'Size'){
                                        $data['char_value'] = $size_start;
                                    }elseif($values['en_name'] == 'Price (USD)'){
                                        if(!empty($priceUsd1) && !empty($priceUsd2)){
                                            $data['char_value'] = $price+$decimal;
                                        }else{
                                			$data['char_value'] = '';
                                		} 
                                    }elseif($values['en_name'] == 'Price (GBP)'){
                                        if(!empty($priceUsd1) && !empty($priceUsd2)){
                                            $data['char_value'] = $price-1+$decimal;
                                        }else{
                                			$data['char_value'] = '';
                                		} 
                                    }else{
                                        $data['char_value'] = $values['default_value'];
                                    }
                                }
                            }
                            $data['decimal_value'] = '';
                            $data['date_value'] = '';
                            break;
                        case 'dc':
                        	$data['interger_value'] = '';
                        	$data['char_value'] = '';
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['decimal_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['decimal_value'] = $getdata['variant'][$values['en_name']][0];
                                }elseif($values['en_name'] == 'Price (USD)'){
                                    if(!empty($priceUsd1) && !empty($priceUsd2)){
                                        $data['decimal_value'] = $price+$decimal;
                                    }else{
                                		$data['decimal_value'] = '';
                               		}
                                }elseif($values['en_name'] == 'Price (GBP)'){
                                    if(!empty($priceUsd1) && !empty($priceUsd2)){
                                        $data['decimal_value'] = $price-1+$decimal;
                                    }else{
                                		$data['decimal_value'] = '';
                               		}
                                }else{
                                	$data['decimal_value'] = '';
                                }
                            }else{
                                $data['decimal_value'] = $values['default_value'];
                            }
                            $date['date_value'] = '';
                            break;
                        case 'dt':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['date_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['date_value'] = $getdata['variant'][$values['en_name']][0];
                                }
                            }else{
                                $data['date_value'] = $values['default_value'];
                            }
                            break;
                        case 'bl':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['boolean_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['boolean_value'] = $getdata['variant'][$values['en_name']][0];
                                }
                            }else{
                                $data['boolean_value'] = $values['default_value'];
                            }
                            break;
                        case 'upc_code':
                        	$data['interger_value'] = '';
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['char_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['char_value'] = $getdata['variant'][$values['en_name']][0];
                                }
                            }else{
                                $data['char_value'] = $values['default_value'];
                            }
                            $data['decimal_value'] = '';
                            $data['date_value'] = '';
                            break;
                        case 'pic':
                        		$data['interger_value'] = '';
                                if($f == 1){
                                    $data['char_value'] = $hc_data[$s]['photo'];
                                    $f = 2;
                                }
                                $data['decimal_value'] = '';
                            $data['date_value'] = '';
                            break;
                        }
                    $z++;
                    $txt = implode(",", $data)."\n";
                    fwrite($myfile, $txt);
                    $data = array();
                }
                $f = 1;
                $code++; 
                $s++;
            }
            fclose($myfile);
        }else{
            for ($i=1; $i < $product_count+1; $i++) { //主体
                
                if($i % $variant_num== 1){
                    $price = rand($priceUsd1,$priceUsd2);
                    $decimal  = rand(1,99) / 100;
                    if($i != 1){
                        $s++;
                        if(empty($hc_data[$s])){
                            $s = 0;
                        }
                        $code++;
                    }
                    foreach ($tem_data['value'] as $key => $value) {
                        $data['id'] = $ids[$z];
                        $data['category_id'] = $category_id;
                        $data['template_id'] = $template_id;
                        $data['product_id'] = $id[$j];
                        $data['parent_id'] = 0;
                        $data['no'] = $value['no'];
                        $data['title'] = $value['en_name'];
                        $data['data_type_code'] = $value['data_type_code'];
                        $data['length'] = $value['length'];
                        $data['creator_id'] = $creator_id;
                        $data['created_time'] = date('Y-m-d H:i:s',time());
                        $data['modified_time'] = date('Y-m-d H:i:s',time());
                        switch ($data['data_type_code']) {
                            case 'int':
                                if(empty($value['default_value'])){//判断是否有默认值
                                    if(array_key_exists($value['en_name'], $getdata['default'])){// 判断是否在编辑的默认值中
                                        $data['interger_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['interger_value'] = $getdata['variant'][$value['en_name']][0];
                                    }
                                }else{
                                    if(array_key_exists($value['en_name'], $getdata['default'])){
                                        $data['interger_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['interger_value'] = $getdata['variant'][$value['en_name']][0];
                                    }else{
                                        $data['interger_value'] = $value['default_value'];//使用默认值
                                    }
                                    
                                }
                                break;
                            case 'char':
                                if($value['en_name'] == 'SKU'){//判断是否为SKU，是就按下面的规则组合SKU
                                    if(!empty($SKUprefix) && !empty($sku_num1) && !empty($sku_num2)){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT);
                                    }
                                }else{
                                    if(empty($value['default_value'])){
                                        if(array_key_exists($value['en_name'], $getdata['default'])){
                                            $data['char_value'] = $getdata['default'][$value['en_name']];
                                        }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                            $data['char_value'] = $getdata['variant'][$value['en_name']][0];
                                        }elseif(array_key_exists($value['en_name'], $hc_data[$s])){//   判断是否在图片与词库的数组中
                                            $data['char_value'] = __str_replace($hc_data[$s][$value['en_name']]);
                                        }
                                    }else{
                                        if(array_key_exists($value['en_name'], $getdata['default'])){
                                            $data['char_value'] = $getdata['default'][$value['en_name']];
                                        }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                            $data['char_value'] = $getdata['variant'][$value['en_name']][0];
                                        }elseif(array_key_exists($value['en_name'], $hc_data[$s])){
                                            $data['char_value'] = __str_replace($hc_data[$s][$value['en_name']]);
                                        }else{
                                            $data['char_value'] = $value['default_value'];
                                        }
                                    }
                                }
                                break;
                            case 'dc':
                                if(empty($value['default_value'])){
                                    if(array_key_exists($value['en_name'], $getdata['default'])){
                                        $data['decimal_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['decimal_value'] = $getdata['variant'][$value['en_name']][0];
                                    }
                                }else{
                                    $data['decimal_value'] = $value['default_value'];
                                }
                                break;
                            case 'dt':
                                if(empty($value['default_value'])){
                                    if(array_key_exists($value['en_name'], $getdata['default'])){
                                        $data['date_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['date_value'] = $getdata['variant'][$value['en_name']][0];
                                    }
                                }else{
                                    $data['date_value'] = $value['default_value'];
                                }
                                break;
                            case 'bl':
                                if(empty($value['default_value'])){
                                    if(array_key_exists($value['en_name'], $getdata['default'])){
                                        $data['boolean_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['boolean_value'] = $getdata['variant'][$value['en_name']][0];
                                    }
                                }else{
                                    $data['boolean_value'] = $value['default_value'];
                                }
                                break;
                            case 'upc_code':
                                if(empty($value['default_value'])){
                                    if(array_key_exists($value['en_name'], $getdata['default'])){
                                        $data['char_value'] = $getdata['default'][$value['en_name']];
                                    }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                        $data['char_value'] = $getdata['variant'][$value['en_name']][0];
                                    }
                                }else{
                                    $data['char_value'] = $value['default_value'];
                                }
                                break;
                            case 'pic':
                                if($f == 1){
                                    if(empty($hc_data[$s]['photo'])){
                                        $s =0;
                                    }
                                    $data['char_value'] = $hc_data[$s]['photo'];
                                    $f = 2;
                                }
                                break;
                        }
                        $z++;
                        $txt = implode(",", $data).'\n'; 
                        fwrite($myfile, $txt);
                        $data = array();
                    }
                    $a = $j;
                    $j++; 
                    $f = 1;
                }
                foreach ($tem_data['value'] as $keys => $values ) {
                    $data['id'] = $ids[$z];
                    $data['category_id'] = $category_id;
                    $data['template_id'] = $template_id;
                    $data['product_id'] = $id[$j];
                    $data['parent_id'] =  $id[$a];
                    $data['no'] = $values['no'];
                    $data['title'] = $values['en_name'];
                    $data['data_type_code'] = $values['data_type_code'];
                    $data['length'] = $values['length'];
                    $data['creator_id'] = $creator_id;
                    $data['created_time'] = date('Y-m-d H:i:s',time());
                    $data['modified_time'] = date('Y-m-d H:i:s',time());
                    switch ($data['data_type_code']) {
                        case 'int':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['interger_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['interger_value'] = $getdata['variant'][$values['en_name']][1];
                                }elseif($values['en_name'] == 'Quantity on hand'){
                                    if(!empty($quantity1) && !empty($quantity2)){
                                        $data['interger_value'] = rand($quantity1,$quantity2);
                                    }                  
                                }elseif($values['en_name'] == 'Weight (ounce)'){
                                    if(!empty($weight1) && !empty($weight2)){
                                        $data['interger_value'] = rand($weight1,$weight2);
                                    }    
                                }elseif($values['en_name'] == 'Weight  (ounce)'){
                                    if(!empty($weight1) && !empty($weight2)){
                                        $data['interger_value'] = rand($weight1,$weight2);
                                    }  
                                }elseif($values['en_name'] == 'Size'){
                                    $data['interger_value'] = $size_start;
                                }
                            }else{
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['interger_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['interger_value'] = $getdata['variant'][$values['en_name']][1];
                                }else{
                                    $data['interger_value'] = $values['default_value'];
                                }
                                
                            }
                            break;
                        case 'char':
                            if($values['en_name'] == 'SKU'){
                                if(!empty($SKUprefix) && !empty($sku_num1) && !empty($sku_num2)){
                                    if(array_key_exists('Color ', $getdata['default'])){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($getdata['default']['Color '],0,2);
                                    }elseif(array_key_exists('Color', $getdata['default'])){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($getdata['default']['Color'],0,2);
                                    }elseif(array_key_exists('Color ', $getdata['variant'])){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($getdata['variant']['Color '][1],0,2);
                                    }elseif(array_key_exists('Color', $getdata['variant'])){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($getdata['variant']['Color'][1],0,2);
                                    }elseif(array_key_exists('Color',$values)){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($values['Color'],0,2);
                                    }elseif(array_key_exists('Color ',$values)){
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start.'-'.substr($values['Color '],0,2);
                                    }else{
                                        $data['char_value'] = $SKUprefix.str_pad($code,4,"0",STR_PAD_LEFT).'-'.$size_start;
                                    }
                                }  
                            }else{
                                if(empty($values['default_value'])){
                                    if(array_key_exists($values['en_name'], $getdata['default'])){
                                        $data['char_value'] = $getdata['default'][$values['en_name']];
                                    }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                        $data['char_value'] = $getdata['variant'][$values['en_name']][1];
                                    }elseif(array_key_exists($values['en_name'], $hc_data[$s])){
                                        $data['char_value'] = __str_replace($hc_data[$s][$values['en_name']]);
                                    }elseif($values['en_name'] == 'Quantity on hand'){
                                        if(!empty($quantity1) && !empty($quantity2)){
                                            $data['char_value'] = rand($quantity1,$quantity2);
                                        }                                   
                                    }elseif($values['en_name'] == 'Weight (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                                $data['char_value'] = rand($weight1,$weight2);
                                            }    
                                    }elseif($values['en_name'] == 'Weight  (ounce)'){
                                        if(!empty($weight1) && !empty($weight2)){
                                            $data['char_value'] = rand($weight1,$weight2);
                                        }  
                                    }elseif($values['en_name'] == 'Size'){
                                        $data['char_value'] = $size_start;
                                    }elseif($values['en_name'] == 'Price (USD)'){
                                        if(!empty($priceUsd1) && !empty($priceUsd2)){
                                            $data['char_value'] = $price+$decimal;
                                        } 
                                    }elseif($values['en_name'] == 'Price (GBP)'){
                                        if(!empty($priceUsd1) && !empty($priceUsd2)){
                                            $data['char_value'] = $price-1+$decimal;
                                        } 
                                    }

                                }else{
                                    if(array_key_exists($values['en_name'],$getdata['default'])){
                                        $data['char_value'] = $getdata['default'][$values['en_name']];
                                    }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                        $data['char_value'] = $getdata['variant'][$values['en_name']][1];
                                    }elseif(array_key_exists($values['en_name'], $hc_data[$s])){
                                        $data['char_value'] = __str_replace($hc_data[$s][$values['en_name']]);
                                    }else{
                                        $data['char_value'] = $values['default_value'];
                                    }
                                }
                            }
                            break;
                        case 'dc':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['decimal_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['decimal_value'] = $getdata['variant'][$values['en_name']][1];
                                }elseif($values['en_name'] == 'Price (USD)'){
                                    if(!empty($priceUsd1) && !empty($priceUsd2)){
                                        $data['decimal_value'] = $price+$decimal;
                                    } 
                                }elseif($values['en_name'] == 'Price (GBP)'){
                                    if(!empty($priceUsd1) && !empty($priceUsd2)){
                                        $data['decimal_value'] = $price-1+$decimal;
                                    } 
                                }
                            }else{
                                $data['decimal_value'] = $values['default_value'];
                            }
                            break;
                        case 'dt':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['date_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['date_value'] = $getdata['variant'][$values['en_name']][1];
                                }
                            }else{
                                $data['date_value'] = $values['default_value'];
                            }
                            break;
                        case 'bl':
                            if(empty($values['default_value'])){
                                if(array_key_exists($values['en_name'], $getdata['default'])){
                                    $data['boolean_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($values['en_name'], $getdata['variant'])){
                                    $data['boolean_value'] = $getdata['variant'][$values['en_name']][1];
                                }
                            }else{
                                $data['boolean_value'] = $values['default_value'];
                            }
                            break;
                        case 'upc_code':
                            if(empty($values['default_value'])){
                                if(array_key_exists($value['en_name'], $getdata['default'])){
                                    $data['char_value'] = $getdata['default'][$values['en_name']];
                                }elseif(array_key_exists($value['en_name'], $getdata['variant'])){
                                    $data['char_value'] = $getdata['variant'][$values['en_name']][1];
                                }
                            }else{
                                $data['char_value'] = $values['default_value'];
                            }
                            break;
                        case 'pic':
                                if($f == 1){
                                    $data['char_value'] = $hc_data[$s]['photo'];
                                    $f = 2;
                                }
                            break;
                        }
                    $z++;
                    $sql = $info->add($data);
                    $data = array();
                }
                if($i % $variant_num == 0){
                    $size_start= $size1;
                }else{
                    $size_start++;
                }
                $f = 1;
                $j++;  
            }      
        }
        M()->execute("LOAD DATA  INFILE 'e:/www/canton/public/data/newfile.txt' INTO TABLE tbl_product_information
FIELDS TERMINATED BY ','
OPTIONALLY ENCLOSED BY ''
LINES TERMINATED BY '\n'(`id`,`category_id`,`template_id`,`product_id`,`parent_id`,`no`,`title`,`data_type_code`,`length`,`creator_id`,`created_time`,`modified_time`,`interger_value`,`char_value`,`decimal_value`,`date_value`)");
        $info->commit(); 
        $form_info->startTrans();
        for ($q=0; $q < $num; $q++) { 
            $da['form_id'] = $form_id;
            $da['product_id'] = $id[$q];
            $da['created_time'] = date('Y-m-d H:i:s',time());
            $query11 = $form_info->add($da);
        }
        $form_info->commit();
        $arr['status'] = 100;
        $this->response($arr,'json');
	}
}