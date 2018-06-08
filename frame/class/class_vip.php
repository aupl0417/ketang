<?php

/**
 * 用户VIP类
 * @author flybug
 * @version 1.0.0
 */
class vip
{

    protected $userID = ''; //编号
    protected $userNick = ''; //昵称
    protected $userState = ''; //用户当前状态
    protected $vipPrice = ''; //vip价格体系

    //构造函数

    function __construct()
    {
        $this->vipPrice = '';
    }

    function add($vartab, $db = NULL)
    {
        $db = is_null($db) ? new MySql() : $db;
        if ($db->InsertRecord('c_vip', $vartab)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function edit($uid, $vartab, $db = NULL)
    {
        $db = is_null($db) ? new MySql() : $db;
        return $db->UpdateRecord('c_user', $uid, $vartab, $flag = 'u_id') == 1;
    }

    /**
     * 根据商家vip类型获取对应的vip数据,用于vip购买和vip延长时间
     * 购买只需要传入前两个参数，延期需要传入所有参数
     * @param type $id //vip类型
     * @param type $discount //折扣,默认值=1，若需要打折请传入小数折扣值
     * @param type $vipStartTime //已经购买vip的开始时间时间,传入xxxx年xx月xx日 xx时xx分xx秒格式即可
     * @param type $vipEndTime //已经购买vip的结束时间,传入xxxx年xx月xx日 xx时xx分xx秒格式即可
     * @return array(vip类型标示id,vip名称,vip级别,vip时长,vip原价,折扣值,折扣后价格,vip开始时间,vip结束时间)
     */
    function getVipTypeById($id, $discount = 1, $vipStartTime = null, $vipEndTime = null)
    {
        $vipStartTime = is_null($vipStartTime) ? date('Y-m-d', time()) : date('Y-m-d', strtotime($vipStartTime));
        $id = trim($id);
        //判断vip类型是否合法
        $arrviptype = array('b2', 'b3', 'b4', 'z2', 'z3', 'z4');
        if (!in_array($id, $arrviptype)) {
            die('vip类型不合法');
        }
        $vipinfo = array();
        switch ($id) {
            case 'b2':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '铂金用户';
                $vipinfo['level'] = 1;
                $vipinfo['month'] = 3;
                $vipinfo['price'] = $this->vipPrice['svip']['1v3'];
                break;
            case 'b3':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '铂金用户';
                $vipinfo['level'] = 1;
                $vipinfo['month'] = 6;
                $vipinfo['price'] = $this->vipPrice['svip']['1v6'];
                break;
            case 'b4':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '铂金用户';
                $vipinfo['level'] = 1;
                $vipinfo['month'] = 12;
                $vipinfo['price'] = $this->vipPrice['svip']['1v12'];
                break;
            case 'z2':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '至尊用户';
                $vipinfo['level'] = 2;
                $vipinfo['month'] = 3;
                $vipinfo['price'] = $this->vipPrice['svip']['2v3'];
                break;
            case 'z3':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '至尊用户';
                $vipinfo['level'] = 2;
                $vipinfo['month'] = 6;
                $vipinfo['price'] = $this->vipPrice['svip']['2v6'];
                break;
            case 'z4':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '至尊用户';
                $vipinfo['level'] = 2;
                $vipinfo['month'] = 12;
                $vipinfo['price'] = $this->vipPrice['svip']['2v12'];
                break;
            default :
                break;
        }
        if ($vipinfo['id']) {
            $vipinfo['discount'] = $discount;                                                    //折扣值
            $vipinfo['discount_price'] = $vipinfo['price'] * $vipinfo['discount'];               //折扣后价格
            $vipinfo['vipstime'] = $vipStartTime;                                                //vip开始时间
            $vipinfo['vipetime'] = is_null($vipEndTime) ? date('Y-m-d', strtotime($vipinfo['vipstime'] . "+" . $vipinfo['month'] . "month")) : date('Y-m-d', strtotime($vipEndTime . "+" . $vipinfo['month'] . "month")); //vip结束时间
            if ($vipinfo['discount'] < 1 || $vipinfo['discount_price'] != $vipinfo['price']) {   //vip信息
                $vipinfo['info'] = $vipinfo['name'] . "，" . $vipinfo['month'] . "个月，原价：" . $vipinfo['price'] . "元，折扣后价格：" . $vipinfo['discount_price'] . "元，开始时间：" . $vipinfo['vipstime'] . "，结束时间：" . $vipinfo['vipetime'];
            } else {
                $vipinfo['info'] = $vipinfo['name'] . "，" . $vipinfo['month'] . "个月，价格：" . $vipinfo['price'] . "元，开始时间：" . $vipinfo['vipstime'] . "，结束时间：" . $vipinfo['vipetime'];
            }
        }
        return $vipinfo;
    }

    /**
     * 根据商家vip类型获取对应的vip数据,用于vip购买和vip延长时间
     * 购买只需要传入前两个参数，延期需要传入所有参数
     * @param type $id //vip类型
     * @param type $discount //折扣,默认值=1，若需要打折请传入小数折扣值
     * @param type $vipStartTime //已经购买vip的开始时间时间,传入xxxx年xx月xx日 xx时xx分xx秒格式即可
     * @param type $vipEndTime //已经购买vip的结束时间,传入xxxx年xx月xx日 xx时xx分xx秒格式即可
     * @return array(vip类型标示id,vip名称,vip级别,vip时长,vip原价,折扣值,折扣后价格,vip开始时间,vip结束时间)
     */
    public function getShikeVipTypeById($id, $discount = 1, $vipStartTime = null, $vipEndTime = null)
    {
        $vipStartTime = is_null($vipStartTime) ? date('Y-m-d', time()) : date('Y-m-d', strtotime($vipStartTime));
        $id = trim($id);
        //判断vip类型是否合法
        $arrviptype = array('b3', 'b4');
        if (!in_array($id, $arrviptype)) {
            die('vip类型不合法');
        }
        $vipinfo = array();
        switch ($id) {
            case 'b3':
                $vipinfo['id'] = $id;                   //vip类型标示id
                $vipinfo['name'] = '试客VIP';           //vip名称
                $vipinfo['level'] = 1;                  //vip级别    
                $vipinfo['month'] = 6;                  //vip时长
                $vipinfo['price'] = $this->vipPrice['bvip']['1v6'];             //vip原价
                break;
            case 'b4':
                $vipinfo['id'] = $id;
                $vipinfo['name'] = '试客VIP';
                $vipinfo['level'] = 1;
                $vipinfo['month'] = 12;
                $vipinfo['price'] = $this->vipPrice['bvip']['1v12'];
                break;
            default :
                break;
        }
        if ($vipinfo['id']) {
            $vipinfo['discount'] = $discount;                                                    //折扣值
            $vipinfo['discount_price'] = $vipinfo['price'] * $vipinfo['discount'];               //折扣后价格
            $vipinfo['vipstime'] = $vipStartTime;                                                //vip开始时间
            $vipinfo['vipetime'] = is_null($vipEndTime) ? date('Y-m-d', strtotime($vipinfo['vipstime'] . "+" . $vipinfo['month'] . "month")) : date('Y-m-d', strtotime($vipEndTime . "+" . $vipinfo['month'] . "month")); //vip结束时间
            if ($vipinfo['discount'] < 1 || $vipinfo['discount_price'] != $vipinfo['price']) {   //vip信息
                $vipinfo['info'] = $vipinfo['name'] . "，" . $vipinfo['month'] . "个月，原价：" . $vipinfo['price'] . "元，折扣后价格：" . $vipinfo['discount_price'] . "元，开始时间：" . date('Y-m-d', strtotime($vipinfo['vipstime'])) . "，结束时间：" . date('Y-m-d', strtotime($vipinfo['vipetime']));
            } else {
                $vipinfo['info'] = $vipinfo['name'] . "，" . $vipinfo['month'] . "个月，价格：" . $vipinfo['price'] . "元，开始时间：" . date('Y-m-d', strtotime($vipinfo['vipstime'])) . "，结束时间：" . date('Y-m-d', strtotime($vipinfo['vipetime']));
            }
        }
        return $vipinfo;
    }

//    /**
//     * 递归计算试客vip邀请提成
//     * @param type $array
//     * @param type $ucode
//     * @param type $filter
//     * @param type $db
//     */
//    public function countPayment($array, $ucode, $db, $filter = 0) {
//        if (!is_array($array)) {
//            return FALSE;
//        }
//        if ($ucode == 0) {
//            return FALSE;
//        }
//        if (empty($array)) {
//            return FALSE;
//        }
//        $sql = "SELECT u_code FROM c_user WHERE u_bvipcode = '$ucode' AND u_type <> '$filter'";
//        $db->Query($sql);
//        $res = $db->getAllRecodes(PDO::FETCH_ASSOC);
//    }

    /**
     * 增加商家信用异动记录
     * @param type $chgType 异动类型-1是消费信用，1是补回信用金，2开通信用金
     * @param type $Ffrom 信用值 例如array('504f115beac0fc14c30d933679732673','500')
     * @param type $operateid 操作人uid
     * @param type $aid 活动id是可选值，只有在涉及活动扣除信用时才有用
     * @param type $memo 异动记录
     * @param type $time 创建时间
     * @param type $db 数据对象
     */
    public function operateSellerCredit($chgType, $Ffrom, $operateid, $aid, $memo, $time, $db = null)
    {
        $db = is_null($db) ? new MySql() : $db;
        $sellerId = $Ffrom[0]; //商家用户id
        $changeMoney = $Ffrom[1]; //异动金额
        $user = new users();
        $userinfo = $user->getUserByID($sellerId, 'ue_scurrentmoney', $db);
        $seller_scurrentmoney = $userinfo['ue_scurrentmoney'];  //商家信用金
        switch ($chgType) {
            //消费信用金
            case '-1':
                $new_scurrentmoney = bcsub($seller_scurrentmoney + '', $changeMoney + '', 2) + 0;
                $creditData = array(
                    'ue_scurrentmoney' => $new_scurrentmoney,
                    'ue_chtime' => $time,
                );
                break;
            //补回信用金
            case '1':
                $new_scurrentmoney = bcadd($seller_scurrentmoney + '', $changeMoney + '', 2) + 0;
                $creditData = array(
                    'ue_scurrentmoney' => $new_scurrentmoney,
                    'ue_chtime' => $time,
                );
                break;
            //开通信用金
            case '2':
                $creditData = array(
                    'ue_sclevel' => 1,
                    'ue_chtime' => $time,
                );
                break;
            default :
                return FALSE;
        }
        //更新商家信用金
        $ret = $db->UpdateRecord('c_userex', $sellerId, $creditData, 'ue_id');
        if (($ret != 0) && ($ret != 1)) {
            return false;
        }
        //插入异动记录
        $chg_data = array(
            'c_sid' => $sellerId,
            'c_money' => $changeMoney,
            'c_memo' => $memo,
            'c_aid' => $aid,
            'c_ctime' => $time,
            'c_type' => $chgType,
            'c_operateid' => $operateid,
        );
        return $db->InsertRecord('c_sellercreditchg', $chg_data) == 1;
    }

    /**
     * @查找我的邀请人(3级)
     * @param $u_code  用户u_code 推广码
     * @param $mytype  用户u_type 类别
     * @param $db
     * @return mixed
     */
    public function findFreecode($u_code, $mytype, $db)
    {
        static $layer = 0;
        static $vip_recommend_res = array();      //返利人
        $us = new users();
        $res = $us->getUserByCode($u_code, 'u_type', $db);
        $db = is_null($db) ? new MySql() : $db;
        if (($u_code != 0 && $res['u_type'] != 3) && $layer < 3) {
//返利层级
            $sql = "SELECT u_id,u_code,u_bvipcode,u_type,u_nick FROM c_user WHERE u_code='{$u_code}'";
            $db->Query($sql);
            $res = $db->getAllRecodes(PDO::FETCH_ASSOC);
            $vip_recommend_res[$layer] = $res[0];
            $layer++;
            $this->findFreecode($res[0]['u_bvipcode'], $res[0]['u_type'], $db);
        }
        return $vip_recommend_res;
    }

    /**
     * @奖励我的邀请人合计多少钱
     * @param $id
     * @param $price
     * @param $db
     * @return bool
     */
    public function rewardVip($id, $price, $db)
    {
        $us = new users();
        $ac = new account();
        $db = is_null($db) ? new MySql() : $db;
        $rank = array(0.2, 0.1, 0.05);        //奖励百分百
        $res = $us->getUserByID($id, 'u_bvipcode,u_type', $db);
        $bvipFcode = $res['u_bvipcode'];   //买家VIP邀请码
        $mytype = $res['u_type'];          //用户类型
        $res_uid = $this->findFreecode($bvipFcode, $mytype, $db);
        //return $res_uid;
        $sum = 0;
        if ($res_uid) {
            foreach ($res_uid as $k => $v) {
                $money = bcmul($rank[$k], $price, 2);
                $fromVip = $us->getUserByID($id, 'u_nick', $db);
                $toVip = $us->getUserByID($v['u_id'], 'u_nick', $db);
                $memo = $fromVip['u_nick'] . '购买VIP奖励邀请人' . $toVip['u_nick'] . '->' . ($rank[$k] * 100) . '%佣金:' . $money;
                if (!$ac->operatAccount('bvipinvite', $money, array($id, 'ac_ldzj'), array($v['u_id'], 'ac_ldzj'), $us->getUserAttrib('userID'), $memo, $db)) {
                    log::writelog($memo . '失败', 'rewardVip', $us->getUserAttrib('userID'));
                    continue;
                }
                $sum += $money;
            }
        }
        return $sum;
    }
}

?>
