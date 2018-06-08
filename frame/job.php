<?php

/*
 * Version : 1.0.0
 * Author  : flybug
 * Comment : 2015-12-22
 */

require("config.php"); //数据库类

echo "\n-------------------------------------------\n";
job::log('任务执行开始');
switch ($argv[2]) {
    case '1':
		//job::startWorks();
		break;
    case '2'://计算每日积分转唐宝
		job::runW2R();
		break;
    case '3'://计算每日收益
		job::runIncome();
		//        $date = date('Y-m-d',strtotime(F::mytime('Y-m-d')) - 86400);
		//        if (score::getTodayStatistics($date)) {
		//            job::log($date . "每日白积分转红积分生成任务执行成功");
		//        } else {
		//            job::log($date . "每日白积分转红积分生成任务执行失败");
		//        }
		//        job::log(score::$error);
		//        $sms = new sms();
		//        $sms->SendTemplateSMS('18607120110', '', ['tempId' => 999]);
		break;
}
/* score::getTodayStatistics('2016-07-17');
echo $argv[1] . ' - ' . $argv[2]; */

//1 0 * * * /usr/local/php/bin/php /home/wwwroot/dttx.com/frame_bak/test.php XCVBNMOUY456 2 >> /home/wwwroot/dttx.com/frame_bak/doMission.log
//31 0 * * * /usr/local/php/bin/php /home/wwwroot/dttx.com/frame_bak/test.php XCVBNMOUY456 3 >> /home/wwwroot/dttx.com/frame_bak/doMission.log
//ob_end_flush();
///usr/bin/php /home/web/mail.php
///usr/bin/php /home/wwwroot/dttx.com/frame_bak/mail.php XCVBNMOUY456 2  >> /home/wwwroot/dttx.com/frame_bak/doMission.log