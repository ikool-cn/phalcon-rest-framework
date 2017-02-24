<?php
namespace App\Core;

use \Phalcon\Logger\Formatter;
use \Phalcon\Http\Request;
use \App\Library\Helper;

class LoggerJsonFormatter extends Formatter
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
        $context['logid '] = self::$logid;
        $context['uri '] = self::$uri;
        $context['method '] = self::$method;
        $context['clientIp '] = self::$clientIp;
        $context['time'] = date('Y-m-d H:i:s', $timestamp);
        $context['log_type'] = $this->getTypeString($type);
        if (!empty($message)) {
            $context['msg'] = $message;
        }
        $json_message = json_encode($context, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR);
        return $json_message . "\n";
    }
}