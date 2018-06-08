<?php

/**
 * 角色类（控制器扩展类）
 * 身份说明：0－无身份；1-用户；2-雇员
 */
// 用户控制模块
abstract class member extends controller {

    public function __construct($options = [], $power = []) {
        parent::__construct($options, [1], $power);
        $this->head = array_key_exists('_ajax', $this->options) ? '' : F::readFile(PUBLICROOT . '/template/cn/share/header.html');
        $this->foot = array_key_exists('_ajax', $this->options) ? '' : F::readFile(PUBLICROOT . '/template/cn/share/footer.html');
    }

}

// 员工控制模块
abstract class worker extends controller {

    public function __construct($options = [], $power = []) {
        parent::__construct($options, [2], $power);
        $this->head = array_key_exists('_ajax', $this->options) ? '' : F::readFile(APPROOT . '/template/cn/share/header.html');
        $this->foot = array_key_exists('_ajax', $this->options) ? '' : F::readFile(APPROOT . '/template/cn/share/footer.html');
    }

}
