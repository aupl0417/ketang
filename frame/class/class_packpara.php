<?php

/**
 *
 * 重写参数安全过滤类，为方便使用，将此类方法全部静态化（重要）
 *
 * @author flybug
 * @version 2.0.0
 * @data 2014-11-02
 *
 */
class packpara {

    static private $isUnsetServerPara = true;

    static public function updateValue($value) {
        if (!self::$isUnsetServerPara) {
            return trim($value);
        }

        $value = htmlspecialchars(addslashes($value));
        if (preg_match("/^http:\/\/.*(taobao|tmall).com/", $value)) {
            return $value;
        }
        if (SYS_NEEDFILTER) {
            $value = preg_replace("/" . SYS_REPLACESTRING . "/", '***', $value);
        }
        return F::filterScript(urldecode(urldecode($value)));
    }

    static public function packValue() {

        $whilelist = array('account/returnUrl', 'account/notifyUrl');
        if (in_array(trim($_SERVER['PATH_INFO'],'/'), $whilelist)) {
            //log::writeLogMongo( 1010101, 'wdfdsfsf', '',$_SERVER['PATH_INFO']);
            self::$isUnsetServerPara = false;
        }

        //路径名
        $paras['PATH_INFO'] = self::updateValue($_SERVER['PATH_INFO']);
        $a = explode('/', trim($paras['PATH_INFO'], '/'));
        $paras['PATH_MODEL'] = ($a[0] != '') ? $a[0] : 'index';
        $paras['PATH_ACTION'] = isset($a[1]) ? $a[1] : 'index';
        $count =count($a)+2;
        $i=2;
        while ($i<$count){
            if (isset($a[$i+1])){
                $_GET[$a[$i]]=$a[$i+1];
                $_G[$a[$i]]=$a[$i+1];
            }
            $i=$i+2;
        }
        //GET
        if (is_array($_GET)) {
            foreach ($_GET as $k => $v) {
                if (!isset($paras[$k])) {
                    if (is_array($v)) {
                        $paras[$k] = $v;
                    } else {
                        $paras[$k] = self::updateValue($v);
                    }
                }
            }
        }
        //POST
        if (is_array($_POST)) {
            foreach ($_POST as $k => $v) {
                if (!isset($paras[$k])) {
                    if (is_array($v)) {
                        $paras[$k] = $v;
                    } else {
                        $paras[$k] = self::updateValue($v);
                    }
                }
            }
        }
        //COOKIE
        if (is_array($_COOKIE)) {
            foreach ($_COOKIE as $k => $v) {
                if (!isset($paras[$k])) {
                    $paras[$k] = self::updateValue($v);
                }
            }
        }
        //FILES

        if (is_array($_FILES)) {
            $paras['Files'] = $_FILES;
        }
		
		


        //防止重复提交
//        if ($paras['_ajax']) {
//            if (!$paras['_sign']) {
//                message::ShowEx('页面请求不合法', '/?model=user');
//                exit;
//            } else if ($paras['_crc'] != md5(PASSKEYWORD . $paras['_sign'] . PASSKEYWORD)) {
//                message::ShowEx('页面请求不合法', '/?model=user');
//                exit;
//            } else {
//                if ($_SESSION['_myFormPostOnce'] == $paras['_sign']) {
//                    message::ShowEx('请不要重复提交', '/?model=user');
//                    exit;
//                }else{
//                    $_SESSION['_myFormPostOnce'] = $paras['_sign'];
//                }
//            }
//        }

        if (self::$isUnsetServerPara) {
            //unset($_GET);
            //unset($_POST);
        }
        //unset($_COOKIE);
        unset($_FILES);

        return $paras;
    }

}

?>
