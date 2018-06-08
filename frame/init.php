<?php

/*
if ( !strstr( $_SERVER[ 'HTTP_USER_AGENT' ], "iPhone" ) && !strstr( $_SERVER[ 'HTTP_USER_AGENT' ], "Android" ) && !strstr( $_SERVER[ 'HTTP_USER_AGENT' ], "iPad" ) ) {
	header('Location:/wait.html');
	
}else{
	echo json_encode(['id' => '95083', 'msg' => '系统维护中...']);
	die;
}

 * Version : 2.5.0
 * Author  : flybug
 * Comment : 2015-10-19
 * 
 * var:
 * 	model-调用的功能模
 * memo:
 * 	根据相关参数调用模块，配合不同的调用参数来定制
 */
define('FRAME_NAME', 'flybug'); //框架名称
define('FRAME_VERSION', '3.0'); //框架版本
define('FRAMEROOT', WEBROOT . '/frame'); //框架的根路径
define('PUBLICROOT', WEBROOT . '/app/public'); //公共项目的路径
define('APPROOT', WEBROOT . '/app/' . APP_NAME); //应用根目录
require(WEBROOT . '/frame/sys/regist.php'); //加载框架核心注册类
require(FRAMEROOT . "/class/class_cache.php"); //加载缓存类
require(FRAMEROOT . "/class/class_function.php" ); //基础函数
require(APPROOT . '/config/config.php'); //加载应用配置文件
require(APPROOT . '/config/dbconfig.php'); //数据库配置文件
require(APPROOT . '/config/regist.php'); //加载app注册类
require(APPROOT . "/language/" . LANGUAGE . ".php"); //语言
require (FRAMEROOT.'/vendor/autoload.php');
date_default_timezone_set('PRC'); //时间区域（中国）
spl_autoload_register(['F', 'myAutoload']);

//处理提交数据
$options = packpara::packValue();
define('PATH_TEMPLATE', APPROOT . '/template/' . LANGUAGE . '/' . $options['PATH_MODEL']);

//模版替换宏
define('_TEMP_PUBLIC_', '/app/public/assets');
define('_TEMP_SHARE_', '/app/' . APP_NAME . '/template/' . LANGUAGE . '/share');
define('_TEMP_ACTION_', '/app/' . APP_NAME . '/template/' . LANGUAGE . '/' . $options['PATH_MODEL']);
define('_TEMP_UPLOAD_', '/app/' . APP_NAME . '/upload');
define('_TEMP_DOWNLOAD_', '/app/' . APP_NAME . '/download');
define('_TEMP_CACHE_', '/app/' . APP_NAME . '/cache');
define('_PUBLIC_PATH_','/app/public');

//是否开启红包
define('RED_OPEN', 1);

//用于权限校验的字符串
define('POWER_CHECK', 'DTTX@123');

//动态加载分项目自定义配置文件
if (defined('EXTEND_CONFIG')){
    $extend_config =explode(',',EXTEND_CONFIG);
    foreach ($extend_config as $item){
        $filename =APPROOT.'/config/'.$item.'.php';
        if (is_file($filename)){
          C(load_config($filename));
        }
    }
}

//系统功能开
if (!DEBUG) {
    error_reporting(0);
} else {
    if (DEBUG_TYPE=='Whoops'){
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    }else{
        error_reporting(E_ALL);
    }
}
$action = APPROOT . '/controller/' . $options['PATH_MODEL'] . '/' . $options['PATH_ACTION'] . '.php';
if (file_exists($action)) {
    require (APPROOT . '/controller/' . $options['PATH_MODEL'] . '/' . $options['PATH_ACTION'] . '.php');

    //error_reporting(E_ALL ^ E_NOTICE);

    # 初始化session.
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', DOMAIN); //
    ini_set('session.cookie_lifetime', '86400');
    session_start();

    $act = str_replace('.', '_', $options['PATH_ACTION']);
    //模块调用
    $obj = new $act($options);
    $obj->run();

} else {
	if (DEBUG) {
	    echo $action."不存在";
	    exit;
	}else{
			if(F::isMobile())
		   header('location:http://' . WAPURL .'/');
		else
		//   header('location:http://' . WWWURL .'/404.html');
	    error_redirect(404);
	}
}

//后期处理
//ob_end_flush();
