<?php
/**
 * 使用openssl实现非对称加密
 */
class rsa{    
	private $_privKey;		/*** 私钥*/   
	private $_pubKey;		/*** 公钥*/   
	public function __construct($confs){
		if(empty($confs) || (!isset($confs['privKey']) && !isset($confs['pubKey']))){
			throw new Exception('Must set the key');
		}
		$this->_privKey = isset($confs['privKey']) ? $confs['privKey'] : null;
		$this->_pubKey = isset($confs['pubKey']) ? $confs['pubKey'] : null;
		
	}


	/**
	 * 获取私钥
	 */
	public function setupPrivKey(){
		if($this->_privKey =='') {
			die('privKey is null');
		}
		return true;
	}
   
	/**
	 * 获取公钥
	 */
	public function setupPubKey(){
		if($this->_pubKey ==''){
			die('pubKey is null');
		}
		return true;
	}
   
	/**
	 * 用私钥加密
	 */
	public function privEncrypt($data){
		if(!is_string($data)){
			return null;
		}
	   
		$this->setupPrivKey();
		$r = openssl_private_encrypt($data, $encrypted, $this->_privKey);
		if($r){
			return base64_encode($encrypted);
		}
		return null;
	}
   
	/**
	 * 用私钥解密
	 */
	public function privDecrypt($encrypted){
		if(!is_string($encrypted)){
			return null;
		}
	   
		$this->setupPrivKey();
	   
		$encrypted = base64_decode(str_replace(" ","+",$encrypted));

		$r = openssl_private_decrypt($encrypted, $decrypted, $this->_privKey);
		if($r){
			return $decrypted;
		}
		return null;
	}
   
	/**
	 * 公钥加密
	 */
	public function pubEncrypt($data){
		
		if(!is_string($data)){
				return null;
		}
		$this->setupPubKey();
	   
		$r = openssl_public_encrypt($data, $encrypted, $this->_pubKey);
		if($r){
				return base64_encode($encrypted);
		}
		return null;
	}
   
	/**
	 * 公钥解密
	 */
	public function pubDecrypt($crypted){
		if(!is_string($crypted)){
				return null;
		}
		$this->setupPubKey(); 
		$crypted = base64_decode(str_replace(" ","+",$crypted));
		$r = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
		if($r){
				return $decrypted;
		}
			return null;
	}
   
	public function __destruct(){
		@ fclose($this->_privKey);
		@ fclose($this->_pubKey);
	}
	
	/**
	 * 签名
	 * $data 签名字符串
	 */
	public function sign($data) {
		if(!is_string($data)){
				return null;
		}
		$this->setupPrivKey();
		if(openssl_sign($data, $binary_signature, $this->_privKey, OPENSSL_ALGO_SHA1)) {
			return base64_encode($binary_signature);
		}
		return null;
	}
	
	/**
	 * 签名认证
	 * $data 认证的字符串
	 * $binary_signature 签名的字符串
	 */
	 public function verify($data,$binary_signature) {
		if(!is_string($data) || !is_string($binary_signature)){
		   return null;
		}
		$this->setupPubKey();
		if(openssl_verify($data, base64_decode($binary_signature), $this->_pubKey, OPENSSL_ALGO_SHA1)) {
			return true;
		}
		return false;
	}
	
	
}
?>
