<?php
//接口认证
class apiAuthorize{
	var $ras;
	var $errorMessage;
	public function __construct($options=[])
    {
      $rsa = isset($options['rsa']) ? $options['rsa'] : [];
	  $this->rsa = new rsa($rsa);
    }
	//签名认证
	public function signCheck($fields) {
		if(!isset($fields)) {
		   $vars = $this->httpVarsDeal();
		}else{
		   $vars = $fields;	
		}
		$sign = $vars['_sign'];
		unset($vars['_sign']);
		if(!isset($sign)) {
			$this->errorMessage = 'Signature cannot be empty';
			return false;
		}
		$string = $this->getFieldsSignString($vars);
		if(!$this->rsa->verify($string,$sign)) {
			$this->errorMessage = 'Signature authentication failed';
			return false;
		}else{
			return true;
		}
		
	}
	//身份校验
	public function IdentityCheck() {
		$fields = $this->httpVarsDeal();
		if(!$this->signCheck($fields)) {
			return false;
		}
		if(empty($fields['timestamp'])) {
			$this->errorMessage = 'timestamp cannot be empty';
			return false;
		}
		$timeOutSize = 30;//设置超时
		$nowtime = time();
		if($fields['timestamp'] + $timeOutSize < $nowtime) {
			$this->errorMessage = 'overtime';
			return false;
		}
		if(empty($fields['uuid'])) {
			$this->errorMessage = 'uuid cannot be empty';
			return false;
		}
		if(empty($fields['app'])) {
			$this->errorMessage = 'app cannot be empty';
			return false;
		}
		$cache = new cache();
		$cacheId = $fields['_sign']; 
		if($cache->get($cacheId)) {
			$this->errorMessage = 'the signature is invalid';
			return false;
		}else{
			$cache->set($cacheId, 1, $timeOutSize);
		}
		return true;
		
	}
	
	//获得用户会话id
	public function getUuid() {
		$uuid = @session_id();
		if(empty($uuid)) {
			session_start();
			return session_id();
		}else{
			return $uuid;
		}
	}
	
	//对签名参数进行拼接
	//array $fields;
	public function getFieldsSignString($fields) {
		if(empty($fields) || !is_array($fields)) {
			return null;
		}else{
          $params = array();
          foreach ($fields as $key => $val) {
			 if(is_int($key)) {
				 $key = '';
			 }
			 if(is_array($val)) {
				$params[] = $key . $this->getFieldsSignString($val);
			 }else{
				if($val!='' && is_string($val) && $val{0} == '@' && is_file($path = ltrim($val,'@'))) { //如果上传的是文件
					$val = md5_file($path); //散列文件，防止非法串改文件
				}
                $params[] = $key . $val;
			 }
          }
		  sort($params);
          $sign_str = join('', $params);
		  $sign_str = strtoupper($sign_str);
		  return $sign_str;
	   }
	}
	
	public function httpVarsDeal() {
		$vars = [];
		if(isset($_GET)) {
		    $vars = array_merge($vars,$_GET);
		}
		if(isset($_POST)) {
			$vars = array_merge($vars,$_POST);
		}
		if(isset($_FILES)){
			foreach($_FILES as $key => $val) {
				if(is_array($val['tmp_name'])) {
					foreach($val['tmp_name'] as $key1 => $val1) {
					  $vars[$key][] = '@'.$val1;
					}
					
				}else{
					$vars[$key] = '@'.$val['tmp_name'];
				}
			}	
		}
		return $vars;
	}
	
   	//获得签名后的参数数组
	 public function paramsAddSign($fields) {
		if(empty($fields)) {
			return ''; 
		}
		$fields['uuid'] = $this->getUuid();//获得用户标识符
		$fields['timestamp'] = time();//获得用户开始调用api的时间
		$fields['rand'] = getRandChar(); //生成随机数，这个很重要，其目的是让1次api签名只能使用一次，以防止非法盗用api链接。
		$sign = $this->rsa->sign($this->getFieldsSignString($fields));
		$fields['_sign'] = $sign; 
		return $fields;
	}
	
}





?>