<?php

use Phalcon\Config;

$config =  new Config(
    [
        'database' => [
            'tb_prefix' => 'xd_',
            'write' => [
                'adapter' => 'Mysql',
                'host' => 'localhost',
                'username' => 'root',
                'password' => '123456',
                'name' => 'test',
                'options' => [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]
            ],

            'read' => [
                /*[
                    'adapter' => 'Mysql',
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => '123456',
                    'name' => 'test',
                    'options' => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]
                ],*/
                [
                    'adapter' => 'Mysql',
                    'host' => '192.168.1.250',
                    'username' => 'root',
                    'password' => 'xiaodao360',
                    'name' => 'xd_test',
                    'options' => [
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]
                ]
            ]
        ],

        'application' => [
            'modelsDir' => APP_PATH . '/models/',
            'coreDirs' => APP_PATH . '/core/',
            'libraryDirs' => APP_PATH . '/library/',
            'baseUri' => '/',
            'logDir'  => APP_PATH . '/caches/logs/',
        ],

        'models' => [
            'metadata' => [
                'adapter' => 'Memory'
            ]
        ],

        //jwt 中间件设置
        'auth_micro' => [
            'secretKey' => '923753F2317FC1EE5B52DF23951B1',
            'payload' => [
                'exp' => 1440,
                'iss' => 'phalcon-jwt-auth'
            ],
            'ignoreUri' => [
                /*'/',
                'regex:/application/',
                'regex:/users/:POST,PUT',
                '/auth/user:POST,PUT',
                '/auth/application',*/
                //'regex:/user/[0-9]+/profile:POST',
                '/user:GET,POST',
            ]
        ],

        'qiniu' => [
            'secrectKey' => 'KoCgPk5_Ivzb4WLvdTLtjObMJA3hLWr-lrCN9D5j',
            'accessKey' => 'WJCzf1_vNP7MDsIdnKFfsUsOKGGy2hs4czjCctRn',
            'domain' => 'http://image.xiaodaowang.cn/',
            'bucket' => 'image',
        ],

        'mail' => [
            'smtp_server' => 'smtp.exmail.qq.com', //邮件服务器
            'smtp_port' => 25, //邮件服务器端口
            'smtp_user_email' => 'service@xiaodao360.com', //SMTP服务器的用户邮箱(一般发件人也得用这个邮箱)
            'smtp_user' => 'service@xiaodao360.com', //SMTP服务器账户名
            'smtp_pwd' => '1234567x', //SMTP服务器账户密码
            'smtp_mail_type' => 'HTML', //发送邮件类型:HTML,TXT(注意都是大写)
            'smtp_time_out' => 30, //超时时间
            'smtp_auth' => true,
            'smtp_from_name' => '校导'
        ],

        'beanstalk' => [
            'host'  => '192.168.1.250',
            'prefix'=> 'xiaodao_'
        ]
    ]
);
return $config->toArray();
