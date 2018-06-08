<?php

/**
 * 加密类
 *
 * $key 密钥
 * $iv 随机的初始向量，必须唯一，和$key配合进行加密和解密
 *
 * @author flybug
 * @version 1.0.0
 */


//测试代码
//$key = 'abcdefgh';
//$iv = 'abcdefgh';
//$msg = '中文的测试 test string';
//$des = new STD3Des($key);
//$rs1 = $des->encrypt($msg);
//echo '<meta http-equiv="content-type" content="text/html; charset=UTF-8" />';
//echo $rs1 . 'a<br />';
//$rs2 = $des->decrypt($rs1);
//echo $rs2;

class STD3Des
{
    private $key = "";
    private $iv = "";

    /**
     * 构造，传递二个已经进行base64_encode的KEY与IV
     *
     * @param string $key
     * @param string $iv
     */
    function __construct($key, $iv = PASSKEYWORD)
    {
        if (empty($key) || empty($iv)) {
            echo 'key and iv is not valid';
            exit();
        }
        $this->key = base64_encode($key);
        $this->iv = base64_encode($iv);
        $this->key = $key;
        $this->iv = $iv;
    }

    /**
     *加密
     * @param <type> $value
     * @return <type>
     */
    public function encrypt($value)
    {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $iv = base64_decode($this->iv);
        $value = $this->PaddingPKCS7($value);
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $iv);
        $ret = base64_encode(mcrypt_generic($td, $value));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    /**
     *解密
     * @param <type> $value
     * @return <type>
     */
    public function decrypt($value)
    {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        $iv = base64_decode($this->iv);
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $iv);
        $ret = trim(mdecrypt_generic($td, base64_decode($value)));
        $ret = $this->UnPaddingPKCS7($ret);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    private function paddingPKCS7($data)
    {
        $block_size = mcrypt_get_block_size('tripledes', 'cbc');
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }

    private function unpaddingPKCS7($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }
}


class TripleDES {  
    public static function genIvParameter() {  
        return mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES,MCRYPT_MODE_CBC), MCRYPT_RAND);  
    }  
  
    private static function pkcs5Pad($text, $blocksize) {  
        $pad = $blocksize - (strlen($text) % $blocksize); // in php, strlen returns the bytes of $text  
        return $text . str_repeat(chr($pad), $pad);  
    }  
  
    private static function pkcs5Unpad($text) {  
        $pad = ord($text{strlen($text)-1});  
        if ($pad > strlen($text)) return false;  
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;  
        return substr($text, 0, -1 * $pad);  
    }  
  
    public static function encryptText($plain_text, $key, $iv) {  
        $padded = TripleDES::pkcs5Pad($plain_text, mcrypt_get_block_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_CBC));  
        return mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $padded, MCRYPT_MODE_CBC, $iv);  
    }  
  
    public static function decryptText($cipher_text, $key, $iv) {  
        $plain_text = mcrypt_decrypt(MCRYPT_TRIPLEDES, $key, $cipher_text, MCRYPT_MODE_CBC, $iv);  
        return TripleDES::pkcs5Unpad($plain_text);  
    }  
};


?>