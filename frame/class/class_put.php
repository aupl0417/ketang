<?php

/**
 * 提成嘉奖/升级奖励/代理奖励加入队列的操作
 * adadsa
 * 2016-07-24
 */
class put {

	//错误码
	static public $errNum = null;
	
	/* 分账货款延时到账 */
	static function paymentDelay($orderid){
		if(!$orderid){
			return false;
		}
		$paymentToSellerDelayTime = attrib::getSystemParaByKey('payment_to_seller_delay_time');//分账货款延迟到账时间(s)
		return queues::producter('erp', 'taskPaymentToSeller', [$orderid], 1024, $paymentToSellerDelayTime + 60);//延迟60s防止服务器时间不同步
	}	

	/* 提成嘉奖加入队列 */
	static function award($orderid){
		if(!$orderid){
			return false;
		}
		return queues::producter('erp', 'taskAward', [$orderid], 1024, 0);
	}
	
	/* 升级奖励加入队列 */
	static function upgrade($orderid){		
		$upgradeAwardTime = attrib::getSystemParaByKey('upgrade_award_time');//用户升级推荐奖励计算时间(s)
		$data = ['erp', 'taskUpAward', $orderid, 512, $upgradeAwardTime + 60];
		
		if(!$orderid){
			return false;
		}
		return queues::producter('erp', 'taskUpAward', [$orderid], 512, $upgradeAwardTime + 60);//升级推荐奖励7天后计算,延迟60s防止服务器时间不同步
	}
	
	/* 代理奖励加入队列 */
	static function agent($orderid){
		//return true;		
		$upgradeAwardTime = attrib::getSystemParaByKey('upgrade_award_time');//用户升级推荐奖励计算时间(s)
		
		if(!$orderid){
			return false;
		}
		return queues::producter('erp', 'taskAgentAward', [$orderid], 512, $upgradeAwardTime + 60);//升级推荐奖励7天后计算,延迟60s防止服务器时间不同步
	}
	
	/* 异步通知加入队列 */
	static function notify($orderid){
		if(!$orderid){
			return false;
		}
		return queues::producter('erp', 'taskNotice', [$orderid], 1024, 0);//
	}

	/* 发送邮件放队列 */
	static function sendmail($email,$nick, $tempId = 1,$data = array()){
		if (!F::isEmail($email)) {
			self::$errNum = -1;
			return false;
		}
		if(!F::isNotNull($nick)){
			self::$errNum = -4;
			return false;
		}

		//防止频繁发送，间隔需要120秒(放在cache里)
		$cache = new cache();
		$cacheCode = $cache->get('mailCode_'.$email);

		if (!$cacheCode) {   //如果此号码没有发送码记录，则set
			$code = rand(100000, 999999);
			$info = array('code'=>$code,'ctime'=>time());
			$cache->set('mailCode_'.$email, $info, 60*60*24);
		}else {     //有缓存信息表示SMS_SENDINTERVAL时间内多次操作
			if(($cacheCode['ctime'] + 180) > time()){
				self::$errNum = -2;//发送过于频繁
				return false;
			}else{
				//重新发送，就重新生成验证码
				$code = rand(100000, 999999);
				$info = array('code'=>$code,'ctime'=>time());
				$cache->set('mailCode_'.$email, $info, 60*60*24);    //验证码保存24小时
			}
		}

		//参数过滤
		switch ($tempId){
			case 3:
				if(!isset($data['url'])){
					self::$errNum = -4;//参数错误
					return false;
				}
				break;
			case 4:
				if(!isset($data['url'])){
					self::$errNum = -4;//参数错误
					return false;
				}
				break;
			case 5:
				if(!isset($data['content'])){
					self::$errNum = -4;//参数错误
					return false;
				}
				break;
		}
		
		return queues::producter('erp', 'taskSendmail', [$email.'-'.$tempId,$email,$nick,$code, $tempId, $data], 1024, 0);
	}
	
	/* 获取用户所有下级加入队列 */
	static function getAllChildren($uid, $maxLevel = 10){
		if(!$uid){
			return false;
		}
		return queues::producter('erp', 'taskGetAllChildren', [$uid, $maxLevel], 512, 1);
	}
}

?>