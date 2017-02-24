<?php

namespace App\Service;
use App\Library\Helper;

class Sms
{
    protected static $suffix = '【校导科技】';

    /**
     * 畅卓发送短信
     * @param $mobile 多个用英文逗号分割
     * @param $message
     * @return bool
     */
    public static function send($mobile, $message)
    {
        $mobile = trim($mobile, ',');
        if ($mobile && $message && preg_match('/^(1[34578]\d{9},)+$/', $mobile . ',')) {
            $url = 'http://sms.chanzor.com:8001/sms.aspx';
            $input = [
                'action' => 'send',
                'userid' => '',
                'account' => 'xiaodaowang',
                'password' => '152750',
                'mobile' => $mobile,
                'sendTime' => '',
                'content' => $message . self::$suffix,
            ];
            $data = Helper::httpRequest($url, $input, 'POST');
            $result = json_decode(json_encode(simplexml_load_string($data)), true);
            if (isset($result['returnstatus']) && $result['returnstatus'] == 'Success') {
                return true;
            }
            //\Log::warning(sprintf("SMS send failed, errmsg=%s, mobile=%s, content=%s", json_encode($result), $mobile, $message));
            return false;
        }
        //\Log::warning(sprintf("SMS send failed, mobile=%s, message=%s", $mobile, $message));
        return false;
    }
}
