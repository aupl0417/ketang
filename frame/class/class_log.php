<?php

/* ClassName: log
 * Memo:日志类
 * Version:1.1.0.0
 * EditTime:2010-10-19
 * Writer:flybug
 * @modified 2015年1月22日
 * 
 * 需要添加记录到数据库的功能
 * 
 * */

class log {

    private $mode; //取值file-记录到文件，db-记录到数据库

    //递归创建多级文件夹

    public function creatDir($newPath) {
        if (!is_dir($newPath)) {
            if (self::creatdir(dirname($newPath))) {
                return mkdir($newPath, 0777);
            }
        } else {
            return true;
        }
    }

    /*
     * 写入日志文件
     * $memo  写入的内容
     * $sign  日志名称标志前缀
     * $user  写入者
     */

    static public function writeLog($memo, $sign = '', $writer = '') {
        $filePath = PATH_LOG . '/' . $sign; //得到log存储路径		
        if (!file_exists($filePath)) {
            log::creatdir($filePath);
        }//路径不存在就递归创建
        $logfile = "$filePath/" . date('Ymd', time()) . '.log';
        $str = F::mytime() . " || " . $writer . " || " . F::getip() . " || " . $memo;
        file_put_contents($logfile, $str . PHP_EOL, FILE_APPEND);
    }
	
	/* 
	 * 写入mongodb日志
	 * $type_id //行为id
	 * $table //记录的table,如果一个操作影响了多个表,那么只记录主变动的表和id,权限id,通过这些在程序中判断还影响了哪些数据
	 * $r_id //记录的id
	 * $change //变动的数据
	 * example: log::writeLogMongo(60202, 't_auth', $options['id'], $update);
	*/
	static public function writeLogMongo($type_id = 9999, $table = '', $r_id = '', $change = [], $user = '', $userType = 0, $connection = 'edu_logs') {
		//return true;
		$data = [
			'log_type_id'	   => $type_id - 0,
			'log_table'	 	   => $table,
			'log_r_id'		   => $r_id.'',
			'log_user'		   => isset($_SESSION['userID']) ? $_SESSION['userID'] : $user,//操作者id
			'log_userType'	   => isset($_SESSION['userType']) ? $_SESSION['userType'] : $userType,//操作者类型1:用户;2:雇员
			'log_time'		   => time(),//时间
			'log_microTime'	   => F::getMicrotime(),//时间
			//'log_server_ip'	   => F::getServerIp(),//ip
			'log_ip'		   => F::GetIP(),//ip
			'log_code'		   => F::getTimeMarkID(),//编号,用于排序
		];
		if($change){
			$data['log_change'] = $change;
		}
		$mongo = new mgdb();
		
		
		return $mongo->insert($connection, $data);
			
		
		
	}
	
	/* 
	 * 记录所有访问日志
	 * $options
	 * 
	 */
	static public function access($options){
		$ip = F::GetIP();
		if($ip == '127.0.0.1' || preg_match("/^10\.0\./", $ip) || preg_match("/^10\.10\./", $ip)){//
			return false;
		}
		else{
			$insert = [];
			if(isset($options['PATH_INFO'])){
				$insert['PATH_INFO'] = $options['PATH_INFO'];
				unset($options['PATH_INFO']);
			}
			if(isset($options['PATH_MODEL'])){
				$insert['PATH_MODEL'] = $options['PATH_MODEL'];
				unset($options['PATH_MODEL']);				
			}
			if(isset($options['PATH_ACTION'])){
				$insert['PATH_ACTION'] = $options['PATH_ACTION'];
				unset($options['PATH_ACTION']);				
			}
			$insert['options'] = json_encode($options);
			
			$mongo = new mgdb();
			
			if(preg_match("/\(CAST\(|SCHEMATA|SELECT\s|update\s|IFNULL|passwd/i", $insert['options'])){
				$danger['log_user']		   = isset($_SESSION['userID']) ? $_SESSION['userID'] : '';//操作者id
				$danger['log_userType']	   = isset($_SESSION['userType']) ? $_SESSION['userType'] : 0;//操作者类型1:会员;2:雇员
				$danger['log_time']		   = time();//时间
				$danger['options']		   = $insert['options'];//传递参数
				$danger['log_microTime']   = F::getMicrotime();//时间
				$danger['log_ip']		   = $ip;//ip
				$danger['log_code']		   = F::getTimeMarkID();//编号,用于排序
				$danger['HTTP_HOST']	   = $_SERVER['HTTP_HOST'];//host
				$mongo->insert('danger_log', $danger);
				die;
				//return false;
			}
			
			$insert['log_user']		   = isset($_SESSION['userID']) ? $_SESSION['userID'] : '';//操作者id
			$insert['log_userType']	   = isset($_SESSION['userType']) ? $_SESSION['userType'] : 0;//操作者类型1:用户;2:雇员
			$insert['log_time']		   = time();//时间
			$insert['log_microTime']   = F::getMicrotime();//时间
			$insert['log_ip']		   = $ip;//ip
			$insert['log_code']		   = F::getTimeMarkID();//编号,用于排序
			$insert['HTTP_HOST']	   = $_SERVER['HTTP_HOST'];//host
			//dump($options);die;
			$connection = 'access_'.F::mytime('Ymd');
			return $mongo->insert($connection, $insert);
		}
	}

	

    public function readLog($logfile) {
        return file_exists($logfile) ? file_get_contents($logfile) : '';
    }

}

?>
