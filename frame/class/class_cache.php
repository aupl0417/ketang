<?php

/*
 * Memo:缓存类
 * Version:1.0.0.0
 * EditTime:2014-03-13
 * Writer:flybug
 * 
 * */

class cache {

    private $type;
    private $cacheparam;
    private $cacheObj;
    private $compressed; //MEMCACHE_COMPRESSED,是否使用memcache压缩
	private $fix;

    public function __construct($type = 'memcache', $fix = 'edu') {
        $this->cacheparam = MEMCACHE_COMPRESSED;
        $this->compressed = MEMCACHE_COMPRESSED;
        $this->type = $type; //缓存类型
		$this->fix = $fix; //缓存前缀
        $this->initCache();
    }

    public function initCache() {
        switch ($this->type) {
            case 'memcache':
            default:
                $this->cacheObj = new Memcache();
                $this->cacheObj->addServer(MEMCACHE_SERVERS, MEMCACHE_PORT, false, 1, 100);
                break;
        }
    }
	
	//新增一个值
    public function add($k, $v, $t = 600) {
        return $this->cacheObj->add($this->fix.$k, $v, $this->compressed, $t);
    }

    //设置一个值，如果存在就覆盖。$k键，$v值，$t过期时间，秒数
    public function set($k, $v, $t = 600) {
		if(strlen($k) > 100){
			return false;
		}
        return $this->cacheObj->set($this->fix.$k, $v, $this->compressed, $t);
    }

    //得到一个值
    public function get($k) {
        return $this->cacheObj->get($this->fix.$k);
    }

    //删除一个值
    public function del($k) {
        return $this->cacheObj->delete($this->fix.$k);
    }

    //更新缓存
    public function flush() {
        return $this->cacheObj->flush();
    }

    //获取Memcache状态信息
    public function getExtendedStats() {
        return $this->cacheObj->getExtendedStats();
    }

    //memcached的increment方法，主要应用于计数器
    public function inc($k) {
        return $this->cacheObj->increment($this->fix.$k);
    }

    //减数器
    public function dec($k) {
        return $this->cacheObj->decrement($this->fix.$k);
    }

    //关闭
    public function close() {
        return $this->cacheObj->close();
    }

}

?>