<?php

/**
 * @author vc
 * @version 2.5.0
 *
 * CURL
 */

class apis
{
    static private $requestNum = 0;    //请求次数
    static public $error = null;
	
	/* 项目调用api的统一方法 */
	/* $path: 请求的路径 u/index/test.json */
	/* $postFields 请求的参数 */
	/* $reNum 如果执行失败,重复请求的次数 */
	/* $connettimeout 请求连接时间;如果是内部项目请求,该参数无效 */
	/* $timeout 执行超时时间; 如果是内部项目请求,该参数无效*/
	// $istransArray 是否返回数组 true为返回
	
	static public function request($path, $postFields = null,$istransArray = false,$reNum = 3,$connettimeout = 5,$timeout = 10){
		/* 如果是项目内部,那么不使用curl方法 */
		/* 如果是跨项目,那么使用curl方法 */
		/* 测试,统一使用curl方法 */		
		$url = apiUrl($path);
		//文件上传用如下格式化
		//$postFields['pic'] = '@D:/cs/e.jpg;type=image/jpeg;filename=maosheng.jpg';
		$urlInfo = parse_url($url);
		$data = null;
		if(isset($urlInfo['host'])) {
			list($app_name,$second) = explode('.',$urlInfo['host'],2);
			if(!empty(IGNORE_SIGN_APPS) && in_array($app_name,explode('|',IGNORE_SIGN_APPS))) {
			    $data = self::get_include_file_result($path,$postFields);	
			}else{
			    $confs['rsa'] = ['privKey'=>self::get_privkey($app_name)];
		        $apiAuth = new apiAuthorize($confs);
			    $postFields['app'] = $app_name;
		        $postFields = $apiAuth->paramsAddSign($postFields);
			    $data = self::curl($url, $postFields, 1);
			}
		}else{
			$data = self::get_include_file_result($url,$postFields);
		}
		return $istransArray ? self::decode($data,true) : $data;
	}
	//获取私钥
    static public function get_privkey($app) {
		return empty($app) ? '' : getCacheFileContent(SECRETKEY_PATH .'/'.$app.'_key.pem');
	}
	//加密字段
	//$string 加密的字符串
	//$app 接口所在的app
	static public function encrypt($string,$app) {
		if(empty($string) || empty($app)) {
		   self::$error = '参数不能为空';
		   return false;
		}
		$rsa = new rsa(['privKey' => self::get_privkey($app)]);
		$string = $rsa->privEncrypt($string);
		if(empty($string)) {
		   self::$error = '加密失败';
		   return false;
		}
		return $string;
	}
	
    /**
     * 服务端执行post请求调用（正确返回reponse对象，错误返回null）
     *
    */
    static private function curl($url, $postFields = null,$reNum = 3,$connettimeout = 5,$timeout = 10)
    {
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //请求等待时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connettimeout);
        //执行时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            /*$postBodyString = '';
            foreach ($postFields as $k => $v) {
                if ('@' != substr($v, 0, 1)) {//判断是不是文件上传
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                }
            }
            $postFields = trim($postBodyString, '&');
            unset($k, $v);*/
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        try {			
            $reponse = curl_exec($ch);
            //判断返回值是否为空
            if($reponse == ''){
                if(self::$requestNum >= $reNum){
                    self::$error = '返回数据为空'.curl_error($ch);
                    throw new Exception(curl_error($ch), 0);
                }
                self::$requestNum += 1;
                //重新请求
                self::curl($url, $postFields,$reNum ,$connettimeout,$timeout);
            }

            if (curl_errno($ch)) {
                if(self::$requestNum >= $reNum){
                    self::$error = curl_error($ch);
                    throw new Exception(curl_error($ch), 0);
                }
                self::$requestNum += 1;
                //重新请求
                self::curl($url, $postFields,$reNum ,$connettimeout,$timeout);
            } else {
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (200 !== $httpStatusCode) {
                    if(self::$requestNum >= $reNum){
                        self::$error = '请求返回状态不是200';
                        throw new Exception('httpStatusError', $httpStatusCode);
                    }
                    self::$requestNum += 1;
                    //重新请求
                    self::curl($url, $postFields,$reNum ,$connettimeout,$timeout);
                }
            }

            //判断返回值是否为json格式
            if(is_null(json_decode($reponse))){
                if(self::$requestNum >= $reNum){
                    self::$error = '返回值不是json格式'.curl_error($ch);
                    throw new Exception(curl_error($ch), 0);
                }
                self::$requestNum += 1;
                //重新请求
                self::curl($url, $postFields,$reNum ,$connettimeout,$timeout);
            }

        } catch (Exception $e) {
            $reponse = null;
        }
        curl_close($ch);
        return $reponse;
    }
		
	
	static private function get_include_file_result($url,$postFields=[]) {
		if(empty($url)) {
			self::$error = '请求地址为空!';
			return false;
		}else{
			 $tempArr = explode('/',ltrim($url,'/'));
//			 $model = 'api';
//			 if(count($tempArr) == 2 && $tempArr[0] == 'api') {
//				$file = APPROOT.DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR . $model . DIRECTORY_SEPARATOR . $tempArr[1] . '.php';
//				$fileClass = $tempArr[1];
//			 }elseif(count($tempArr) == 3 && $tempArr[1] == 'api'){
//				$file = WEBROOT.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$tempArr[0].DIRECTORY_SEPARATOR.'model'.DIRECTORY_SEPARATOR . $model . DIRECTORY_SEPARATOR . $tempArr[2] . '.php';
//				$fileClass = $tempArr[2];
//			 }else{
//				self::$error = '请求api错误!';
//				return false;
//			 }
			//指定文件夹访问
			$allowDir = ['api','apiFlow'];
			if((count($tempArr) == 2 && in_array($tempArr[0],$allowDir))) {
				$file = APPROOT.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR . $tempArr[0] . DIRECTORY_SEPARATOR . $tempArr[1] . '.php';
				$fileClass = $tempArr[1];
				$model =  $tempArr[0];
			}elseif((count($tempArr) == 3 && $tempArr[1] == 'api')||(count($tempArr) == 3 && $tempArr[1] == 'apiFlow')){
				$file = WEBROOT.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.$tempArr[0].DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR . $tempArr[1] . DIRECTORY_SEPARATOR . $tempArr[2] . '.php';
				$fileClass = $tempArr[2];
				$model =  $tempArr[1];
			}else{
				self::$error = '请求api错误!';
				return false;
			}
			 if(!is_file($file)) {
				self::$error = '请求地址不存在!';
				return false;
			 }else{
				$postFields = !empty($postFields) && is_array($postFields) ? $postFields : [];
				$action = str_replace('.', '_', $fileClass);
				$postFields['PATH_ACTION'] = $action;
				$postFields['PATH_MODEL'] = $model;
				ob_start();
				!defined('INTERNAL_CALL') && define('INTERNAL_CALL', true); //设置来至内部调用
				require_once($file);
				$result = '';
				$obj = new $action($postFields);
				$obj->run();
				$result = ob_get_contents();
				ob_end_clean();
				return $result;
			}	 
		}
	}
    /**
     * 数组转换json格式  -- bayayun 2016-05-30
     * @param $code string 编码标识
     * @param $data  array 数据转换
     */
    static function apiCallback($code, $data = array()) {
        echo json_encode(array("code" => $code, "data" => $data));        
    }
	/* 解析接口返回的数据 */
	static public function decode($data){
		$data = json_decode($data, true);
		if(!is_array($data)){
			$data = ['code' => '1002', 'data' => '接口返回数据错误'];
		}
		return $data;
	}
	
	static public function getError(){
		return self::$error;
	}

}

?>
