<?php
/**
 * Created by PhpStorm.
 * User: aupl
 * Date: 2017/3/24
 * Time: 18:00
 */

class WebExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('array', array($this, 'returnArray')),
            new \Twig_SimpleFilter('U', array($this, 'getUrl')),
        );
    }

    protected function returnArray(){
        return func_get_args();
    }

    /* (简写，功能待丰富)
     * @param $url string url地址 如public/index?id=1&b=2
     * @param $params string/array 如array('a=1', 'b=2')  或 a=1&b=2
     * return string
     * */
    protected function getUrl($url, $params){
        $urlInfo = parse_url($url);
        $pathArray = explode('/', trim($urlInfo['path'], '/'));
        $host = $_SERVER['HTTP_HOST'];
        if(count($pathArray) == 3){
            $host = $pathArray[0] . DOMAIN . '/';
            array_shift($pathArray);
        }

        if($params && is_array($params)){
            $params = array_map(function($val){
                return str_replace('/', '=', $val);//array('a/1', 'b/2')
            }, $params);
            $params = implode('&', $params);
        }

        $query = isset($urlInfo['query']) ? $urlInfo['query'] : '';
        $query = !$params ?: $query . '&' . $params;

        if(URL_MODEL === 0 && $query){
            parse_str($query, $output);
            $query = '?' . http_build_query($output);
        }else if(URL_MODEL === 1 && $query){
            parse_str($query, $output);
            $paramString = '';
            foreach ($output as $key=>$val){
                $paramString .= '/' . $key . '/' . $val;
            }
            $query = $paramString;
        }

        if($host){
            $url   =  (is_ssl()?'https://':'http://').$host.implode('/', $pathArray) . $query;
            if(isset($urlInfo['fragment'])){
                $url .= '#' . $urlInfo['fragment'];
            }
        }

        return $url;
    }

    /**
     * 判断是否SSL协议
     * @return boolean
     */
    function is_ssl() {
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
            return true;
        }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }

    public function getName()
    {
        return 'app_extension';
    }
}