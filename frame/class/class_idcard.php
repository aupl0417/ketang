<?php

/**
 *身份证校验信息类
 *
 * @author flybug
 * @version 1.0.0
 */
class idcard
{
    //身份证验证函数
    //计算身份证校验码，根据国家标准GB 11643-1999
    static public function idcard_verify_number($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $verify_number_list = array(1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2);
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor [$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    // 将15位身份证升级到18位
    static public function idcard_15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array(996, 997, 998, 999)) !== false) {
                $idcard = substr($idcard, 0, 6) . 18 . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . 19 . substr($idcard, 6, 9);
            }
        }
        return $idcard . idcard::idcard_verify_number($idcard);
    }

    // 18位身份证校验码有效性检查
    static public function idcard_checksum18($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        return (idcard::idcard_verify_number($idcard_base) == strtoupper(substr($idcard, 17, 1)));
    }

    //非法地区
    static public function idcard_checkarea($idcard)
    {
        $aCity = array(11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古", 21 => "辽宁", 22 => "吉林", 23 => "黑龙江", 31 => "上海", 32 => "江苏", 33 => "浙江", 34 => "安徽", 35 => "福建", 36 => "江西", 37 => "山东", 41 => "河南", 42 => "湖北", 43 => "湖南", 44 => "广东", 45 => "广西", 46 => "海南", 50 => "重庆", 51 => "四川", 52 => "贵州", 53 => "云南", 54 => "西藏", 61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => "宁夏", 65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外");
        return (array_key_exists(substr($idcard, 0, 2), $aCity));
    }

    //验证生日
    static public function idcard_checkbirth($idcard)
    {
        return substr($idcard, 10, 2) . substr($idcard, 12, 2) . substr($idcard, 6, 4);
        return (checkdate(substr($idcard, 10, 2), substr($idcard, 12, 2), substr($idcard, 6, 4)));
    }

    //验证生日2
    static public function idcard_checkbirthEx($birthday)
    {
        if ($birthday == '') {
            return false;
        }
        return (preg_replace('/(19|20)[0-9]{2}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', '', $birthday) == '');
    }

    //综合校验身份证
    static public function idcard_checkIDCard($idcard)
    {
        if (preg_replace('/[0-9]{14,17}[0-9Xx]$/', '', $idcard) != '') {
            return '输入的身份证号非法，请核对。';
        }
        if (strlen($idcard) == 15) {
            $idcard = idcard::idcard_15to18($idcard);
        }
        if (!idcard::idcard_checksum18($idcard)) {
            return '输入的身份证号校验位不对，请核对。';
        }
        if (!idcard::idcard_checkarea($idcard)) {
            return '输入的身份证区域代码不对，请核对。';
        }
        if (!idcard::idcard_checkbirth($idcard)) {
            return '输入的身份证生日信息不对，请核对。';
        }
        return 1;
    }

    //得到区域代码、生日、性别。返回值为数组。
    static public function idcard_getMyInfo($idcard)
    {
        $info['flag'] = idcard::idcard_checkIDCard($idcard);
        if ($info['flag'] == 1) {
            $info['area'] = substr($idcard, 0, 6);
            $info['birth'] = substr($idcard, 6, 4) . '-' . substr($idcard, 10, 2) . '-' . substr($idcard, 12, 2);
            $info['sex'] = (substr($idcard, 16, 1) % 2);
        }
        return $info;
    }
}

?>