<?php

/**
 * 系统信息类
 *
 * @author flybug
 * @version 1.0.0
 *
 */
class message {

    static public function getMessageByID($id) {
        return $GLOBALS['msg'][$id];
    }

    static public function getMsgStruct($id, $info = '') {
        return array('id' => $id, 'msg' => self::getMessageByID($id), 'info' => $info);
    }

    //返回Json格式的信息结构
    static public function getJsonMsgStruct($id, $info = '') {
        return json_encode(self::getMsgStruct($id, $info));
    }

    static public function getJsonMyMsgStruct($msg) {
        return json_encode(array('id' => '-1', 'msg' => $msg, 'info' => ''));
    }

    //根据传入的数组格式化信息。$id-信息编号，$arg-数组
    /*
     * 根据传入的数组格式化信息。$id-信息编号，$arg-数组
     * eg.
     * 定义的信息为：xxx => "我的测试定制信息是%s，这里是需要替换的";
     * getFormatMsg(xxx,['abc']);
     * 输出：“我的测试定制信息是abc，这里是需要替换的”
     * 
     */
    static public function getFormatMsg($id, $arg) {
        return sprintf(self::getMessageByID($id), $arg);
    }

    public static function show($msg, $gourl = '', $limittime = 3000) {
        $page = new page();
        $page->setTemplateFile('system/message_new_version');
        $page->getHtmlFromTemplateFile();
        $page->setTemplateData([
            '_replace' => [
                'sysname' => NAME,
                'msg' => $msg,
                'gourl' => $gourl,
                'timeout' => $limittime
            ]
        ]);
        $page->assemble();
        echo $page->getHTML();
    }

}

?>