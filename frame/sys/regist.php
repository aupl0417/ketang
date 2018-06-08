<?php

//系统注册类
/*
 * 注册frameclass核心类库
 * 注册的方法为：在对应的数组中添加键值对，键名为类的名字，值名为类文件名，值名为空即表示类文件名与类名相同。
 */

define('WWWURL', 'www' . DOMAIN);
define('TRADEURL', 'trade' . DOMAIN);
define('WORKERURL', 'work' . DOMAIN);
define('OPENURL', 'docopen' . DOMAIN);
define('UCURL', 'u' . DOMAIN);
define('WAPURL', 'wap' . DOMAIN);
define('EDUURL', 'edu' . DOMAIN);
define('PAYURL', 'pay' . DOMAIN);
define('SRVURL', 'service' . DOMAIN);
define('BBSURL', 'bbs' . DOMAIN);
define('DEMOURL', 'demo' . DOMAIN);
define('INSIDEAPI', 'insideapi' . DOMAIN);
define('DTPAY', 'dtpay' . DOMAIN);
define('EDU', 'edu' . DOMAIN);

define('ADMIN_ID', '00000000000000000000000000000000');
define('ADMIN_NAME', '系统账户');
define('ERPAPPKEY', 'C000000000000001');

define('TFS_UPLO0AD', 'http://10.0.0.9:8200/v1/tfs');//tfs上传地址
define('TFS_APIURL', 'https://image.dttx.com/v1/tfs');//tfs查看服务器地址

$frameclass = [
    'myHTML' => 'html',
    'MySql' => 'db',
    'myRedis' => 'redis',
    'UploadFile' => 'upload',
    'xml',
    'tfs',
	'message',
    'taobaoAPI' => 'appsdk',
    'HttpDownload' => 'httpdownload',  
    'log',   
    'packpara',
    'validate',
    'idcard',
    'controller',	
    'calculation', //参数计算类
    'score', //积分操作类  	
    'bletter',
    'divpage',
    'resource',
    'cache',
    'vip',
    'aliyun',
    'myexcel',
    'MyQRCode' => 'myqrcode',
    'sms',
    'STD3Des' => 'crypt', //3Des加密，需要php开mcrypt扩展
    'relation', //用户关系基类
    'uploadFile' => 'upload',
    //全局角色注册
    //'guest' => 'acter',访客页面由各子站定义，不再框架全局定义
    'member' => 'acter',
    'worker' => 'acter',
    'bbs' => 'acter',
    'mobileservice' => 'acter',
    'queues',
    'mgdb' => 'mongo',
    'redenvelope',
	'apis',
	'job',
	'rsa',
	'apiAuthorize',
	'api',
	'put',
    'dataDao'=>'dataDao',
    'kpage',
    'DBModel'=>'dbmodel',
    'encrypt'=>'encrypt'
];