<?php
/**
 * Created by PhpStorm.
 * User: lirong
 * Date: 2017/3/24
 * Time: 19:33
 */
class encrypt{

    private static $key =PASSKEYWORD;

    /**
     * 简单对称加密算法之加密
     * @param String $string 需要加密的字串
     * @param String $skey 加密EKY
     * @author Anyon Zou <zoujingli@qq.com>
     * @date 2013-08-13 19:30
     * @update 2014-10-10 10:10
     * @return String
     */
    public static function encode($string = '', $skey = '') {
        if (empty($skey)){
            $skey = self::$key;
        }
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace(array('=', '+', '/'), array('WVCEW', 'TtTRE', 'GFE3W'), join('', $strArr));
    }
    /**
     * 简单对称加密算法之解密
     * @param String $string 需要解密的字串
     * @param String $skey 解密KEY
     * @author Anyon Zou <zoujingli@qq.com>
     * @date 2013-08-13 19:30
     * @update 2014-10-10 10:10
     * @return String
     */
    public static function decode($string = '', $skey = '') {
        if (empty($skey)){
            $skey = self::$key;
        }
        $strArr = str_split(str_replace(array('WVCEW', 'TtTRE', 'GFE3W'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($skey) as $key => $value)
            $key <= $strCount  && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }



}