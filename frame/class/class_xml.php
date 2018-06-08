<?php

//xml操作的类。
class xml {

    private $XMLObj;
    private $XMLStr;
    private $XMLHtml;

    function __construct($DOMObj = NULL) {
        $this->setDOMObj($DOMObj);
    }

    function setDOMObj($DOMObj) {
        $this->XMLObj = $DOMObj;
    }

    function getDOMObj() {
        return $this->XMLObj;
    }

    /*     * **************************************************************
     * 打开xml,返回类型按照参数指定.
     * obj-返回xml文档对象;str-返回标准asc字符串;html-返回网页可显示的html代码
     * *************************************************************** */

    function openXML($xmlfile, $model = "obj") {
        $dom = new DOMDocument("1.0", "utf-8");
        if (!file_exists($xmlfile)) {
            $dom->save($xmlfile);
        } else {
            $dom->load($xmlfile);
        }
        switch ($model) {
            case "str":
                $this->XMLStr = $dom->saveXML();
                break;
            case "html":
                $this->XMLHtml = $dom->saveHTML();
                break;
            default:
                $this->XMLObj = $dom;
        }
    }

    //Document 对象是一棵文档树的根，可为我们提供对文档数据的最初（或最顶层）的访问入口。
    function openXMLFromStr($xmlstr) {

        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadXML($xmlstr);
        $this->XMLObj = $dom;
    }

    function saveXML($model = 'str') {
        switch ($model) {
            case "str":
                return $this->XMLObj->saveXML();
                break;
            case "html":
                return $this->XMLObj->saveHTML();
        }
    }

    /*     * **************************************************************
     * 创建新的xml,返回类型按照参数指定.
     * 返回xml文档对象,并创建空文件
     * *************************************************************** */

    function createXML() {
        $this->XMLObj = new DOMDocument("1.0", "utf-8");
    }

    /*     * ************************************
     * createNodeWithAttrib 创建带属性的新节点
     * $root   此节点的父节点
     * $id     此节点的名称
     * $attrib 此节点的属性数组
     * $text  此节点的文本值
     * ************************************* */

    function createNodeWithAttrib($root, $id, $attrib = '', $text = '') {
        $addNod = $this->XMLObj->createElement($id); //创建一个新节点
        if ($text != '') {//创建文本值
            $t = $this->XMLObj->createTextNode($text);
            $addNod->appendChild($t);
        }
        if (is_array($attrib)) {
            $this->createAttrib($addNod, $attrib);
        }
        $root->appendChild($addNod); //把新节点附给指定的父节点
        return $addNod;
    }

    /*     * ************************************
     * createAttrib 创建指定节点的属性值
     * $cnode  当前节点对象
     * $attrib 此节点的属性数组
     * ************************************* */

    function createAttrib($cnode, $attrib) {
        foreach ($attrib as $key => $value) {//根据属性数组给新节点附属性值
            $temp = $this->XMLObj->createAttribute($key); //创建一个新的属性
            $cnode->appendChild($temp); //把属性附给新节点
            $tempvalue = $this->XMLObj->createTextNode($value); //创建一个新的文本节点作为属性的值
            $temp->appendChild($tempvalue); //把属性的值附给属性
        }
        return true;
    }

    //删除指定TagName的所有子节点
    function delChildsByTagName($tagName) {
        $nodes = $this->getNodesFromTagName($tagName);
        if (!is_object($nodes)) {
            return true;
        }
        foreach ($nodes as $node) {
            //删除节点必须从后往前删否则无法定位子节点
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                $node->removeChild($node->childNodes->item($i));
            }
        }
    }

    /*
     * 解析XML得到相关数据
     */

    //通过标志名称得到结点对象集合
    function getNodesFromTagName($tagName) {
        return $this->XMLObj->getElementsByTagName($tagName);
    }

    //通过标志名称得到第一个结点对象
    function getOneFromTagName($tagName) {
        return $this->XMLObj->getElementsByTagName($tagName)->item(0);
    }

    //得到结点集合的个数
    function getNodeCount($nodes) {
        return $nodes->length;
    }

    //得到指定结点的指定属性
    function getAttribFromNode($node, $aName) {
        return $node->getAttribute($aName);
    }

    //得到指定结点的内容值
    function getValueFromNode($node) {
        return $node->nodeValue;
    }

    //得到指定结点的子结点个数
    function getNodeChildCount($node) {
        return $node->childNodes->length;
    }

    //得到指定结点的子结点
    function getNodeChilds($node) {
        return $node->childNodes;
    }

    //根据结点路径得到结点对象
    function getNodeFromDOMXPath($XPath) {
        $xpath = new DOMXPath($this->XMLObj);
        return $xpath->query($XPath);
    }

    //把结点集合变为关联数组
    //形如：array("属性名"=>"属性值",……,"value"=>结点内容)
    function getArrayFromNodes($nodes) {
        $num = 0;
        foreach ($nodes as $node) {
            foreach ($node->attributes() as $key => $v) {
                $arr[$num][$key] = $v;
            }
            $arr[$num]['value'] = $this->getValueFromNode($node);
            $num++;
        }
        return $arr;
    }

    //修改指定节点的内容
    function setNodeValue($node, $text) {
        $node->nodeValue = $text;
    }

    //修改指定节点的指定属性的值
    function setNodeAttrib($node, $att, $v) {
        $node->setAttribute($att, $v);
    }

    /*     * ************************************
     * editNodeAttrib 修改指定节点的属性值
     * $dom    XML文档对象
     * $tag    节点名称
     * $id     此节点的标识
     * $op     操作(填加,删除,修改)
     * $attrib 此节点的属性数组
     * ************************************* */

    function editNodeAttrib($tag, $id, $op, $attrib) {
        $editNode = $this->getNodesFromTagName($tag); //查找所有节点
        foreach ($editNode as $value) {
            foreach ($value->attributes as $a) {
                if ($a->nodeValue == $id) {
                    foreach ($attrib as $key => $v) {
                        switch ($op) {
                            case "add":
                                $temp = $this->XMLObj->createAttribute($key); //创建一个新的属性
                                $value->appendChild($temp); //把属性附给新节点
                                $tempvalue = $this->XMLObj->createTextNode($v); //创建一个新的文本节点作为属性的值
                                $temp->appendChild($tempvalue); //把属性的值附给属性
                                break;
                            case "edit":
                                $value->setAttribute($key, $v);
                                break;
                            case "del":
                                $value->removeAttribute("$key");
                                break;
                        }
                    }
                }
            }
        }
    }

    /* ----------XML转二维数组--------------- */

    static function xml2array($xml) {
        $parser = xml_parser_create('UTF-8'); // UTF-8 or ISO-8859-1
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $values);
        xml_parser_free($parser);

        $return = array();
        $stack = '';
        $attrs = array();
        $levelCounters = array(0);
        foreach ($values as $val) {
            if ($val['type'] == "open") {
                $ind = array_pop($levelCounters);
                $stack = $val['tag'];
                if (isset($val['attributes']) && $val['attributes']) {
                    foreach ($val['attributes'] as $attrKey => $attrVal)
                        $return[$attrKey] = $attrVal;
                }
                array_push($levelCounters, $ind + 1);
                array_push($levelCounters, 0);
            } elseif ($val['type'] == "close") {
                $stack = substr($stack, 0, strrpos($stack, '.'));
            } elseif ($val['type'] == "complete") {
                $ind = array_pop($levelCounters);
                $stack = $val['tag'];
                if (isset($val['attributes']) && $val['attributes']) {
                    foreach ($val['attributes'] as $attrKey => $attrVal)
                        $return[$attrKey] = $attrVal;
                }
                $return[$stack] = $val['value'];
                $stack = substr($stack, 0, strrpos($stack, '.'));
                array_push($levelCounters, $ind + 1);
            }
        }
        return $return;
    }
    
    

}

/*$temp = new MyXML();
try {
$temp->openXMLFromStr('<?xml version="1.0" encoding="utf-8"?>
<question><id>Q05_011620147447</id><para><memo>简述CAXA制造工程...</memo><title>简述CAXA制造工程师2006的主要功能？</title></para><information/><gradepoint><point_0 score="1">这个是知识点1</point_0><point_0 score="3">这个是知识点2</point_0><point_0 score="4">这个是知识点3</point_0><point_0 score="1">这个是知识点4</point_0><point_0 score="2">这个是知识点5</point_0></gradepoint><answer/></question>');
//$temp->delChildsByTagName('id');
}catch (Exception $e){
	echo -1;
	exit();
}
$temp->setNodeValue($temp->getOneFromTagName('memo'),'');
echo $temp->saveXML('str');
*/

