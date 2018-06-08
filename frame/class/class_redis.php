<?php

/* ClassName: myRedis
 * Memo:redis class
 * Version:2.1.1
 * EditTime:2011-11-21
 * Writer:flybug
 * eg.
 * $r = (new myRedis())->linkID;
 * $r->set('name','xiongbin');
 * echo $r->get('name');
 * 
 * 
 * 
 * */

class myRedis extends Redis {

    public $obj;

    public function __construct() {
        try {
            $this->obj = new Redis();
            $this->obj->pconnect(REDIS_HOST, REDIS_PORT);
            $this->obj->auth(REDIS_PASS);
        } catch (Exception $ex) {
            die('create redis error.');
        }
    }
    
    public function getObj(){
        return $this->obj;
    }

    public function __destruct() {
        //$this->close();
    }

    //关闭链接
    public function close() {
        $this->obj->close();
        $this->obj = null;
    }

    //统计查询的数量
    public function count($tab, $where_) {
        
    }

}
