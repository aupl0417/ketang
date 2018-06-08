<?php
/**
 * Created by PhpStorm.
 * User: lirong
 * Date: 2017/3/17
 * Time: 21:09
 */
class dataDao{

    static $db = null;

    public function __construct() {
        self::$db = new MySql();
    }

    public function create($sql,$listRow,$curpage,$orderBy=''){

        if (empty($sql)){
            return false;
        }

        $sql = preg_replace('/\n/', ' ', $sql);

        $reg = "/(?<=select)(.+?)(?=[\s]+from[\s\(]{1})/i";
        if(!preg_match($reg, $sql, $matchs)){//sql查询的字段必须包括在{}中
            return array();
            exit;
        };

        $fields = ' ' . $matchs[0] . ',';//所有查询字段

        $tsql = preg_replace($reg, ' count(1) ', $sql, 1);//str_replace('###', 'count(1)', $sql);
        $recordsTotal = self::$db->getField($tsql);

        if (!empty($curpage) && $recordsTotal != 0 && $curpage > ceil($recordsTotal / $listRow)){
            $curpage = ceil($recordsTotal / $listRow); //当前页数大于最后页数，取最后一页
        }
        if (!empty($orderBy)){
            $sql.= " ORDER BY ".$orderBy;
        }
        $sql .= " LIMIT " . ($curpage - 1) * $listRow . ",$listRow;";

        $data = self::$db->getAll($sql);

        $datalist =array();

        $datalist['count']=$recordsTotal;
        $datalist['data']=$data;

        if (!empty($recordsTotal)){
            return $datalist;
        }else{
            $datalist['count']=0;
            $datalist['data']=array();
        }


    }



}