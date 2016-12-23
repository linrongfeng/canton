<?php
namespace Home\Controller;
use Think\Controller;

class TestController extends Controller{
	public function test(){
		print_r(GetSysId("product_information_record",525)) ;
	}
}