<?php
namespace App\Core;

use \Phalcon\Logger\Formatter;
use \Phalcon\Http\Request;
use \App\Library\Helper;

class LoggerLineFormatter extends Formatter
{
    static $logid = null;
    static $uri = null;
    static $clientIp = null;
    static $method = null;

    public function format($message, $type, $timestamp, $context = NULL)
    {
        if (!self::$logid) {
            $request = new Request();
            self::$logid = Helper::getLogId();
            self::$uri = $request->getURI();
            self::$clientIp = $request->getClientAddress();
            self::$method = $request->getMethod();
        }
        return sprintf('%s [%s %s %s %s %s] %s %s %s', $this->getTypeString($type), self::$logid, date('Y-m-d H:i:s', $timestamp), self::$clientIp, self::$method, self::$uri, $message, json_encode($context, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR), PHP_EOL);
    }
}