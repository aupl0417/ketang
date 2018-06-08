<?php

/*
  根据 TFS RESTful API ( https://github.com/alibaba/nginx-tfs/blob/master/TFS_RESTful_API.markdown )
  编写而成的 TFS RESTful API Client
 */

class tfs {

    static private $tfsApi = TFS_UPLO0AD;

    static function setOption($suffix, $simple_name) {
//        return ($suffix == '') ? '' : "?suffix=$suffix&simple_name=$simple_name";
        return ($suffix == '') ? '' : sprintf('?suffix=%s&simple_name=%s', $suffix, $simple_name);
    }

    static function setApi($api) {
        self::$tfsApi = $api;
    }

    static function request($para = '', $method = 'GET', $data = null) {
        //die(TFS_APIURL . $para);
        $ch = curl_init(self::$tfsApi . $para);
        //curl_setopt($ch, CURLOPT_URL, TFS_APIURL);
        //指定 HTTP 请求的 Method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        //设定CURL请求的超时时间(当你需要上传或者获取比较大的文件时,可以将超时时间设置的大一点,以防止文件没操作完成,但超过超时时间之后出现问题)
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); //单位秒(此处设定为2分钟)
		
        if ($data !== null) {
            //https 请求
            if (strlen(self::$tfsApi) > 4 && (strtolower(substr(self::$tfsApi, 0, 5)) == 'https' ||  strtolower(substr(self::$tfsApi, 0, 4)) == 'http')) {               
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //去除自带的这两个 HTTP请求头
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:', 'Accept:'
            ));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: '
            ));
        }

        //将得到的内容通过 curl_exec 返回, 而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //curl_exec 返回的内容不要包含HTTP响应头
        curl_setopt($ch, CURLOPT_HEADER, false);
        $return_data = curl_exec($ch);
        curl_close($ch);

        return $return_data;
    }

    //返回保存后的文件名,如果保存失败, 则返回 FALSE
    //$suffix-文件后缀；$simple_name-是否要求必须带正确后缀才可访问存入的TFS文件(1：要求必须带正确后缀才可访问;0：不带后缀也可访问)
    static function save($data, $suffix = '', $simple_name = 0) {
        $ret = tfs::request(tfs::setOption($suffix, $simple_name), 'POST', $data);
//        $ret = tfs::request('', 'POST', $data);
        $ret = json_decode($ret, TRUE);

        //解析 json 字符串失败, 或者返回的内容中没有 TFS_FILE_NAME, 则认为保存失败
        if ($ret === NULL || !isset($ret['TFS_FILE_NAME'])) {
            return 'error';
        }

        return $ret['TFS_FILE_NAME'];
    }

    //返回更新后的文件名,如果更新失败, 则返回 FALSE
    //$filename 旧的文件名(即你要更新哪个文件的文件名)
    //$data 要更新的内容是什么
    //$parameter 设置更新后的文件时访问所需要的参数
    static function update($filename, $data, $suffix = '', $simple_name = 0) {
        $ret = tfs::request(sprintf('/%s%s', $filename, tfs::setOption($suffix, $simple_name)), 'PUT', $data);

        $ret = json_decode($ret, TRUE);

        //解析 json 字符串失败, 或者返回的内容中没有 TFS_FILE_NAME, 则认为保存失败
        if ($ret === NULL || !isset($ret['TFS_FILE_NAME'])) {
            return FALSE;
        }

        return $ret['TFS_FILE_NAME'];
    }

    //从TFS服务器上获取文件
    static function read($filename) {
        return tfs::request(sprintf('/%s', $filename), 'GET');
    }

    //从TFS服务器上删除文件
    static function delete($filename) {
        tfs::request(sprintf('/%s', $filename), 'DELETE');
    }

    //从网站目录复制文件到TFS服务器上
    //$filename:本地文件的完整路径
    //$delete:是否删除本地文件 0:否,1-是
    static function move($filename, $delete = 1) {
        if (file_exists($filename)) {
            $suffix = substr($filename, strrpos($filename, '.'));
            $file = file_get_contents($filename);
            $saveName = tfs::save($file, $suffix, 1);
            //print_r($saveName);
            if ($saveName == 'error') {
                return false;
            }
            if ($delete) {
                @unlink($filename);
            }
            return $saveName;
        } else {
            return false;
        }
    }

}

////测试代码, 自动生成要上传的文件
//$tf1 = __DIR__ . '/test1.txt';
//$tf2 = __DIR__ . '/test2.txt';
//
////写入文件内容
//file_put_contents($tf1, 'HelloWorld!!!!!');
//file_put_contents($tf2, 'HelloWorld, Again!!!!!' . time());
//
//
//
////保存文件至TFS上
////使用 .txt 的扩展名保存文件(suffix=.txt), 并且要求访问时必须带正确的后缀才可以访问存入的文件(simple_name=1)
////第二个参数中的内容, 请根据 TFS RESTful API 中的说明并根据自己的需要做相应的修改, 此处只是根据你之前提供的代码做演示
//$result = tfs::save(file_get_contents($tf1), '?suffix=.txt&simple_name=1');
//
//
//if ($result === FALSE) {
//    exit('文件保存失败!');
//}
//
//$filename = $result;
//echo "上传成功后的文件名为: ", $filename, "\n";
//
//
////从TFS服务器上读取文件
//$f = $tfs->ReadFile($filename);
//
////将读取到的文件写入到本地
//file_put_contents(__DIR__ . '/ntest1.txt', $f);
//
//
//
//
////更新文件至TFS上
//$result = $tfs->UpdateFile($filename, file_get_contents($tf2));
//
//if ($result === FALSE) {
//    exit('更新文件失败!');
//}
//
//$filename = $result;
//echo "更新后的文件名为: ", $filename, "\n";
//
////从TFS服务器上读取文件
//$f = $tfs->ReadFile($filename);
////将读取到的文件写入到本地
//file_put_contents(__DIR__ . '/ntest2.txt', $f);
//
//
//
////输出上传/下载下来的文件的MD5值
//$files = array('test1.txt', 'ntest1.txt', 'test2.txt', 'ntest2.txt');
//
//foreach ($files as $f) {
//    echo __DIR__ . '/' . $f, "\t", md5(file_get_contents(__DIR__ . '/' . $f)), "\n";
//}
//
//
//
////删除更新后的文件
//$tfs->delete($filename);
//
////尝试读取刚才删除的文件
//$f = $tfs->read($filename);
//
////会输出 404, 因为文件被删除了, 不存在了, 根据 TFS RESTful API 文档中的定义, 服务器返回  404 Not Found
//echo $f;


