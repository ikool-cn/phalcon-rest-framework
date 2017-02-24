<?php
namespace App\Library;
use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;

class Helper
{
    /**
     * 获取配置
     * @param $name
     * @param string $default
     * @return null|string
     */
    public static function getConfig($name, $default = '')
    {
        global $config;
        if (empty($name)) {
            return $config;
        }
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                $name = strtolower($name);
                return isset($config[$name]) ? $config[$name] : $default;
            }

            $name = explode('.', $name);
            $name[0] = strtolower($name[0]);
            return isset($config[$name[0]][$name[1]]) ? $config[$name[0]][$name[1]] : $default;
        }
        return null;
    }

    // 邮箱验证
    public static function isEmail($email)
    {
        return !!filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // 手机验证
    public static function isMobile($phone)
    {
        return preg_match("/^1[3-8][0-9]{9}$/", $phone);
    }

    //是否为URL
    public static function isUrl($str, $with_path = true)
    {
        if (empty($str)) {
            return true;
        }
        if ($with_path) {
            return !!filter_var($str, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
        }
        return !!filter_var($str, FILTER_VALIDATE_URL);
    }

    //是否是微信
    public static function isWeixin()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            return false;
        }
        return true;
    }

    /**
     * 生成日志ID
     * @return string
     */
    public static function getLogId()
    {
        return substr(md5(uniqid(rand(), true)), 16);
    }

    /**
     * 通过curl发送HTTP请求
     * @param string $url 接口url
     * @param array $data 数据
     * @param int $timeout curl允许执行的最长秒数
     * @param string $method 请求类型 GET|POST
     * @param array $headers 扩展的包头信息（如：["User-Agent: {$_SERVER['HTTP_USER_AGENT']}", "Referer: {$_SERVER['HTTP_REFERER']}"]）
     * @param bool $urlencode 请求类型为POST时是否对$data进行http_build_query处理(区别在于发送后的header头：数组发送后为 multipart/form-data, 字符串为x-www-form-urlencoded)
     * @param string $auth_basic 格式$username . ":" . $password
     * @param int $try_times 重试次数
     * @return array
     */
    public static function httpRequest($url, $data = [], $method = 'GET', $timeout = 5, $urlencode = true, $headers = [], $auth_basic = '', $try_times = 3)
    {
        if (!function_exists('curl_init')) {
            exit('curl extension not found');
        }
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);

        if (!empty($auth_basic)) {
            curl_setopt($ci, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ci, CURLOPT_USERPWD, $auth_basic);
        }

        $method = !empty($method) ? strtoupper($method) : 'GET';
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method);
        switch ($method) {
            case 'PUT':
            case 'POST':
                if (!empty($data)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $urlencode ? http_build_query($data) : $data);
                }
                break;
            case 'DELETE':
            case 'GET':
                if (!empty($data)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?') . (is_array($data) ? http_build_query($data) : $data);
                }
                break;
        }

        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        curl_setopt($ci, CURLOPT_URL, $url);

        $headers = (array)$headers;
        if ($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        }

        $i = 0;
        while (++$i <= $try_times) {
            $response = curl_exec($ci);
            if ($response !== false) {
                break;
            }
        }

        if ($response === false) {
            $msg = sprintf('http_request error, url=%s, params=%s, error=%s', $url, json_encode($data), curl_error($ci));
            //\Think\Log::error($msg);
        }

        curl_close($ci);
        return $response;
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @param string $type
     * @param int $convert
     * @return string
     */
    public static function random($length = 6, $type = 'string', $convert = 0)
    {
        $config = array(
            'number' => '1234567890',
            'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
            'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        );

        if (!isset($config[$type])) $type = 'string';
        $string = $config[$type];

        $code = '';
        $strlen = strlen($string) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $string{mt_rand(0, $strlen)};
        }
        if (!empty($convert)) {
            $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
        }
        return $code;
    }

    //获取七牛Token
    public static function getQiniuToken()
    {
        $config = self::getConfig('qiniu');
        require __DIR__ . '/../../vendor/qiniu/php-sdk/src/Qiniu/functions.php';

        //http://developer.qiniu.com/article/developer/security/put-policy.html
        $policy = [
            'mimeLimit' => 'image/*',
            'insertOnly' => 1,
            //'fsizeMin' => 2 * 1024,
            //'fsizeLimit' => 2 * 1024 * 1024,
        ];

        $token = (new \Qiniu\Auth($config['accessKey'], $config['secrectKey']))->uploadToken($config['bucket'], null, 31536000, $policy);

        return [
            'qiniu_token' => $token,
            'qiniu_domain' => $config['domain'],
            'qiniu_bucket' => $config['bucket']
        ];
    }

    /**
     * 消息入队
     * @param $tube
     * @param array $args
     * @param int $delay
     */
    public static function enqueue($tube, array $args, $delay = 0) {
        $config = self::getConfig('beanstalk');
        $beanstalk = new BeanstalkExtended([
            'host'   => $config['host'],
            'prefix' => $config['prefix'],
        ]);

        // Save the video info in database and send it to post-process
        $beanstalk->putInTube($tube, json_encode($args), ['delay' => $delay]);
    }

    /**
     * 同步发送邮件
     * @param $mailto
     * @param $subject
     * @param string $body
     * @return bool
     * @throws \phpmailerException
     */
    public static function sendMail($mailto, $subject, $body = '')
    {
        $config = self::getConfig('mail');

        require __DIR__ . '/../../vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

        $mail = new \PHPMailer;
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $config['smtp_server'];  // Specify main and backup SMTP servers
        $mail->SMTPAuth = $config['smtp_auth'];                               // Enable SMTP authentication
        $mail->Username = $config['smtp_user'];                 // SMTP username
        $mail->Password = $config['smtp_pwd'];                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $config['smtp_port'];                                    // TCP port to connect to

        $mail->setFrom($config['smtp_user_email'], $config['smtp_from_name']);

        if (is_string($mailto)) {
            $mailto = [$mailto];
        }
        if (is_array($mailto)) {
            foreach ($mailto as $m) {
                if (self::isEmail($m)) {
                    $mail->addAddress($m);
                }
            }
        }

        //$mail->addAddress('joe@example.net', 'Joe User');     // Add a recipient
        //$mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');

        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if (!$mail->send()) {
           // \Log::error(sprintf('mail send faild:%s', $mail->ErrorInfo));
            return false;
        }
        return true;
    }
}