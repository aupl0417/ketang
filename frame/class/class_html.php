<?php

/* * ****************** */
/*
  /*  Version : 1.3.0
  /*  Author  : flybug
  /*  Comment : 20140312
 *  重要修正：
 *  1、重写了相关正则表达式，增加了对换行回车制表符等特殊字符的匹配，可以在不改变原模板排版的基础上进行匹配
 *  2、用preg正则函数集替换了ereg正则函数集，php5.3后继版本不支持ereg，且preg效率更高。
 *  
 *  2014-03-12
 *  增加了html的head标签设置功能
 *  
 *  edit by flybug
 */

/* * ****************** */

class myHTML {

    private $tag = [
        "tag_count" => 2,
        "tag_type" => [
            0 => "replace",
            1 => "loop"
        ],
        //tag_sign标签
        "tag_sign" => '/<replace[\w\W]+?<\/replace>|<loop[\w\W]+?<\/loop>/',
        //tag_head标签头
        "tag_head" => '/<(replace|loop)[\w\W]+?>/',
        //tag——foot标签尾文件
        "tag_foot" => '/<\/(replace|loop)>/',
        //replace标签
        "replace" => [
            "preg_sign" => '/<replace[\w\W]+?<\/replace>/',
            "preg_head" => '/^<replace[\w\W]+?>/'
        ],
        //loop标签
        "loop" => [
            "preg_sign" => '/<loop[\w\W]+?<\/loop>/',
            "preg_head" => '/^<loop[\w\W]+?>/'
        ],
        //head标签
        "head" => '/<head>[\w\W]*<\/head>/i',
        "title" => '/<title>[\w\W]*<\/title>/i',
        "keywords" => '/<meta name=*.keywords.*?>/i',
        "description" => '/<meta name=*.Description.*?>/i',
        //body标签
        "body" => '/<body>/i',
    ];
    private $array_tag = array
        (
        "tag_count" => 0,
        "tag" => array(),
        "type" => array(),
        "para" => array(),
        "body" => array()
    );

    public function __construct() {
       
    }

    private function getTags($html) {
        if (!preg_match_all($this->tag['tag_sign'], $html, $out)) {
            return false;
        }
        $this->array_tag['tag'] = $out[0];
		//exit(print_r($this->array_tag['tag']));
        unset($out); //释放变量
        $this->array_tag['tag_count'] = count($this->array_tag['tag']);
        $this->getTagVar();
        return true;
    }

    private function getTagVar() {
        $i = 0;
        foreach ($this->array_tag['tag'] as $value) {
            preg_match($this->tag['tag_head'], $value, $out);
            $out[0] = preg_replace('/<|>|"/', '', $out[0]);
            $arr_value = explode(' ', $out[0]);
            $this->array_tag['type'][$i] = $arr_value[0]; //preg_replace('/flybug:/', '', $arr_value[0]);
            unset($out);
            unset($arr_value[0]);
            foreach ($arr_value as $v) {
                $splitnum = strpos($v, '=');
                $this->array_tag['para'][$i][substr($v, 0, $splitnum)] = substr($v, $splitnum + 1);
            }
            $body = preg_replace($this->tag['tag_head'], '', $this->array_tag['tag'][$i]);
            $body = preg_replace($this->tag['tag_foot'], '', $body);
            $this->array_tag['body'][$i] = $body;
            ++$i;
        }
    }

    //得到html $html为名字，$para数据
    public function getHTML($html, $_para) {
        if ($html == '') {
            return '';
        }
		$OperatingVars = $this->getOperatingVar($html,isset($_para['_replace']) ? $_para['_replace'] : []);
		$html = $this->compile_if($html,$OperatingVars);
        if (!$this->getTags($html)) {
            return self::analysisFunc($html,$OperatingVars); //编译模板方法
        }
        for ($i = 0; $i < $this->array_tag['tag_count']; ++$i) {
            $body = $this->array_tag['body'][$i];
            switch ($this->array_tag['type'][$i]) {
                case "replace" :
                    $t = explode('|', $this->array_tag['para'][$i]['value']);
                    if (!is_array($t)) {
                        $body = preg_replace('/{' . $t . '}/', $_para['_replace'][$t], $body);
                    } else {
                        foreach ($t as $value) {
                            $body = preg_replace("/{" . $value . "}/", $_para['_replace'][$value], $body);
                        }
                    }
                    break;
                case "loop" :
                    $t = $_para['_loop'][$this->array_tag['para'][$i]['data']];
                    if (!isset($t)) {
                        break;
                    }
                    if (!isset($this->array_tag['para'][$i]['count'])) {
                        $count = count($t);
                    } else {
                        $count = min($this->array_tag['para'][$i]['count'], count($t));
                    }
                    $temp = '';
                    $j = 0;
                    for (; $j < $count; ++$j) {
                        $temp .= $body;
                        foreach ($t[$j] as $k => $v) {
							$temp = $this->compile_if($temp,$t[$j] + $OperatingVars);
                            $temp = preg_replace("/{" . $k . "}/", $v, $temp);
                        }
                    }
                    $body = $temp;
                    break;
                case "all" :
                    break;
            }
            $this->array_tag['body'][$i] = $body;
        }
        unset($i);
        unset($body);
        foreach ($this->array_tag['tag'] as $key => $value) {
            $html = str_replace($value, $this->array_tag['body'][$key], $html);
        }
        return $html = self::analysisFunc($html,$OperatingVars); //运算模板函数;
    }
	//获得参与模板运算的变量
	public function getOperatingVar($html='',$vars) {
		if(empty($vars)) {
			return [];
		}
		$_vars = [];
		if(preg_match_all('/\$[_\w]{1,20}/',$html,$matches)) {
			foreach($matches[0] as $var) {
					$key = substr($var,1);
					if(isset($vars[$key])) {
					  $_vars[$key] = $vars[$key];
				}
			}
		}
		return $_vars;
	}
	
	//解析模板函数
	public static function analysisFunc($html='',$vars=[]) {	
		if($html=='' || !preg_match('/{:[:\w]{1,20}\([^{]+?\)\s*}/is',$html)) {
			return $html;
		}	
		return preg_replace_callback('/{:([:\w]{1,20})\(([^{]*?)\)\s*}/is', function ($match) use ($vars,$html) {
           $func = $match[1];
		   $params = isset($match[2]) ? $match[2] : '';
		   if(strpos($func,"::")) {
			  list($class,$method) = explode('::',$func,2);
			  if(!method_exists($class,$method)) {
                 trigger_error('class "'.$class.'" not exist method "'.$method.'"',E_USER_ERROR);
			  }
           }else
		      if(!function_exists($func)) {
			    trigger_error('function "'.$func.'" not exist',E_USER_ERROR); 
		   }
		   if(preg_match('/,?\$/',$params)) {
		     extract($vars);
		   }
		   $result = '';
		   eval('$result='.$func.'('.$params.');');
		   return $result;
        }, $html);
    }
    /*
     * 设置模板文件的head信息，如果没有head标签，则不设置
     * hasHeadTag检查是否有head标签
     * setHeadTag批量设置
     * setTitleOfHeadTag设置标题
     * setKeywordsOfHeadTag设置关键字
     * setDescriptionOfHeadTag设置描述
     * setPageSign设置页面合法校验标识
     */

     public function hasHeadTag($html) {
        return preg_match($this->tag['head'], $html);
    }

    //统一设置title、keywords、description
    public function setHeadTag($html, $title = '', $keywords = '', $description = '') {
        if ($this->hasHeadTag($html)) {
            $html = ($title) ? $this->setTitleOfHeadTag($html, $title) : $html;
            $html = ($keywords) ? $this->setDescriptionOfHeadTag($html, $description) : $html;
            $html = ($description) ? $this->setKeywordsOfHeadTag($html, $keywords) : $html;
        }
        return $html;
    }

    //设置title
    public function setTitleOfHeadTag($html, $title = '') {
        if (!$this->hasHeadTag($html)) {
            return $html;
        }
        //找到则替换，没有找到则添加到<head>标签后
        if (preg_match($this->tag['title'], $html)) {
            $html = preg_replace($this->tag['title'], "<title>$title</title>", $html);
        } else {
            $html = preg_replace('/<head>/i', "<head><title>$title</title>", $html);
        }
        return $html;
    }

    //设置keywords
    public function setKeywordsOfHeadTag($html, $keywords = '') {
        if (!$this->hasHeadTag($html)) {
            return $html;
        }
        //找到则替换，没有找到则添加到</head>标签前
        if (preg_match($this->tag['keywords'], $html)) {
            $html = preg_replace($this->tag['keywords'], "<meta name=\"keywords\" content=\"$keywords\" />", $html);
        } else {
            $html = preg_replace('/<\/title>/i', "</title>\r\n<meta name=\"keywords\" content=\"$keywords\" />", $html);
        }
        return $html;
    }

    //设置description
    public function setDescriptionOfHeadTag($html, $description = '') {
        if (!$this->hasHeadTag($html)) {
            return $html;
        }
        //找到则替换，没有找到则添加到</head>标签前
        if (preg_match($this->tag['description'], $html)) {
            $html = preg_replace($this->tag['description'], "<meta name=\"description\" content=\"$description\" />", $html);
        } else {
            $html = preg_replace('/<\/title>/i', "</title>\r\n<meta name=\"description\" content=\"$description\" />", $html);
        }
        return $html;
    }
	
	//编译if语句
	private function compile_if($str ='',$vars) {
	  if($str == '' || !is_array($vars))
	    return 	$str;
	   $replaceLoop = [];
	   $replaceLoopStr = '#loop#';		
	   if(preg_match_all('|<loop[^>]+>((?!</loop>).)*<if[^>]+>[\s\S]*</if>[\s\S]*</loop>|isU',$str,$matches)) { //为防止if标签包含的loop标签出现if,直接把loop标签替换成'$replaceLoopStr'字符串，以便破坏loop下面的if
		  foreach($matches[0] as $key=>$val) {
		    $replaceLoop[$replaceLoopStr.$key] = $val;
		    $str = str_replace($val,$replaceLoopStr.$key,$str);
		  }
	   }	
	   $str = preg_replace_callback("|<if[^>]+>([\s\S]*)</if>|U",  function($m)  use ($vars,$str) {	  
	   $str0 = $m[0];
	   if(preg_match('|<loop[^>]+>((?!</loop>).)*'.preg_quote($str0).'[\s\S]*</loop>|isU',$str)) { //当检索到的if标签被loop标签包含时候，不进行替换。
		  return $str0;
	   }		
	   extract($vars);
	   $string = '';
       $str0 = preg_replace('|<if[^>]+>|iU','${0}$string = \'',$str0);
       $str0 = preg_replace('|<elseif[^>]+>|iU','\';${0}$string = \'',$str0);
       $str0 = preg_replace('|<else>|iU','\';${0}$string = \'',$str0);
       $str0 = preg_replace('|</if>|iU','\';${0}',$str0);
       $patterns = [
           '|<if([^>]+)>|U',
		   '|<elseif([^>]+)>|iU',
		   '|<else>|iU',
		   '|</if>|iU',  
       ];
       $replaces = [
           'if(${1}){',
		   '}elseif(${1}){',
		   '}else{',
		   '}', 
      ];
      $str0 = preg_replace($patterns,$replaces,$str0);
	  $patterns1 = [
		   '|(if\(.+?[\s]+)egt([\s]+)|iU', //大于等于
		   '|(if\(.+?[\s]+)lt([\s]+)|',//小于
		   '|(if\(.+?[\s]+)elt([\s]+)|', //小于等于
		   '|(if\(.+?[\s]+)gt([\s]+)|iU',//大于
      ];
	  $replaces1 = [
		   '${1}>=${3}',
		   '${1}<${3}',
		   '${1}<=${3}',
		   '${1}>${3}' , 
      ];
	  if(!get_magic_quotes_gpc()) {
		 $str0 = preg_replace_callback('|({\$string[\s]*=[\s]*\')([\s\S]+?)(\';[\s]*})|', function($mtes) { return $mtes[1].addslashes($mtes[2]).$mtes[3];}, $str0);
	  }
	  
	  $str0 = preg_replace($patterns1,$replaces1,$str0);
      eval($str0);
	  if(!get_magic_quotes_gpc()) {
		 $string = stripslashes($string);  
	  }
      return $string;  
     },$str);
	 if(!empty($replaceLoop)) {
		 if(preg_match_all('|'.preg_quote($replaceLoopStr).'[\d]{1,2}|',$str,$matches)) {  //还原出被替换的loop
		   foreach($matches[0] as $key=>$val) {
			   if(isset($replaceLoop[$val])) {
				   $str = str_replace($val,$replaceLoop[$val],$str);
			   }   
		   }
	    }	 
	  }
	 return $str;
   }

}
