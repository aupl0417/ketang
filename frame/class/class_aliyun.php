<?php
/**
 * 阿里云OSS,CDN访问类
 *
 * @author flybug
 * @data 2014-06-05
 *
 */

//require_once $GLOBALS['cfg_basedir'].'/include/lib/aliyun/aliyun.php';

use Aliyun\OSS\OSSClient;

class aliyun
{
    private $aliyun_KeyId;
    private $aliyun_Secret;
    private $client;
    private $bucket;
    private $key;

    public function __construct($type = 'image')
    {
        $this->aliyun_KeyId = ALIYUN_KEYID;
        $this->aliyun_Secret = ALIYUN_SECRET;

        $this->client = $this->createClient();
        switch ($type) {
            case 'image':
                $this->setBucket(ALIYUN_OSSImageBucket);
                break;
            case 'media':
                $this->setBucket(ALIYUN_OSSMediaBucket);
                break;
        }
    }

    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setBucketAndKey($bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key = $key;
    }

    //创建客户端对象
    function createClient()
    {
        return OSSClient::factory(array(
            'AccessKeyId' => $this->aliyun_KeyId,
            'AccessKeySecret' => $this->aliyun_Secret,
        ));
    }

    //列举对象
    function listObjects($bucket)
    {
        $result = $this->client->listObjects(array(
            'Bucket' => $bucket,
        ));
        foreach ($result->getObjectSummarys() as $summary) {
            echo 'Object key: ' . $summary->getKey() . "\n";
        }
    }

    //存入字符串对象
    function putStringObject($content)
    {
        $result = $this->client->putObject(array(
            'Bucket' => $this->bucket,
            'Key' => $this->key,
            'Content' => $content,
        ));
        return $result->getETag();
    }

    //存入资源对象
    function putResourceObject($key = '', $fileName)
    {
        switch (pathinfo($fileName, PATHINFO_EXTENSION)) {
            case 'jpg':
            case 'jpe':
            case 'jpeg':
                $t = 'image/jpeg';
                break;
            case 'png':
                $t = 'image/png';
                break;
            case 'gif':
                $t = 'image/gif';
                break;
            default:
                $t = 'application/octet-stream';
                break;
        }


        $result = $this->client->putObject(array(
            'Bucket' => $this->bucket,
            'Key' => ($key == '') ? $this->key : $key,
            'ContentType' => $t,
            'Content' => fopen($fileName, 'r'),
            'ContentLength' => filesize($fileName),
        ));
        return $result->getETag();
    }

    //得到对象
    function getObject()
    {
        return $this->client->getObject(array(
            'Bucket' => $this->bucket,
            'Key' => $this->key,
        ));
    }

    //删除对象
    function deleteObject($key)
    {
        return $this->client->deleteObject(array(
            'Bucket' => $this->bucket,
            'Key' => $key,
        ));
    }

    //$keyId = 'FM4LRK667sr2tBQD';

    //$keySecret = 'HEgQxUgwSNZLt5GmzUsqsot0GBBhYE';

    //$client = createClient($keyId, $keySecret);

    //$bucket = 'yikuaiyou-image';

    //$key = 'ad/test2.jpg';

    //putStringObject($client, $bucket, $key, '123');

    //putResourceObject($client, $bucket, $key, fopen(dirname(__FILE__).'/8043_06.jpg', 'r'), filesize(dirname(__FILE__).'/8043_06.jpg'));

    //getObject($client, $bucket, $key);

    //deleteObject($client, $bucket, $key);

    //OSSClient $client

}

?>