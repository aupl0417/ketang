<?php

/**
 * 天翼开放平台短信访问类
 *
 * @author flybug
 * @data 2014-06-05
 *
 */
class sms {

    private $sms_UserId;
    private $sms_KeyId;
    private $sms_Secret;
    private $sms_Type = 0; //短信发送平台：0-电信天翼；1-短信网
    private $sms_Error;

    /* 示远短信配置账号：
密码：
【大唐天下】 */
    private $syConfig = [
        'noSupport'	=> [11, 12, 13, 14, 15, 16, 18, 20, 22, 24, 25, 27],
        'support'	=> [1],
        'chanel1'	=> [//普通接口
            'api'		=> 'http://send.18sms.com/msg/HttpBatchSendSM',
            'account'	=>'q5gd78',
            'pswd'		=>'Gv573N0X'
        ],
        'chanel2'	=> [//营销接口
            'api'		=> 'http://121.43.107.8:8888',
            'userid'	=>12,
            'account'	=>'',
            'password'	=>''
        ]
    ];

    //模版短信映射表
    private $sms_Map = array(
        'validate' => '91549108',
//        'order_success' => '91001958',
//        'order_fail' => '91001959',
//        'try_success' => '91001885',
//        'try_fail' => '91001896'
    );
    private $accessToken; //访问令牌
    private $accessToken_Url = 'https://oauth.api.189.cn/emp/oauth2/v3/access_token'; //访问令牌获取地址
    //private $redirect_AccessToken_Url = 'http://www.youpinshiyong.com/?model=sms&do=getAccessToken';//访问令牌回调地址
    private $token; //授权码
    private $send_token_Url = 'http://api.189.cn/v2/dm/randcode/token'; //获取授权码地址
    private $send_RandCode_Url = 'http://api.189.cn/v2/dm/randcode/send'; //发送验证码短信息地址
    private $redirect_RandCode_Url = 'http://user.youpinshiyong.com/?model=sms&do=getRundCode'; //获取短信验证码回调地址
    private $send_TemplateSms_Url = 'http://api.189.cn/v2/emp/templateSms/sendSms'; //发送模版短信地址
    private $accessTokenCacheSign = '88577eb486bb54c4322659f4ff85b7dc'; //访问令牌缓存标识[md5(flybug_accessTokenCacheSign)]
    private $send_TemplateSMS_DXWUrl = 'http://web.duanxinwang.cc/asmx/smsservice.aspx';
    private $send_TemplateSMS_QYXSUrl = 'http://106.3.37.29:8808/sms.aspx';
    private $backList = array( );//黑名单号码;
    private $whiteList = [];
    private $prefixSign = '';

    public function __construct() {

        $this->sms_UserId = SMS_USERID;
        $this->sms_KeyId = SMS_KEYID;
        $this->sms_Secret = SMS_SECRET;
        $this->sms_Type = SMS_TYPE;
    }

    //获取Access_token，自动缓存
    /*
     * @param $clearCache 是否强制刷新token,默认不强制刷新
     */
    public function getAccessToken($type = 'client_credentials', $clearCache = 0) {
        $cache = new cache();
        $this->accessToken = $cache->get($this->accessTokenCacheSign);
        if ($clearCache || !$this->accessToken) {
            $param = array(
                'app_id' => $this->sms_KeyId,
                'app_secret' => $this->sms_Secret,
                'grant_type' => $type
            );
            $ret = json_decode(F::curl($this->accessToken_Url, $param), true);
            $this->accessToken = $ret['access_token'];
            $cache->set($this->accessTokenCacheSign, $this->accessToken, $ret['expires_in']); //缓存当前accesstoken
        }
    }

    //获取信任
    public function getToken() {
        $param['app_id'] = "app_id=" . $this->sms_KeyId;
        $param['access_token'] = "access_token=" . $this->accessToken;
        $param['timestamp'] = "timestamp=" . date('Y-m-d H:i:s');
        ksort($param);
        $plaintext = implode("&", $param);
        $param['sign'] = "sign=" . rawurlencode(base64_encode(hash_hmac("sha1", $plaintext, $this->sms_Secret, $raw_output = True)));
        $result = F::curl($this->send_token_Url . '?' . $plaintext . '&' . $param['sign']);
        $resultArray = json_decode($result, true);
        $this->token = $resultArray['token'];
    }

    //发送模版短信
    private function SendTemplateSMS($phone, $tid, $tparam) {
        //return true;
        switch ($this->sms_Type) {
            case 0://天翼
                $this->getAccessToken();
                $param['app_id'] = $this->sms_KeyId;
                $param['access_token'] = $this->accessToken;
                $param['timestamp'] = date('Y-m-d H:i:s');
                $param['acceptor_tel'] = $phone;
                $param['template_id'] = $tid;
                $param['template_param'] = json_encode($tparam);
                ksort($param);
                foreach ($param as $k => $v) {
                    $t[] = "$k=$v";
                }
                $plaintext = implode("&", $t);
                $param['sign'] = base64_encode(hash_hmac("sha1", $plaintext, $this->sms_Secret, $raw_output = True));
                $result = F::curl($this->send_TemplateSms_Url, $param);
                $resultArray = json_decode($result, true);
                if ($resultArray['res_code'] != 0) {
                    log::writelog($resultArray['res_message'], 'sms');
                    if ($resultArray['res_code'] == 110) {
                        $this->getAccessToken('client_credentials', 1);
                    }
                }
                return $resultArray['res_code'] == 0;
                break;
            case 1://短信网
                $param = array(
                    'name' => $this->sms_KeyId,
                    'pwd' => $this->sms_Secret,
                    'content' => '尊敬的用户，您的验证码为' . $tparam['code'] . '，有效期180秒，工作人员不会向您索要短信内容，切勿泄露。感谢您的支持！【大唐天下】',
                    'mobile' => $phone,
                    'stime' => '',
                    'sign' => '',
                    'type' => 'pt',
                    'extno' => '',
                );
                $result = F::curl($this->send_TemplateSMS_DXWUrl, $param);
                log::writelog('用户手机' . $phone . '发送了验证码' . $tparam['code'] . '，返回状态为' . $result, 'sms');
                if (is_null($result) || $result == '') {
                    return false;
                }
                $resultArray = explode(',', $result);
                return $resultArray[0] == 0;
                break;
            case 2://企业信使
                switch($tparam['tempId']){
                    case 1:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您本次操作的验证码为：' . $tparam['code'] . '，如有疑问请联系客服。';
                        break;
                    case 2:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的密码已重置为：' . $tparam['code'] . '。有疑问请联系客服。';
                        break;
                    case 11:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的个人认证已通过，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 12:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的个人认证被驳回，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 13:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的企业认证已通过，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 14:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的企业认证被驳回，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 15:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的联盟商家认证已通过，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 16:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的联盟商家认证被驳回，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 17:
                        if(isset($tparam['act_reason'])){
                            $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，由于您的账户存在异常记录（'.$tparam['act_reason'].'），现已被冻结。请联系客服95083。';
                        }else{
                            $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，由于您的账户存在异常记录，现已被冻结。详情请咨询客服95083。';
                        }
                        break;
                    case 18:
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'] . '用户，您的账户已恢复正常使用，详情请登录网站https://'.WWWURL.' 查看或联系客服95083。';
                        break;
                    case 19:
                        $content = $tparam['nick'].'单笔转账超过十万，已被拒绝！';
                        break;
                    case 20:
                        //检查参数
                        if(!isset($tparam['p_nick']) || !isset($tparam['nick']) || !isset($tparam['levelName'])  || !isset($tparam['money'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        //奖励现金转为唐宝
                        $tang = F::bankerAlgorithm($tparam['money'],100);
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您推荐的用户:'.$tparam['nick'].' 已升级为'.$tparam['levelName'].'，如果您7天内未升级为创客或更高级，将无法获得'.$tang.'唐宝奖励！';
                        break;
                    case 21:
                        //检查参数
                        if(!isset($tparam['store'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        $content = $this->prefixSign . '尊敬的' . $tparam['nick'].'用户，您的库存积分低于您设置的'.$tparam['store']."，请及时充值! 以免影响您的正常运营（库存积分不足，系统将停止对用户的奖励结算）！";
                        break;
                    case 22:
                        //检查参数
                        if(!isset($tparam['p_nick']) || !isset($tparam['nick']) || !isset($tparam['money'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        //奖励现金转为唐宝
                        $tang = F::bankerAlgorithm($tparam['money'],100);
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您推荐的用户:'.$tparam['nick'].' 已购买代理，如果您7天内未升级到【创投】或成为代理商，将无法获得'.$tang.'唐宝奖励！';
                        break;
                    case 23:
                        //提现被驳回
                        if(!isset($tparam['p_nick']) || !isset($tparam['money'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您的提现申请被驳回，驳回原因请查看提现记录详情，提现金额￥'.$tparam['money'].' 已退回您的账户，您可以重新提现，客服热线95083。';
                        break;
                    case 24:
                        //认证被撤销
                        if(!isset($tparam['p_nick']) || !isset($tparam['p_name'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您的'.$tparam['p_name'].'被撤销，详情请登录网站https://'.WWWURL.' 查看或联系客服95083';
                        break;
                    case 25:
                        //唐人大学奖励
                        if(!isset($tparam['p_nick']) || !isset($tparam['p_name'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您的账户收到奖励：'.$tparam['p_name'].'，详情请登录网站https://'.WWWURL.' 查看或联系客服95083';
                        break;
                    case 26:
                        //雇员密码重置
                        if(!isset($tparam['password']) || empty($tparam['password'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        $content = $this->prefixSign . '您的登录密码重置成功，新密码是：'.$tparam['password'].'。请及时更换您的密码。';
                        break;
                    case 27:
                        //检查参数
                        if(!isset($tparam['p_nick']) || !isset($tparam['nick']) || !isset($tparam['money'])){
                            $this->sms_Error = -6;
                            return false;
                        }
                        //奖励现金转为唐宝
                        $tang = F::bankerAlgorithm($tparam['money'],100);
                        $content = $this->prefixSign . '尊敬的' . $tparam['p_nick'] . ' 用户，您推荐的用户:'.$tparam['nick'].' 已升级代理商的职位，如果您7天内未升级到【创投】或成为代理商，将无法获得'.$tang.'唐宝奖励！';
                        break;
                    case 997:
                        $content = $this->prefixSign . '尊敬的管理员，队列服务器故障。'.F::mytime();
                        break;
                    case 998:
                        $content = $this->prefixSign . '尊敬的管理员，服务器遭受攻击。'.F::mytime();
                        break;
                    case 999:
                        $content = $this->prefixSign . '尊敬的管理员，每日积分转唐宝的任务已经完成。';
                        break;
                    default:
                        $this->sms_Error = -6;
                        return false;
                        break;
                }
                //print_r($tparam);

                $param = array(
                    'userid' => $this->sms_UserId,
                    'account' => $this->sms_KeyId,
                    'password' => $this->sms_Secret,
                    'content' => $content,
                    'mobile' => $phone,
                    'sendTime' => '',
                    'action' => 'send',
                    'extno' => ''
                );
                //print_r($param);
                //日志
                if(substr($phone,0,3) == '144'){    //145开头的号码为测试账号
                    log::writeLogMongo(95083, 't_sms', $phone, $content);
                    return true;
                }else{
                    $logs = $param;
                    $logs['content'] = $content;
                    if(in_array($tparam['tempId'], $this->syConfig['support'])){//示远可支持的短信
                        if(mt_rand(0, 100) <= 100){//随机一部分短信走示远
                            //log::writeLogMongo(950834, 't_sms', $phone, '发送sy验证码');
                            if($this->sySmsSend($param)){//示远发送成功
                                $smslogs=array('smstype'=>'发送了sy验证码','content'=>$content);
                                log::writeLogMongo(95083, 't_sms', $phone, $smslogs);
                                return true;
                            }
                            else{
                                log::writeLogMongo(950834, 't_sms', $phone, $this->sms_Error);
                            }
                        }
                    }
                    $param['content'] = $param['content'] . '【大唐天下】';
                    log::writeLogMongo(95083, 't_sms', $phone, '发送了验证码');
                    $result = xml::xml2array(F::curl($this->send_TemplateSMS_QYXSUrl, $param));
                    return $result['message'] == 'ok';
                }
                break;
        }
    }

    //发送模版认证短信（成功返回验证码索引id，失败返回0）
    public function SendValidateSMS($phone,$tempId = 1,$data = array(), $nick = '大唐天下') {
        if (!F::isPhone($phone)) {
            $this->sms_Error = -3;//不是有效的手机号码
            return false;
        }

        //防止频繁发送，间隔需要120秒(放在cache里)
        $cache = new cache();
        $cacheCode = $cache->get('smsCode_'.$phone);
            if($tempId == 2){
                $code = rand(10000000, 99999999);
            }else{
                $code = rand(100000, 999999);
            }
        if (!$cacheCode) {   //如果此号码没有发送码记录，则set
            $save = array(
                'code'  =>  $code,
                'ctime'  =>  time(),
                'errTimes'   => 0
            );
            $cache->set('smsCode_'.$phone, $save, 86400);    //验证码保存180秒
        }else {     //有缓存信息表示SMS_SENDINTERVAL时间内多次操作
            //判断是否频繁发生
            if(($cacheCode['ctime']+SMS_SENDINTERVAL) > time() ){
                $this->sms_Error = -2;//发送过于频繁
                return false;
            }

        //    $code = $cacheCode['code'];
            $save = array(
                'code'  =>  $code,
                'ctime'  =>  time(),
                'errTimes'   => 0
            );
            $cache->set('smsCode_'.$phone, $save, 86400);    //更新发送时间
        }

        $p = array(
            'nick' => $nick,
            'code' => $code,
            'tempId' => $tempId
        );

        $p = array_merge($p,$data);


        if ($this->SendTemplateSMS($phone, $this->sms_Map['validate'], $p)) {
            if($tempId == 2){
                return $code;
            }else{
                return true;
            }
        } else {
            log::writeLogMongo(12566, 'sms', $phone, 'error:'.$this->sms_Error);

            if(!$this->sms_Error){
                $this->sms_Error = -1;
            }
            return false;
        }
    }

    private function sySmsSend($param){
        $post_data = array();
        $syConfig = $this->syConfig['chanel1'];
        $post_data['account']	 = $syConfig['account'];   //帐号
        $post_data['pswd']		 = $syConfig['pswd'];  //密码
        $post_data['msg']		 = urlencode($param['content']); //短信内容需要用urlencode编码下
        $post_data['mobile']	 = $param['mobile']; //手机号码， 多个用英文状态下的 , 隔开
        $post_data['product']	 = ''; //产品ID
        $post_data['needstatus'] = true; //是否需要状态报告，需要true，不需要false
        $post_data['extno']		 = '';  //扩展码   可以不用填写
        $url					 = $syConfig['api'];

        $result	= F::curl($url, $post_data);
        $resArr = explode(',', $result);
        if(count($resArr) == 2){
            if($resArr[1] == '0'){
                return true;
            }
            else{
                $this->sms_Error = '发送失败sy:'.$result.'_'.$post_data.'_'.json_encode($syConfig);//发送失败
                return false;
            }
        }
        else{
            $this->sms_Error = '发送失败sy';//发送失败
            return false;
        }
    }

    //根据索引得到模版认证短信的验证码进行验证
    public function TestValidateByIndex($phone, $code) {
        $cache = new cache();
        $cacheInfo = $cache->get('smsCode_'.$phone);
        if (!$cacheInfo) {
            $this->sms_Error = '验证码错误';
            return false;
        }

        $cacheCode = $cacheInfo['code'];

        $errTimes = isset($cacheInfo['errTimes'])?$cacheInfo['errTimes']:0;
        //检查验证次数
        if($errTimes >= 5 ){
            $this->sms_Error = '验证码错误超过5次，请重新发送';
            $cache->del('smsCode_'.$phone); //清除缓存
            return false;
        }

        //检测验证码
        if($cacheCode == $code){    //匹配成功
            $cache->del('smsCode_'.$phone); //清除缓存
            return true;
        }else{
            //错误次数
            $num = $errTimes + 1;
            //验证错误次数累加，达到五次就清除验证码
            $save = array(
                'code'      =>  $cacheInfo['code'],
                'ctime'     =>  $cacheInfo['ctime'],
                'errTimes'   => $num
            );
            $cache->set('smsCode_'.$phone, $save, 86400);    //更新发送时间
            $this->sms_Error = '验证码错误第'.$num.'次（五次之后自动清除验证码）';
            return false;
        }
    }

    //测试短信发送
    public function test($phone) {
        return $this->SendValidateSMS($phone);
    }

    //短信发送失败的原因
    public function getError() {
        return $this->sms_Error;
    }

}

?>