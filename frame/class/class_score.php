<?php

class score {
    /*
     * 白积分到红积分的每日返还
     * getWhiteToRedTable：得到每日返还的单元数据表（超过10000分才参与）
     * getTodayStatistics：得到每日统计数据
     * whiteScoreToRedScore：按照没覅返还单元数据表进行返还
     */
	
    static public $error;

    //得到每日返还的单元数据表
    static function getWhiteToRedTable() {
        $db = new MySql();
		$dbErp = new MySql(DB_ERP);
		
        if ($db->getField('SELECT COUNT(*) FROM pay_whitetored_return WHERE wrr_isProgress = 0') != 0) {
            self::$error = -10;
            return false;
        }
        $db->exec('TRUNCATE pay_whitetored_return');
        if ($db->getField('SELECT COUNT(*) FROM pay_whitetored_return') != 0) {
            self::$error = -11;
            return false;
        }
        $returnUnionNumber = attrib::getSystemParaByKey('w2r_ReturnUnionBaseScore', 1, $dbErp);
        $dataFileName = 'data' . date('YmdHis', time()) . '.txt';
        $db->exec("SELECT a_uid,FLOOR(a_score / $returnUnionNumber) INTO OUTFILE '$dataFileName' FROM pay_account WHERE a_state='1' AND a_score >= $returnUnionNumber");
        $db->exec("LOAD DATA INFILE '$dataFileName' INTO TABLE pay_whitetored_return (wrr_uid,wrr_returnUnit)");

        return true;
    }

    //得到每日统计数据
    static function getTodayStatistics($calcdate) {
        if (!self::getWhiteToRedTable()) {
            self::$error = -1;
            return false;
        }

        $yesterday = strtotime($calcdate) - 86400;
		$calcdateStr = str_replace('-', '', $calcdate);
        $db = new MySql();		
		$dbErp = new MySql(DB_ERP);//erp数据库
        //获取统计日期前一天数据
        $sql = "select * from t_statistics_system where ss_id = '" . $yesterday . "' limit 1";
        $prev = $dbErp->getRow($sql);
        if ($prev == array()) {
            self::$error = -2;
            return false;
        }

        //只要有一个返还被处理，则不允许再更新此表
        $sql = 'select count(*) as num from pay_whitetored_return where wrr_isProgress = 1';
        if ($db->getField($sql) > 0) {
            self::$error = -3;
            return false;
        }

        //获取入池和出池百分比
        $p['ss_wrTodayPoolInPer'] = attrib::getSystemParaByKey('w2r_wrTodayPoolInPer', 1, $dbErp);
        $p['ss_wrTodayPoolOutPer'] = attrib::getSystemParaByKey('w2r_wrTodayPoolOutPer', 1, $dbErp);
        $p['ss_wrTodayReturnUnionBaseScore'] = attrib::getSystemParaByKey('w2r_ReturnUnionBaseScore', 1, $dbErp);

        //统计当天的5账户总量a_freeMoney, a_frozenMoney, a_score, a_tangBao, a_storeScore
        $sql = "SELECT SUM(a_freeMoney) AS `free`,SUM(a_frozenMoney) AS `frozen`,SUM(a_score) AS `score`,SUM(a_tangBao) AS `tangBao`,SUM(a_storeScore) AS `store` FROM pay_account WHERE a_id > 1";//1586235456216477
        $row = $db->getRow($sql);
        $p['ss_totalFree'] = $row['free'];
        $p['ss_totalFrozen'] = $row['frozen'];
        $p['ss_totalScore'] = $row['score'];
        $p['ss_totalTangBao'] = $row['tangBao'];
        $p['ss_totalStore'] = $row['store'];
		
		//统计当天的5账户总量 - 排除测试数据
        $sql = "SELECT SUM(a_freeMoney) AS `free`,SUM(a_frozenMoney) AS `frozen`,SUM(a_score) AS `score`,SUM(a_tangBao) AS `tangBao`,SUM(a_storeScore) AS `store` FROM pay_account WHERE a_isTest = 0";//1586235456216477
        $row = $db->getRow($sql);
        $p['ss_totalFreeReal'] = $row['free'];
        $p['ss_totalFrozenReal'] = $row['frozen'];
        $p['ss_totalScoreReal'] = $row['score'];
        $p['ss_totalTangBaoReal'] = $row['tangBao'];
        $p['ss_totalStoreReal'] = $row['store'];
		
		//

        //获取累计用户量
        $p['ss_totalMem'] = 0;
        $p['ss_totalMemL1'] = 0;
        $p['ss_totalMemL2'] = 0;
        $p['ss_totalMemL3'] = 0;
        $p['ss_totalMemL4'] = 0;
        $sql = "SELECT count(*) FROM t_user WHERE u_createTime <= '$calcdate 23:59:59'";
        $p['ss_totalMem'] = $dbErp->getField($sql);
        $sql = "SELECT u_level,COUNT(*) as num FROM t_user WHERE u_upgradeTime <= '$calcdate 23:59:59' GROUP BY u_level";
        $row = $dbErp->getAll($sql);
        foreach ($row as $v) {
            $p['ss_totalMemL' . $v['u_level']] = $v['num'];
        }
        unset($p['ss_totalMemL5']);

        //累计个人用户、企业用户、联盟商家、开发者、代理公司、GP、LP
        $sql = "SELECT "
                . "(SELECT COUNT(*) FROM t_user WHERE u_type = 0 AND u_createTime <= '$calcdate 23:59:59') AS a,"
                . "(SELECT COUNT(*) FROM t_user WHERE u_type = 1 AND u_createTime <= '$calcdate 23:59:59') AS b,"
                . "(SELECT COUNT(*) FROM t_user_company WHERE u_isUnionSeller = 1 AND u_unionTime <= '$calcdate 23:59:59') AS c,"
                . "(SELECT COUNT(*) FROM t_user_company WHERE u_isDevelopParter = 1 AND u_developTime <= '$calcdate 23:59:59') AS d,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'A' AND cgl_createTime <= '$calcdate 23:59:59') AS f,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'B' AND cgl_createTime <= '$calcdate 23:59:59') AS g,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'C' AND cgl_createTime <= '$calcdate 23:59:59') AS h,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'D' AND cgl_createTime <= '$calcdate 23:59:59') AS i,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'E' AND cgl_createTime <= '$calcdate 23:59:59') AS j,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'F' AND cgl_createTime <= '$calcdate 23:59:59') AS k";
        //print_r($sql);exit;
        $row = $dbErp->getRow($sql);
        $p['ss_totalMemPerson'] = $row['a'];
        $p['ss_totalMemCom'] = $row['b'];
        $p['ss_totalUnionSeller'] = $row['c'];
        $p['ss_totalDevelopParter'] = $row['d'];
        $p['ss_totalA'] = $row['f'];
        $p['ss_totalB'] = $row['g'];
        $p['ss_totalC'] = $row['h'];
        $p['ss_totalD'] = $row['i'];
        $p['ss_totalE'] = $row['j'];
        $p['ss_totalF'] = $row['k'];

        //获取累计代理公司总数量和分级别数量
        $p['ss_totalCompany'] = 0;
        $p['ss_totalCompanyL1'] = 0;
        $p['ss_totalCompanyL2'] = 0;
        $p['ss_totalCompanyL3'] = 0;
        $p['ss_totalCompanyL4'] = 0;
        $p['ss_totalCompanyL5'] = 0;
        $p['ss_totalCompanyL6'] = 0;
        $p['ss_totalCompanyL7'] = 0;
        $p['ss_totalCompanyL8'] = 0;
        $sql = "SELECT com_level, COUNT(*) as num FROM t_company WHERE com_createTime <= '$calcdate 23:59:59' AND com_level > 0 AND com_level < 8 GROUP BY com_level";
        $row = $dbErp->getAll($sql);
        foreach ($row as $v) {
            $p['ss_totalCompanyL' . $v['com_level']] = $v['num'];
            $p['ss_totalCompany'] += $v['num'];
        }

        //获取今日用户量
        $p['ss_todayMem'] = 0;
        $p['ss_todayMemL1'] = 0;
        $p['ss_todayMemL2'] = 0;
        $p['ss_todayMemL3'] = 0;
        $p['ss_todayMemL4'] = 0;
        $sql = "SELECT count(*) FROM t_user WHERE u_createTime LIKE '$calcdate%'";
        $p['ss_todayMem'] = $dbErp->getField($sql);
        $sql = "SELECT u_level,COUNT(*) as num FROM t_user WHERE u_upgradeTime LIKE '$calcdate%' GROUP BY u_level";
        $row = $dbErp->getAll($sql);
        foreach ($row as $v) {
            $p['ss_todayMemL' . $v['u_level']] = $v['num'];
        }

        //今日个人用户、企业用户、联盟商家、开发者、代理公司、GP、LP
        $sql = "SELECT "
                . "(SELECT COUNT(*) FROM t_user WHERE u_type = 0 AND u_createTime LIKE '$calcdate%') AS a,"
                . "(SELECT COUNT(*) FROM t_user WHERE u_type = 1 AND u_createTime LIKE '$calcdate%') AS b,"
                . "(SELECT COUNT(*) FROM t_user_company WHERE u_isUnionSeller = 1 AND u_unionTime LIKE '$calcdate%') AS c,"
                . "(SELECT COUNT(*) FROM t_user_company WHERE u_isDevelopParter = 1 AND u_developTime LIKE '$calcdate%') AS d,"
                . "(SELECT COUNT(*) FROM t_company WHERE com_createTime LIKE '$calcdate%') AS e,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'A' AND cgl_createTime LIKE '$calcdate%') AS f,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'B' AND cgl_createTime LIKE '$calcdate%') AS g,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'C' AND cgl_createTime LIKE '$calcdate%') AS h,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'D' AND cgl_createTime LIKE '$calcdate%') AS i,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'E' AND cgl_createTime LIKE '$calcdate%') AS j,"
                . "(SELECT COUNT(*) FROM t_company_gplp WHERE cgl_type = 'F' AND cgl_createTime LIKE '$calcdate%') AS k";
        //dump($sql);exit;
        $row = $dbErp->getRow($sql);
        $p['ss_todayMemPerson'] = $row['a'];
        $p['ss_todayMemCom'] = $row['b'];
        $p['ss_todayUnionSeller'] = $row['c'];
        $p['ss_todayDevelopParter'] = $row['d'];
        $p['ss_todayCompany'] = $row['e'];
        $p['ss_todayA'] = $row['f'];
        $p['ss_todayB'] = $row['g'];
        $p['ss_todayC'] = $row['h'];
        $p['ss_todayD'] = $row['i'];
        $p['ss_todayE'] = $row['j'];
        $p['ss_todayF'] = $row['k'];
		
        //红白积分转换
        $p['ss_wrTodayNewWhiteScore'] = bcsub($p['ss_totalScore'], (bcsub($prev['ss_totalScore'], $prev['ss_wrReturnScore'], 4)), 4); //今日总量白积分-（昨日总量-昨日返还量）=今日新增白积分
        $p['ss_wrTodayPoolScore'] = bcadd($prev['ss_wrNotReturnScore'], bcmul($p['ss_wrTodayNewWhiteScore'], $p['ss_wrTodayPoolInPer'], 4), 4);

        $p['ss_wrReturnScore'] = bcmul($p['ss_wrTodayPoolScore'], $p['ss_wrTodayPoolOutPer'], 4);
        $p['ss_wrNotReturnScore'] = bcmul($p['ss_wrTodayPoolScore'], (1 - $p['ss_wrTodayPoolOutPer']), 4);
        $p['ss_wrTotalReturnScore'] = bcadd($prev['ss_wrTotalReturnScore'], $p['ss_wrReturnScore'], 4);
        $p['ss_wrTotalUseRedScore'] = bcadd($prev['ss_wrTotalUseRedScore'], bcsub(bcadd($prev['ss_totalTangBao'], $prev['ss_wrReturnScore'], 4), $p['ss_totalTangBao'], 4), 4); //昨日累计红积分使用及转换的总量+（今日红积分总量-（昨日红积分总量+昨日返还量））=今日累计红积分使用及转换的总量

        $sql = 'SELECT SUM(wrr_returnUnit) FROM pay_whitetored_return';
        $p['ss_wrReturnUnionTotal'] = $db->getField($sql);
        $p['ss_wrUnionScore'] = bcdiv($p['ss_wrReturnScore'], $p['ss_wrReturnUnionTotal'], 4);
		job::log("实际每单元转换唐宝数为" . $p['ss_wrUnionScore']);
		$p['ss_wrUnionScore'] = max(5.0125, $p['ss_wrUnionScore']);
		$p['ss_wrUnionScore'] = min(5.2631, $p['ss_wrUnionScore']);
		//$p['ss_wrUnionScore'] = 5;

        $p['ss_id'] = strtotime($calcdate);
        $p['ss_date'] = $calcdate;
        $p['ss_createTime'] = F::mytime();
        $p['ss_isPublish'] = 1;
		
		//当天新增库存积分
		$sql = "SELECT SUM(sc_score) FROM `pay_account_store_tran` WHERE sc_id LIKE '".$calcdateStr."%' AND sc_type=1";
		$p['ss_todayStore'] = $db->getField($sql);
		$p['ss_todayStore'] = $p['ss_todayStore'] - 0;
		
		//当天分账收益额（扣货款的8%）		
		$sql = "SELECT SUM(ca_money) FROM `pay_account_cash_tran` WHERE ca_businessId='10220' AND ca_id LIKE '".$calcdateStr."%' AND ca_type=1";
		$p['ss_todaySubStore'] = $db->getField($sql);
		$p['ss_todaySubStore'] = $p['ss_todaySubStore'] - 0;
		
		//当天在线充值总额 
		$sql = "SELECT SUM(ci_money) FROM `pay_account_cash_in` WHERE ci_successTime LIKE '".$calcdate."%' AND ci_state=1 AND ci_uid NOT IN (SELECT a_uid FROM pay_account WHERE a_isTest=1)";
		$ss_todayOnlineRecharge = $db->getField($sql);
		$p['ss_todayOnlineRecharge'] = $ss_todayOnlineRecharge - 0;
		
		//当天银行卡转账到账总额
		$sql = "SELECT SUM(bt_money) FROM pay_bank_transfer WHERE bt_state=1 AND bt_arriveTime LIKE '".$calcdate."%' AND bt_uid NOT IN (SELECT a_uid FROM pay_account WHERE a_isTest=1)";
		$ss_todayBankRecharge = $db->getField($sql);
		$p['ss_todayBankRecharge'] = $ss_todayBankRecharge - 0;
		
		//当天现场业务总额
		$sql = "SELECT SUM(fre_money) FROM pay_field_recharge WHERE fre_createTime LIKE '".$calcdate."%' AND fre_uid NOT IN (SELECT a_uid FROM pay_account WHERE a_isTest=1)";
		$ss_todayFieldRecharge = $db->getField($sql);
		$p['ss_todayFieldRecharge'] = $ss_todayFieldRecharge - 0;		
		
		$p['ss_todayRecharge'] = $p['ss_todayOnlineRecharge'] + $p['ss_todayBankRecharge'] + $p['ss_todayFieldRecharge'];
		
		
		//当天提现申请总额
		$sql = "SELECT SUM(co_money) FROM pay_account_cash_out WHERE co_caid LIKE '".$calcdateStr."%'";
		$p['ss_todayCashout'] = $db->getField($sql);
		$p['ss_todayCashout'] = $p['ss_todayCashout'] - 0;
		
		//当天提现完成总额
		$sql = "SELECT SUM(co_money) FROM pay_account_cash_out WHERE co_arriveDateTime LIKE '".$calcdate."%' AND co_state=1";
		$p['ss_todayCashoutFinished'] = $db->getField($sql);
		$p['ss_todayCashoutFinished'] = $p['ss_todayCashoutFinished'] - 0;

        return $dbErp->insert('t_statistics_system', $p) == 1;

//        if ($db->insert('t_statistics_system', $p) == 1) {
//            jobW2R(10, $p['ss_wrUnionScore']);
//        } else {
//            echo "生成统计表失败 \n";
//        }
    }

    //更新指定日期的日返还统计表
    static function updateTodayStatistics($calcdate, $inper = 0, $outper = 0) {
        $yesterday = strtotime($calcdate) - 86400;
        $db = new MySql();
        //获取统计日期前一天数据
        $sql = "select * from t_statistics_system where ss_id = '" . $yesterday . "' limit 1";
        $prev = $db->getRow($sql);
        if ($prev == array()) {
            self::$error = -5;
            return false;
        }

        //只要发布了，则不允许再更新此表
        $sql = "select count(*) as num from t_statistics_system where ss_id = '" . strtotime($calcdate) . "' and ss_isPublish = 0";
        if ($db->getField($sql) == 0) {
            self::$error = -6;
            return false;
        }

        //只要有一个返还被处理，则不允许再更新此表
        $sql = 'select count(*) as num from pay_whitetored_return where wrr_isProgress = 1';
        if ($db->getField($sql) != 0) {
            self::$error = -7;
            return false;
        }

        //获取入池和出池百分比
        $p['ss_wrTodayPoolInPer'] = ($inper == 0) ? attrib::getSystemParaByKey('w2r_wrTodayPoolInPer') : $inper;
        $p['ss_wrTodayPoolOutPer'] = ($outper == 0) ? attrib::getSystemParaByKey('w2r_wrTodayPoolOutPer') : $outper;

        //统计当天的8账户总量
        $sql = "select ss_totalWhiteScore,ss_totalRedScore from t_statistics_system where ss_id = '" . strtotime($calcdate) . "'";
        $row = $db->getRow($sql);
//        $p['ss_totalWhiteScore'] = $row['ss_totalWhiteScore'];
//        $p['ss_totalRedScore'] = $row['ss_totalRedScore'];
        //红白积分转换
        $p['ss_wrTodayNewWhiteScore'] = bcsub($row['ss_totalWhiteScore'], (bcsub($prev['ss_totalWhiteScore'], $prev['ss_wrReturnScore'], 4)), 4); //今日总量白积分-（昨日总量-昨日返还量）=今日新增白积分
        $p['ss_wrTodayPoolScore'] = bcadd($prev['ss_wrNotReturnScore'], bcmul($p['ss_wrTodayNewWhiteScore'], $p['ss_wrTodayPoolInPer'], 4), 4);

        $p['ss_wrReturnScore'] = bcmul($p['ss_wrTodayPoolScore'], $p['ss_wrTodayPoolOutPer'], 4);
        $p['ss_wrNotReturnScore'] = bcmul($p['ss_wrTodayPoolScore'], (1 - $p['ss_wrTodayPoolOutPer']), 4);
        $p['ss_wrTotalReturnScore'] = bcadd($prev['ss_wrTotalReturnScore'], $p['ss_wrReturnScore'], 4);
        $p['ss_wrTotalUseRedScore'] = bcadd($prev['ss_wrTotalUseRedScore'], bcsub(bcadd($prev['ss_totalRedScore'], $prev['ss_wrReturnScore'], 4), $p['ss_totalRedScore'], 4), 4); //昨日累计红积分使用及转换的总量+（今日红积分总量-（昨日红积分总量+昨日返还量））=今日累计红积分使用及转换的总量

        $sql = 'SELECT SUM(wrr_returnUnit) FROM pay_whitetored_return';
        $p['ss_wrReturnUnionTotal'] = $db->getField($sql);
        $p['ss_wrUnionScore'] = bcdiv($p['ss_wrReturnScore'], $p['ss_wrReturnUnionTotal'], 4);
		job::log("实际每单元转换唐宝数为" . $p['ss_wrUnionScore']);
		$p['ss_wrUnionScore'] = max(5.0163, $p['ss_wrUnionScore']);
		$p['ss_wrUnionScore'] = min(5.2631, $p['ss_wrUnionScore']);
        $p['ss_createTime'] = F::mytime();

        return $db->update('t_statistics_system', $p, "ss_id = '" . strtotime($calcdate) . "'") == 1;
    }

    //白积分到红积分转换
    //$num:并行进程总数，$tid：当前进程ID，$UnionScore：每单元返还积分，$step：步长
    static function whiteToRed3($num, $tid, $UnionScore, $step = 1000) {
        $db = new MySql();
		$dbErp = new MySql(DB_ERP);//erp数据库
        
        while ($ret = $db->getAll("SELECT wrr_id,wrr_uid,wrr_returnUnit FROM pay_whitetored_return WHERE wrr_id % $num = $tid AND wrr_returnUnit > 0 AND wrr_isProgress = 0 LIMIT $step")) {
            $db->beginTRAN();
            try {
                $ids = '';
                foreach ($ret as $v) {
					$ac = new account($db);
					$nick = $dbErp->getField("SELECT u_nick FROM t_user WHERE u_id = '".$v['wrr_uid']."'");
					$score = F::bankerAlgorithm($v['wrr_returnUnit'], $UnionScore, 4);
					$memo = "积分转换唐宝（转换单元数" . $v['wrr_returnUnit'] . "* 每单元转换唐宝{$UnionScore}）";
					if(!$ac->transferScore(30201, $v['wrr_uid'], $nick, '', -1,  $score, '', 0, '',  $memo) || !$ac->transferTang(40101, $v['wrr_uid'], $nick, '', 1, $score, '', 0, '', $memo)){
						throw new Exception($ac->getError());
					}
					$ids .= $v['wrr_id'] . ',';
					
                }
                $ids = trim($ids, ',');
                $sql = "UPDATE pay_whitetored_return SET wrr_isProgress = 1 WHERE wrr_id in ($ids)";
                if ($db->exec($sql) <= 0) {
                    throw new Exception($ac->getError());
                }
                $db->commitTRAN();
                //echo "$tid.success at " . date('Y-m-d H:i:s') . "\n";
            } catch (Exception $ex) {
                echo 'line:' . $ex->getLine() . ';error:' . $ex->getMessage() . "\n";
                $db->rollBackTRAN();
            }
            //usleep(50000);
        }
    }

    //白积分转红积分任务
    static function jobW2R($intNum = 10, $UnionScore) {
        $pids = array(); // 进程PID数组
        for ($i = 0; $i < $intNum; $i++) {
            $pid = pcntl_fork(); // 产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息
            if ($pid == -1) {
                echo "couldn't fork" . "\n";
                die();
            } elseif (!$pid) {//子进程
                score::whiteToRed3($intNum, $i, $UnionScore);
                exit(8); //子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
            } else {
                $pids[$pid] = $i;
            }
        }

        while (count($pids) > 0) {
            foreach ($pids as $p => $num) {
                $res = pcntl_waitpid($p, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    $code = pcntl_wexitstatus($status);
                    if ($code == 8) {
                        //正常结束
                        job::log("进程" . $num . "(" . $p . ")" . " 正常结束(" . $code . ") -> " . time());
                        //echo "进程" . $num . "(" . $p . ")" . " 正常结束(" . $code . ") -> " . time() . "\n";
                    } else {
                        //非正常结束
                        job::log("进程" . $num . "(" . $p . ")" . " 非正常结束(" . $code . ") -> " . time() . "需要重启进程");
                        //echo "进程" . $num . "(" . $p . ")" . " 非正常结束(" . $code . ") -> " . time() . "需要重启进程\n";
                        //重启此进程
                        $pid = pcntl_fork(); // 产生子进程，而且从当前行之下开试运行代码，而且不继承父进程的数据信息
                        if ($pid == -1) {
                            echo "couldn't fork" . "\n";
                            die();
                        } elseif (!$pid) {
                            job::log("第" . $num . "个进程 -> " . time() . "重启");
                            //echo "\n" . "第" . $num . "个进程 -> " . time() . "重启\n";
                            score::whiteToRed3($intNum, $num, $UnionScore);
                            exit(8); //子进程要exit否则会进行递归多进程，父进程不要exit否则终止多进程
                        } else {
                            $pids[$pid] = $num;
                        }
                    }
                    unset($pids[$p]);
                }
            }
            sleep(1);
        }
        job::log("执行完毕，校验数据完整性");
        $db = new MySql();
        $count = $db->getField("SELECT COUNT(*) as Num FROM pay_whitetored_return WHERE wrr_isProgress = 0");
        return $count == 0;
    }

    //冻结白积分转白积分任务
    static function jobL2W($intNum = 10) {
        
    }

}
