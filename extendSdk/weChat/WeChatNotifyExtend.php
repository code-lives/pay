<?php

/**
 * Created by PhpStorm.
 * User: Li Jie
 2017/12/27
: 17:57
 */

namespace Pays\payment\extendSdk\weChat;

use Pays\payment\sdk\wechatPay\lib\WxPayOrderQuery;

class WeChatNotifyExtend extends \Pays\payment\sdk\wechatPay\lib\WxPayNotify
{

    public $errorMsg = '';
    public $result;
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new \Pays\payment\sdk\wechatPay\lib\WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \Pays\payment\sdk\wechatPay\lib\WxPayApi::orderQuery($input);
        if (
            array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {

        if (!array_key_exists("transaction_id", $data)) {
            $this->errorMsg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["transaction_id"])) {
            $this->errorMsg = "订单查询失败";
            return false;
        }
        return true;
    }
}
