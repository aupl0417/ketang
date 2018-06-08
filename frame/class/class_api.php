<?php
abstract class api extends controller {
    public function __construct($options = [], $power = []) {
		parent::__construct($options, [0], $power);
        if(!defined('INTERNAL_CALL')) { //如果不是内部调用就需要进行签名校验
           $confs['rsa'] = ['pubKey'=>$this->_get_pubkey()];
	       $apiAuth = new apiAuthorize($confs);
		   if(!$apiAuth->IdentityCheck()) {
			 die(json_encode(array("code" => 1000, "data" => $apiAuth->errorMessage)));	
		   }
		}
    }
	 //获得公钥
	 protected function _get_pubkey() {
		$app = isset($this->options['app']) ? $this->options['app'] : '';
		if(empty($app)) {
			die(json_encode(array("code" => 1000, "data" => "app cannot be empty")));
		}
		$path = SECRETKEY_PATH.'/'.$app.'_pub.pem';
		$countent = getCacheFileContent($path);
		if(!empty($countent)) {
			return $countent;
		}else{
		    die(json_encode(array("code" => 1000, "data" => "Illegal entry")));	
		}
	}
	//解密传过来的加密字段
	protected function decrypt($string) {
		if(defined('INTERNAL_CALL'))
		  return $string;
		if(empty($string))
		  return ''; 
		$rsa = new rsa(['pubKey' => $this->_get_pubkey()]);
		$string = $rsa->pubDecrypt($string); 
		if(empty($string)) { 
		   return ''; 
		}else{
			return $string;
		}
	}
}
