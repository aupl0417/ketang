<?
/*
	* card manager for sybase.
	* Copyright (c) 2003 by lzhang.
	* Author: <zdf7919@sina.com.cn>
	* $Id: des.php 2003/07/15 08:55:27 lzhang Exp $
	* DES加密解密PHP版
*/
/*
	方法：将对字符串的加密转换成对ASCII数的加密
*/
class DES_CLASS
{
	//初始置换IP=[64]
	var $IP_Tbl = array(
			58, 50, 42, 34, 26, 18, 10, 2, 60, 52, 44, 36, 28, 20, 12, 4,
			62, 54, 46, 38, 30, 22, 14, 6, 64, 56, 48, 40, 32, 24, 16, 8,
			57, 49, 41, 33, 25, 17,  9, 1, 59, 51, 43, 35, 27, 19, 11, 3,
			61, 53, 45, 37, 29, 21, 13, 5, 63, 55, 47, 39, 31, 23, 15, 7
					);
	//逆转换IP^-1=[64]
	var $IPR_Tbl = array(
			40, 8, 48, 16, 56, 24, 64, 32, 39, 7, 47, 15, 55, 23, 63, 31,
			38, 6, 46, 14, 54, 22, 62, 30, 37, 5, 45, 13, 53, 21, 61, 29,
			36, 4, 44, 12, 52, 20, 60, 28, 35, 3, 43, 11, 51, 19, 59, 27,
			34, 2, 42, 10, 50, 18, 58, 26, 33, 1, 41,  9, 49, 17, 57, 25
					);
	//扩展用矩阵=[48]
	var $E_Tbl = array(
			32,  1,  2,  3,  4,  5,  4,  5,  6,  7,  8,  9,
	 		 8,  9, 10, 11, 12, 13, 12, 13, 14, 15, 16, 17,
			16, 17, 18, 19, 20, 21, 20, 21, 22, 23, 24, 25,
			24, 25, 26, 27, 28, 29, 28, 29, 30, 31, 32,  1
					);
	//32位置换函数 P 用于输出=[32]
	var $P_Tbl = array(
			16, 7, 20, 21, 29, 12, 28, 17, 1,  15, 23, 26, 5,  18, 31, 10,
			2,	8, 24, 14, 32, 27, 3,  9,  19, 13, 30, 6,  22, 11, 4,  25
					);
	//序号选择表=[56]
	var $PC1_Tbl = array(
			57, 49, 41, 33, 25, 17,  9,  1, 58, 50, 42, 34, 26, 18,
			10,  2, 59, 51, 43, 35, 27, 19, 11,  3, 60, 52, 44, 36,
			63, 55, 47, 39, 31, 23, 15,  7, 62, 54, 46, 38, 30, 22,
			14,  6, 61, 53, 45, 37, 29, 21, 13,  5, 28, 20, 12,  4
					);
	// permuted choice key (Tbl) =[48]
	var $PC2_Tbl = array(
			14, 17, 11, 24,  1,  5,  3, 28, 15,  6, 21, 10,
			23, 19, 12,  4, 26,  8, 16,  7, 27, 20, 13,  2,
			41, 52, 31, 37, 47, 55, 30, 40, 51, 45, 33, 48,
			44, 49, 39, 56, 34, 53, 46, 42, 50, 36, 29, 32
					);
	// number left rotations of pc1 =[16]
	var $LOOP_Tbl = array(
			1,1,2,2,2,2,2,2,1,2,2,2,2,2,2,1
					);
	// The (in)famous S-boxes =[8][4][16]
	var $S_Box = // S1
	array(14, 4,	13,	 1,  2, 15, 11,  8,  3, 10,  6, 12,  5,  9,  0,  7,
	 0, 15,  7,  4, 14,  2, 13,  1, 10,  6, 12, 11,  9,  5,  3,  8,
	 4,  1, 14,  8, 13,  6,  2, 11, 15, 12,  9,  7,  3, 10,  5,  0,
	15, 12,  8,  2,  4,  9,  1,  7,  5, 11,  3, 14, 10,  0,  6, 13,
	// S2
	15,  1,  8, 14,  6, 11,  3,  4,  9,  7,  2, 13, 12,  0,  5, 10,
	 3, 13,  4,  7, 15,  2,  8, 14, 12,  0,  1, 10,  6,  9, 11,  5,
	 0, 14,  7, 11, 10,  4, 13,  1,  5,  8, 12,  6,  9,  3,  2, 15,
	13,  8, 10,  1,  3, 15,  4,  2, 11,  6,  7, 12,  0,  5, 14,  9,
	// S3
	10,  0,  9, 14,  6,  3, 15,  5,  1, 13, 12,  7, 11,  4,  2,  8,
	13,  7,  0,  9,  3,  4,  6, 10,  2,  8,  5, 14, 12, 11, 15,  1,
	13,  6,  4,  9,  8, 15,  3,  0, 11,  1,  2, 12,  5, 10, 14,  7,
	 1, 10, 13,  0,  6,  9,  8,  7,  4, 15, 14,  3, 11,  5,  2, 12,
	// S4
	 7, 13, 14,  3,  0,  6,  9, 10,  1,  2,  8,  5, 11, 12,  4, 15,
	13,  8, 11,  5,  6, 15,  0,  3,  4,  7,  2, 12,  1, 10, 14,  9,
	10,  6,  9,  0, 12, 11,  7, 13, 15,  1,  3, 14,  5,  2,  8,  4,
	 3, 15,  0,  6, 10,  1, 13,  8,  9,  4,  5, 11, 12,  7,  2, 14,
	// S5
	 2, 12,  4,  1,  7, 10, 11,  6,  8,  5,  3, 15, 13,  0, 14,  9,
	14, 11,  2, 12,  4,  7, 13,  1,  5,  0, 15, 10,  3,  9,  8,  6,
	 4,  2,  1, 11, 10, 13,  7,  8, 15,  9, 12,  5,  6,  3,  0, 14,
	11,  8, 12,  7,  1, 14,  2, 13,  6, 15,  0,  9, 10,  4,  5,  3,
	// S6
	12,  1, 10, 15,  9,  2,  6,  8,  0, 13,  3,  4, 14,  7,  5, 11,
	10, 15,  4,  2,  7, 12,  9,  5,  6,  1, 13, 14,  0, 11,  3,  8,
	 9, 14, 15,  5,  2,  8, 12,  3,  7,  0,  4, 10,  1, 13, 11,  6,
	 4,  3,  2, 12,  9,  5, 15, 10, 11, 14,  1,  7,  6,  0,  8, 13,
	// S7
	 4, 11,  2, 14, 15,  0,  8, 13,  3, 12,  9,  7,  5, 10,  6,  1,
	13,  0, 11,  7,  4,  9,  1, 10, 14,  3,  5, 12,  2, 15,  8,  6,
	 1,  4, 11, 13, 12,  3,  7, 14, 10, 15,  6,  8,  0,  5,  9,  2,
	 6, 11, 13,  8,  1,  4, 10,  7,  9,  5,  0, 15, 14,  2,  3, 12,
	// S8
	13,  2,  8,  4,  6, 15, 11,  1, 10,  9,  3, 14,  5,  0, 12,  7,
	 1, 15, 13,  8, 10,  3,  7,  4, 12,  5,  6, 11,  0, 14,  9,  2,
	 7, 11,  4,  1,  9, 12, 14,  2,  0,  6, 10, 13, 15,  3,  5,  8,
	 2,  1, 14,  7,  4, 10,  8, 13, 15, 12,  9,  0,  3,  5,  6, 11
	 );
//////////////////////////////////////////////////////////////////////////
	var $SubKey=array(array());	//16圈子密钥=[16][48]
	var $strary=array();		//源码所对应的数组
	var $keyary=array();		//密匙数组
//////////////////////////////////////////////////////////////////////////
//初始化密匙及原文
function initary($key,$str){
	for($i=0;$i<8;$i++)			//8位密匙
		$this->keyary[$i]=ord($key[$i]);
	for($i=0;$i<8;$i++)
		$this->strary[$i]=ord($str[$i]);
	}
//循环左移
function RotateL($In,$len,$loop)
{
	$Tmp=array();
	for($i=0;$i<$loop;$i++)
		$Tmp[$i]=$In[$i];
	for($i=0;$i<$len-$loop;$i++)
		$In[$i]=$In[$i+$loop];
	for($i=0;$i<$loop;$i++)
		$In[$len-$loop+$i]=$Tmp[$i];
}
//把位变换成字节
function BitToByte($Out,$In,$bits)
{
	for($i=0;$i<$bits;$i++){
		$Out[$i/8] |= $In[$i]<<($i%8);
		}
}
//置换操作
function Transform($Out,$In,$Tbl,$len){
	static $Tmp=array();
	for($i=0;$i<$len;$i++)
		$Tmp[$i] = $In[$Tbl[$i]-1 ];
	for($i=0;$i<$len;$i++)
		$Out[$i]=$Tmp[$i];
}
//设置加密因子
function SetKey(){
	$K=array();$Rk=array();$Lk=array();
	$this->ByteToBit(&$K,$this->keyary,64);
	$this->Transform(&$K,$K,$this->PC1_Tbl, 56);
	for($i=0; $i<16; $i++) {
		for($j=0;$j<28;$j++){
			$Rk[$j]=$K[$j+28];
			$Lk[$j]=$K[$j];
			}
		$this->RotateL(&$Rk,28,$this->LOOP_Tbl[$i]);
		$this->RotateL(&$Lk,28,$this->LOOP_Tbl[$i],28);
		for($j=0;$j<28;$j++){
			$K[$j]=$Lk[$j];
			$K[$j+28]=$Rk[$j];
			}
		$this->Transform(&$this->SubKey[$i],$K,$this->PC2_Tbl, 48);
		}
	}
//将In字符串转换成64位二进制位到Out数组中
function ByteToBit($Out,$In,$bits){
	for($i=0; $i<$bits; $i++){
		$Out[$i]=($In[$i/8]>>($i%8)) & 1;
		}
	}
//S操作
function S_func($Out,$In)
{	$Tmp=array();
	for($i=0;$i<8;$i++) {
		$j=($In[0+$i*6]<<1)+$In[5+$i*6];
		$k=($In[1+$i*6]<<3)+($In[2+$i*6]<<2)+($In[3+$i*6]<<1)+ $In[4+$i*6];
		for($x=0;$x<4;$x++){
			$Tmp[$x]=($this->S_Box[$x/8+$i*64+$j*16+$k]>>($x%8))&1;
			$Out[$i*4+$x]=$Tmp[$x];
			}
		}
}
//F操作
function F_func($In,$Ki)	//In[32],Ki[48]
{
	$MR=array();			//MR[48]
	$this->Transform(&$MR,$In,$this->E_Tbl,48);
	for($i=0;$i<48;$i++){
		$MR[$i]^=$Ki[$i];		//异或处理
		}
	$this->S_func(&$In,$MR);
	$this->Transform(&$In,$In,$this->P_Tbl, 32);
}
//加解密
function Run($Out,$key,$str,$type=0){
	$M=array();$Rm=Array();$Lm=array();
	$this->initary($key,$str);
	$this->SetKey();
	$this->ByteToBit(&$M,$this->strary,64);
	$this->Transform(&$M,$M,$this->IP_Tbl,64);
	if($type==0)	//加密
		 for($i=0;$i<16;$i++) {
		 	for($j=0;$j<32;$j++){
		 		$Rm[$j]=$M[32+$j];	//右32位
		 		$Lm[$j]=$M[$j];		//左32位
		 		$Tmp[$j]=$Rm[$j];	//保存右32位
		 		}
			$this->F_func(&$Rm,$this->SubKey[$i]);//右32位
			for($j=0;$j<32;$j++)
				$Rm[$j]^=$Lm[$j];		//异或处理

			for($j=0;$j<32;$j++)		//置左32位为原先的右32位
				$Lm[$j]=$Tmp[$j];
			for($j=0;$j<32;$j++){		//重置数组M
				$M[$j]=$Lm[$j];
				$M[$j+32]=$Rm[$j];
				}
			}
	else for($i=15; $i>=0; $i--){
		 	for($j=0;$j<32;$j++){
		 		$Rm[$j]=$M[32+$j];	//右32位
		 		$Lm[$j]=$M[$j];		//左32位
		 		$Tmp[$j]=$Lm[$j];	//保存右32位
		 		}
			$this->F_func(&$Lm,$this->SubKey[$i]);
			for($j=0;$j<32;$j++)
				$Lm[$j]^=$Rm[$j];
			for($j=0;$j<32;$j++)
				$Rm[$j]=$Tmp[$j];
			for($j=0;$j<32;$j++){		//重置数组M
				$M[$j]=$Lm[$j];
				$M[$j+32]=$Rm[$j];
				}
			}
	$this->Transform(&$M,$M,$this->IPR_Tbl, 64);
	$this->BitToByte(&$Out,$M, 64);
	}
//加密
function Encode($key,$str){
	$ret=array();
	$this->Run(&$ret,$key,$str);
	return $this->arytostr($ret);
	}
//解密
function Decode($key,$str){
	$ret=array();
	$this->Run(&$ret,$key,$str,1);
	return $this->arytostr($ret);
	}
//将数组转换成字串
function arytostr($ary){
	$ret="";
	for($i=0;$i<count($ary);$i++){
		$ret.=chr($ary[$i]);
		}
	return $ret;
	}
}
//
/*/	DES字符加密解密
//		$type	=	0	->使用$key加密$str;
//				=	1	->使用$key解密$str;
//*/
function DES($str,$key="",$type=0){
	$des=new DES_CLASS;	
	switch ($type){
		case 0	:
		default	: //加密encode
				$ret="";
				for($i=0;$i<strlen($str)/8;$i++){
					$src=substr($str,$i*8,8);
					$ret.=$des->Encode($key,$src);
					}
				break;
		case 1	: //解密decode
				$ret="";
				for($i=0;$i<strlen($str)/8;$i++){
					$src=substr($str,$i*8,8);
					$ret.=$des->Decode($key,$src);
					}
				$str="";
				for($i=0;$i<strlen($ret);$i++)
					if($ret[$i]!=chr(0)) $str.=$ret[$i];
				$ret=$str;
				break;
		}
	return $ret;
	}
//MD5加密
function Md5Encode($str){
	return $str;
	}
	
//十六进制字串到ASCII字符
function HexToStr($hex){
	$ret="";
	for($i=0;$i<strlen($hex);$i+=2)
		$ret.=chr(HexDec(substr($hex,$i,2)));
	return $ret;
}
//ASCII字符到十六进制字串
function StrToHex($str){
	$ret="";
	for($i=0;$i<strlen($str);$i++){
		$ch = DecHex(ord($str[$i]));
		if(strlen($ch) == 1) $ch="0".$ch;
		$ret.=$ch;
//		print "<br>str[$i]=$ch";
	}
	return strtoupper($ret);
}


/*des例*/

$key="1979818";
$str="留言板不经安装设置是不能使用的安装设置过程并不是要故意使的留言板看上去很复杂而是要让你熟悉、理解并正确的使用留言板的后台设置程序";
//$str="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
//$str="1fgsdf._+e";

print "key=$key<br>";
print "str=$str<br>";
$a=DES($str,$key);
$b=DES($a,$key,1);
$c= StrToHex($a);
$d= HexToStr($c);
$e= DES($d,$key,1);

$base64_en = base64_encode($a);
$base64_de = base64_decode($base64_en);
$de_from_base64 = DES($base64_de,$key,1);

print "<br>encode=$a,".strlen($a);
print "<br>encode=$a,".strlen($a);
print "<br>decode=".nl2br($b).",".strlen($b);

print "<br>encode-StrToHex()=$c,".strlen($c);
print "<br>encode-HexToStr()=$d,".strlen($d);
print "<br>decode-2=$e,".strlen($e);


print "<br>base64_en=$base64_en,".strlen($base64_en);
print "<br>base64_de=$base64_de,".strlen($base64_de);
print "<br>de_from_base64=$de_from_base64,".strlen($de_from_base64);


print "<br> CRYPT_STD_DES :".DES('test','12');


?>
