<?php

class calculation {

    //手续费计算
    static function getFee($type) {
        switch ($type) {
            case 1://红积分到佣金10%
                break;
            case 2:
                break;
        }
    }

    //根据业务计算转账数据
    static function getTransferDataByBusid($busid, $data) {
        switch ($busid) {
            case 310://预库-预存款购买库存积分
                $data = round($data * 625, 2);
                break;
            case 311://红佣-红积分兑换佣金
                $data = round($data / 100, 2);
                break;
            case 402://库预-撤销预存款购买库存积分
                $data = F::bankerDIv($data, 625);
                break;
        }
        return $data;
    }

}
