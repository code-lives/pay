<?php

/**
 * Created by PhpStorm.
 * User: Li Jie
 * 使用示例:
 * $pay = AliPay::init([])->set('111', 9.9)->params([]);
 * $sign = $pay->getSign();
 *
 * @property \Pays\payment\sdk\aliPay\aop\AopClient $AopClient
 */

namespace Pays\payment\drive;

use Pays\payment\sdk\aliPay\aop\AopClient;
use Pays\payment\sdk\aliPay\aop\request\AlipayTradeAppPayRequest;

/**
 * Class AliPay
 * @property string $notifyUrl
 * @property string $notifyRefundUrl
 * @property string $returnUrl
 * @property string $config
 * @package Pays\payment\drive
 */
class AliPay implements \Pays\payment\core\PayInterface
{

    public $notifyUrl = '';
    public $returnUrl = '';
    public $notifyRefundUrl = '';

    private $config;

    private $params;

    public $AopClient;

    public $request;


    /**
     * 初始化
     * @param $config
     * @return AliPay
     */
    public static function init($config)
    {
        $class = new self();
        $class->config = $config;
        $class->notifyUrl = $config['notifyUrl'];
        $class->returnUrl = $config['returnUrl'] ?? '';
        $class->notifyRefundUrl = $config['notifyRefundUrl'] ?? '';
        defined('AOP_SDK_WORK_DIR') ?: define("AOP_SDK_WORK_DIR", $config['logDir'] ?? sys_get_temp_dir() . '/');

        //        初始化支付宝和配置参数
        //        $class->AopClient = $class->getAopClient();
        //        返回本类自身
        return $class;
    }

    /**
     * @return AopClient
     */
    private function getAopClient()
    {
        $config = &$this->config;
        $aopClient = new AopClient();
        $aopClient->appId = $config['appId'];
        $aopClient->rsaPrivateKey = $config['rsaPrivateKey'];  // 请填写开发者私钥去头去尾去回车，一行字符串
        $aopClient->alipayrsaPublicKey = $config['alipayrsaPublicKey']; // 请填写支付宝公钥，一行字符串
        $aopClient->signType = $config['signType'] ?? 'RSA2';  // 签名方式
        $aopClient->format = $config['format'] ?? 'JSON';
        $aopClient->charset = $config['charset'] ?? 'utf-8';

        return $aopClient;
    }

    /**
     * 设置主要参数
     * @param $outOrderSn
     * @param $amount
     * @param string $title
     * @param string $body
     * @param string $intOrderSn 第三方支付平台生成的订单号，如支付宝支付时返回的支付宝交易号，不是每个平台都需要
     * @return AliPay
     */
    public function set($outOrderSn, $amount, $title = '', $body = '', $intOrderSn = '')
    {
        $this->params['out_trade_no'] = $outOrderSn;
        $this->params['body'] = $body;
        $this->params['subject'] = $title;
        $this->params['total_amount'] = number_format($amount, 2);
        //        $this->params['timeout_express'] = '30m';
        return $this;
    }

    public function params(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function customParams($value)
    {
        $this->params['passback_params'] = urlencode($value);
        return $this;
    }

    /**
     * @return string
     */
    public function getAppParam()
    {
        $request = new AlipayTradeAppPayRequest();
        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent(json_encode($this->params, JSON_UNESCAPED_UNICODE));
        return $this->getAopClient()->sdkExecute($request);
    }

    public function autoActionFrom()
    {
        $payRequestBuilder = new \Pays\payment\sdk\aliPay\wappay\buildermodel\AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($this->params['body']);
        $payRequestBuilder->setSubject($this->params['subject']);
        $payRequestBuilder->setOutTradeNo($this->params['out_trade_no']);
        $payRequestBuilder->setTotalAmount($this->params['total_amount']);
        $payRequestBuilder->setTimeExpress($this->params['timeout_express']);

        # 将参数转换为 支付宝 sdk 的参数
        $config = [
            'app_id' => $this->config['appId'],
            'merchant_private_key' => $this->config['rsaPrivateKey'],
            //异步通知地址
            'notify_url' => $this->notifyUrl,
            //同步跳转
            'return_url' => $this->returnUrl,
            'charset' => $this->config['charset'] ?? 'UTF-8',
            'sign_type' => $this->config['signType'] ?? 'RSA2',
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $this->config['alipayrsaPublicKey'],
        ];

        $payResponse = new \Pays\payment\sdk\aliPay\wappay\service\AlipayTradeService($config);
        $payResponse->wapPay($payRequestBuilder, $config['return_url'], $config['notify_url']);
        exit();
    }

    /**
     * 验签
     * @param null $post
     * @return bool
     */
    public function validate($post = null)
    {
        return $this->getAopClient()->rsaCheckV1($post, NULL, "RSA2");
    }

    /**
     * 原路退款
     * @param string $reason
     * @return bool|mixed|\SimpleXMLElement
     */
    public function refund($reason = '正常退款')
    {
        # 设置退款金额
        //        $this->params($this->params['total_amount']);
        $this->params(['refund_amount' => $this->params['total_amount']]);
        // 部分退款唯一订单号
        $this->params(['out_request_no' => $this->params['out_trade_no']]);
        unset($this->params['total_amount'], $this->params['out_trade_no']);
        if (empty($this->notifyRefundUrl)) throw new \Exception('未设置退款异步通知url');

        # 获取驱动
        $request = new \Pays\payment\sdk\aliPay\aop\request\AlipayTradeRefundRequest();
        $request->setNotifyUrl($this->notifyRefundUrl);
        $request->setBizContent(json_encode($this->params, JSON_UNESCAPED_UNICODE));
        $result = $this->getAopClient()->execute($request);
        if (empty($result)) return false;

        # 返回结果
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        return !empty($resultCode) && $resultCode == 10000;
    }
}
