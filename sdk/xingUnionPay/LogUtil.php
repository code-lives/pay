<?php

/**
 * Created by PhpStorm.
 * User: Li Jie
 2018/7/9
: 11:28
 */

namespace Pays\payment\sdk\UnionPay;

use Pays\payment\sdk\UnionPay\SDKConfig;
use Pays\payment\sdk\UnionPay\PhpLog;

class LogUtil
{
    private static $_logger = null;
    public static function getLogger()
    {
        if (LogUtil::$_logger == null) {
            $l = SDKConfig::getSDKConfig()->logLevel;
            if ("INFO" == strtoupper($l))
                $level = PhpLog::INFO;
            else if ("DEBUG" == strtoupper($l))
                $level = PhpLog::DEBUG;
            else if ("ERROR" == strtoupper($l))
                $level = PhpLog::ERROR;
            else if ("WARN" == strtoupper($l))
                $level = PhpLog::WARN;
            else if ("FATAL" == strtoupper($l))
                $level = PhpLog::FATAL;
            else
                $level = PhpLog::OFF;
            LogUtil::$_logger = new PhpLog(SDKConfig::getSDKConfig()->logFilePath, "PRC", $level);
        }
        return self::$_logger;
    }
}
