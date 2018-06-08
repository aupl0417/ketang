<?php

require(FRAMEROOT . '/lib/beanstalk/src/Client.php'); //加载beanstalk

class queues {

    static private $cursize;
    static private $size = 20;

    static function getInstance() {
        $beanstalk = new \Beanstalk\Client(['persistent' => false, 'host' => '10.0.0.52', 'port' => 11301, 'timeout' => 3]);
        if (!$beanstalk->connect()) {
            exit(current($beanstalk->errors()));
        }
        return $beanstalk;
    }

    static function setRunFlag($flag) {
        $redis = new myRedis();
        $redis->obj->set('queues.state', $flag);
        $redis->close();
    }

    static function getRunFlag() {
        $redis = new myRedis();
        return $redis->obj->get('queues.state');
        $redis->close();
    }

    static function start($tubes) {
			
        self::setRunFlag(1);
        if (!is_array($tubes)) {
            self::runQueues($tubes);
        } else {
            foreach ($tubes as $v) {
                self::runQueues($v);
            }
        }
    }

    static function stop($tubes) {
        self::setRunFlag(0);
        if (!is_array($tubes)) {
            self::producter($tubes, 'end123456', '', 0);
        } else {
            foreach ($tubes as $v) {
                self::producter($v, 'end123456', '', 0);
            }
        }
    }

    static function runQueues($tube) {
        $producerPid = pcntl_fork();
        if ($producerPid == -1) {
            die("could not fork");
        } elseif ($producerPid) {// main
            while (true) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    die("could not fork");
                } elseif ($pid) {// main
                    self::$cursize++;
                    if (self::$cursize >= self::$size) {
                        $sunPid = pcntl_wait($status, WUNTRACED);
                        if (!self::getRunFlag()) {
                            break;
                        }
                        self::$cursize--;
                    }
                } else {// sub work
                    //$this->worker();
                    queues::work($tube);
                    exit(0);
                }
            }
            exit(0);
        } else {// sub
            //$this->producter();
            exit(0);
        }
    }

    static function producter($tube, $func, $param, $level = 1024, $wait = 0) {
        $beanstalk = self::getInstance();
        $beanstalk->useTube($tube);
        //往tube中增加数据
        $put = $beanstalk->put(
                $level, // 任务的优先级.
                $wait, // 不等待直接放到ready队列中.
                60, // 处理任务的时间.
                serialize(array($func, $param)) // 任务内容
        );
		
        if (!$put) {
			return false;
            //exit('commit job fail');
        }
		self::log($put, $func, $param, $level, $wait, 0);
        $beanstalk->disconnect();
		return true;
    }

    static function work($tube) {
		
        $beanstalk = self::getInstance();
		
        //设置要监听的tube
        $beanstalk->watch($tube);
        //取消对默认tube的监听，可以省略
        $beanstalk->ignore('default');
		
        while (true) {
            //获取任务，此为阻塞获取，直到获取有用的任务为止
            $job = $beanstalk->reserve(); //返回格式array('id' => 123, 'body' => 'hello, beanstalk')
            //处理任务

            $info = unserialize($job['body']);

            if ($info[0] == 'shutdown') {
				//self::log($tube, $info[0], $info[1], 0, 0, 1);	
                $beanstalk->delete($job['id']);
                break;
            }
			/* 根据返回的job的执行结果进行下一步操作
			 * return job执行结果
			 * 1:处理成功 删除任务
			 * -1:严重错误(例如数据不存在),删除job
			 * -2:顺延1.1倍时间再次执行,直至延迟时间达到 86400s * 14,并且将任务的优先级-1直至0,则休眠该job
			 * -3:休眠任务,暂无唤醒机制
 			*/
			$result = job::exec($info[0], $info[1]);
			$jobStats = queues::statsJob($job['id']);
			self::log($job['id'], $info[0], $info[1], $jobStats['pri'], $jobStats['delay'], $result);	//记录本次执行结果
			switch($result){
				case 1:
				case -1:
					
					$beanstalk->delete($job['id']);
					break;
				case -2:
					if($jobStats['delay'] >= 86400 * 14){//延迟执行时间超过86400s * 14则删除该job
						$beanstalk->delete($job['id'], 0);
						$result = -99;
					}else{
						/* 当延迟时间为0或者升级推荐奖励延迟时间(7*86400+60) */
						$dbErp = new MySql(DB_ERP);//erp数据库链接
						$upgradeAwardTime = attrib::getSystemParaByKey('upgrade_award_time', 0, $dbErp);//用户升级推荐奖励计算时间(s)
						$upgradeAwardTime += 60;
						if($jobStats['delay'] == 0 || $jobStats['delay'] == $upgradeAwardTime){
							$jobStats['delay'] = 10;
						}else{
							$jobStats['delay'] = floor($jobStats['delay'] * 1.1);
						}
						--$jobStats['pri'];
						$jobStats['pri'] = $jobStats['pri'] < 0 ? 0 : $jobStats['pri'];
						
						self::producter('erp', $info[0], $info[1], $jobStats['pri'], $jobStats['delay']);
						$beanstalk->delete($job['id']);
						//$beanstalk->release($job['id'], $jobStats['pri'], $jobStats['delay']);
						
					}
					break;
				case -3:
				default:
					$beanstalk->bury($job['id'], 0);
					break;
			}
        }
        $beanstalk->disconnect();
    }

    static function getStatusByTubeName($tube = '') {
        $beanstalk = self::getInstance();
        if ($tube == '') {
            foreach ($beanstalk->listTubes() as $v) {
                $job[] = $beanstalk->statsTube($v);
            }
        } else {
            $job = $beanstalk->statsTube($tube);
        }
        return $job;
    }

    static function getInfoByTubeStatus($tube, $field) {
        $job = self::getStatusByTubeName($tube);
        return $job[$field];
    }

    static function clear() {
        $beanstalk = self::getInstance();
        foreach ($beanstalk->listTubes() as $tube) {
            $beanstalk->useTube($tube);

            while ($job = $beanstalk->peekReady()) {
                $beanstalk->delete($job['id']);
            }
            while ($job = $beanstalk->peekBuried()) {
                $beanstalk->delete($job['id']);
            }
        }
        $beanstalk->useTube('default');
    }
	
	/* 获取指定job的信息 */
	static function statsJob($id) {
		$beanstalk = self::getInstance();	
		return $beanstalk->statsJob($id);
	}	

    static function log($put, $func, $param, $level, $wait, $result) {
		$data = [
			'job'	   => $func.'-'.$param[0],
			'put'	   => $put,
			'func'	   => $func,
			'param'	   => $param,
			'level'	   => $level,
			'wait'	   => $wait,
			'result'   => $result,//执行结果
			'time'	   => time(),//时间			
		];
		$mongo = new mgdb();		
		
		return $mongo->insert('queuelog', $data);			
		
        //echo F::mytime() . '  ' . $m . "\n";
    }

}
