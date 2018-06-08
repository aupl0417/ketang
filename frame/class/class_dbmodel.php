<?php
use Medoo\Medoo;

/**
 * Created by PhpStorm.
 * User: lirong
 * Date: 2017/3/18
 * Time: 14:10
 */
class DBModel extends Medoo {

    public function __construct($config=array()){
        $options=array(
            'database_type' => DB_TYPE,
            'database_name' => DB_NAME,
            'server' => DB_HOST,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'port' => DB_PORT,
        );
        $options = array_merge($options,$config);
        parent::__construct($options);
    }

}