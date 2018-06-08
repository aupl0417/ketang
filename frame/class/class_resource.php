<?php

/**
 *
 * 资源管理
 * @author flybug
 * @version 1.0.0
 */
class resource
{

    public function __construct()
    {

    }

    //添加资源
    public function addResource($vartab)
    {
        $db = new MySql();
        return $db->InsertRecord('a_resource', $vartab);
    }

    //编辑资源
    public function editResource($id, $vartab, $flag = 'r_id')
    {
        $db = new MySql();
        return $db->UpdateRecord('a_resource', $id, $vartab, $flag);
    }

    //查找资源信息
    public function getResourceById($ids = '', $fildes = '*', $and_ = '1=1', $db = NULL)
    {
        $cache = new cache();
        $ids = F::addYh($ids);
        $db = is_null($db) ? new MySql() : $db;
        if ($ids == '') {
            $sql = "SELECT {$fildes} FROM a_resource WHERE $and_ ORDER BY r_id DESC";
        } else {
            $sql = "SELECT {$fildes} FROM a_resource WHERE r_id in ({$ids}) AND $and_ ORDER BY r_id DESC";
        }
        $sqlMd5 = md5($sql);
        $listResult = $cache->get($sqlMd5);
        if (!$listResult) {
            $db->Query($sql);
            $listResult = $db->getAllRecodes(PDO::FETCH_ASSOC);
            $cache->set($sqlMd5, $listResult, 60 * 30);
        }
        return $listResult;
    }

    //获取资源分类
    public function getResourceType($ids = '', $num_ = 100, $db = NULL)
    {
        $wh_ = '';
        if ($ids) {
            $ids = F::addYh($ids);
            $wh_ = "where t_id in (" . $ids . ")";
        }
        $db = is_null($db) ? new MySql() : $db;
        $sql = "SELECT t_id, t_typename FROM a_resourcetype " . $wh_ . " limit 0," . $num_;
        $db->Query($sql);
        return $db->getAllRecodes(PDO::FETCH_ASSOC);
    }

    //获取资源分类
    public function getTypenameBytid($ids = '', $db = NULL)
    {
        $wh_ = '';
        if ($ids) {
            $ids = F::addYh($ids);
            $wh_ = "where t_id in (" . $ids . ")";
        }
        $db = is_null($db) ? new MySql() : $db;
        $sql = "SELECT t_typename FROM a_resourcetype " . $wh_;
        $db->Query($sql);
        return $db->getAllRecodes(PDO::FETCH_ASSOC);
    }

    //根据信息id删除资源
    public function delResource($id, $db = NULL)
    {
        $db = is_null($db) ? new MySql() : $db;
        return $db->DeleteRecord('a_resource', $id, 'r_id');
    }

    //把逗号分隔符r_ids、r_urls串转换为loop数组
    public function getLoopArrayByidsAndUrls($r_id)
    {
        $row = $this->getResourceById($r_id);
        $ids = explode(',', $row[0]['r_ids']);
        $urls = explode(',', $row[0]['r_urls']);
        for ($i = 0; $i < count($ids); $i++) {
            $ret[$i]['id'] = $ids[$i];
            $ret[$i]['url'] = $urls[$i];
        }
        return $ret;
    }
}
