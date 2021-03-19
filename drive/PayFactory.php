<?php

/**
 * Created by PhpStorm.
 * User: Li Jie

: 11:03
 */

namespace Pays\payment\drive;



class PayFactory
{
    private static $payDrive = [
        'AliPay' => '\Pays\payment\drive\AliPay',
        'WeChatPay' => '\Pays\payment\drive\WeChatPay',
        'BeijinPay' => '\Pays\payment\drive\BeijinPay',
        'UnionPay' => '\Pays\payment\drive\UnionPay',
        'PaySsion' => '\Pays\payment\drive\PaySsion',
        'PayPal' => '\Pays\payment\drive\PayPal',
        'ApplePay' => '\Pays\payment\drive\ApplePay',
        'tuoTiao' => '\Pays\payment\drive\TouTiaoPay'
    ];

    /**
     * 返回单例
     * @param $payInstanceName
     * @return AliPay|WeChatPay|ApplePay|BeijinPay|UnionPay|PaySsion|PayPal|TouTiaoPay
     */
    public static function getInstance($payInstanceName)
    {
        static $class;
        if (isset($class[$payInstanceName])) return $class[$payInstanceName];
        return $class[$payInstanceName] = new self::$payDrive[$payInstanceName]();
    }

    /**
     * 获取各个app支付需要的参数
     * @param $sets
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn
     * @return array
     */
    public static function getAppsParam($sets, $outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        $return = [];
        foreach (self::$payDrive as $name => $class) {
            $return[$name] = static::getInstance($name)
                ->init($sets[$name])
                ->set($outOrderSn, $amount, $title, $body, $intOrderSn)
                ->getAppParam();
        }
        return $return;
    }
}
