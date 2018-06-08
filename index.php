<?php

/*
 * 框架入口 
 */
header('Content-type: text/html; charset=utf-8');
define('WEBROOT', dirname(realpath(__FILE__)));

/*
 * 请根据你的域名设定根域名
 */
define('DOMAIN', '.aupl.com'); //根域名
/*
 * 多站模式
 */
if (preg_match('/.*(?='.DOMAIN.')/', $_SERVER['HTTP_HOST'], $out)) {
    if (!file_exists(WEBROOT . '/app/' . $out[0])) {
        die('忘了建这个站点？');
    } else {
        define('APP_NAME', $out[0]);
    }
} else {
    die('路径错了！');
}

/*
 * 单站模式
 */

//define('APP_NAME', '');
require('frame/init.php'); //require之后的当前路径仍然是目前的路径，不改变当前路径

exit;
