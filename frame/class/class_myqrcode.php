<?php
/**
 * @author flybug
 * @version 1.0.0
 *
 * 二维码模块
 *
 * $errorCorrectionLevel     纠错级别：L、M、Q、H
 * $matrixPointSize          点的大小：图片每个黑点的像素
 *
 *
 */
require_once(WEBROOT . '/frame/lib/phpqrcode/phpqrcode.php');

class MyQRCode
{
    //直接输出到网页
    static public function getOutHtml($data, $errorCorrectionLevel = 'L', $matrixPointSize = 4)
    {
        QRcode::png($data, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    //输出为图片
    static public function getOutPic($data, $filename, $errorCorrectionLevel = 'L', $matrixPointSize = 4)
    {
        QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    //输出为带Logo的二维码图片
    static public function getOutPicWithLogo($data, $filename, $logo, $errorCorrectionLevel = 'L', $matrixPointSize = 10)
    {
        QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

        $QR = imagecreatefromstring(file_get_contents($filename));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        imagepng($QR, $filename);
    }

    //设置缓存标记 + 输出二维码图片
    static public function getWorkAppVerifyQRCode($sessionID, $userID, $method, $string=NULL, $expire = 300)
    
    {
        //基于sessoin_id的key
        $cacheID = 'smmm_' . md5( "{$sessionID}{$userID}$method" );

        //时间戳（10位） + 处理方法名（模块_方法）
        $data = '_' . strval( time() ) . "|$method|$sessionID|" . $string;
        ( new cache() )->set( $cacheID, array('data' => $data), $expire );

        //返回二维码图片
        self::getOutHtml( $data );
        return;
    }

    //获取cache的key
    static public function getWorkAppVerifyCacheKey($sessionID, $userID, $method)
    {
        return  'smmm_' . md5( "{$sessionID}{$userID}$method" );
    }

    //获取cache的数据
    static public function getWorkAppVerifyData($sessionID, $userID, $method)
    {
        return (new cache())->get( self::getWorkAppVerifyCacheKey($sessionID, $userID, $method) );
    }

}
