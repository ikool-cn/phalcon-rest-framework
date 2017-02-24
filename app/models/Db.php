<?php
namespace App\Model;

use Phalcon\Db\Adapter\Pdo\Mysql;

class Db
{
    protected $db;
    protected $tb;
    private $config;

    static $read = null;
    static $write = null;
    static $is_master = false;

    /**
     * 读取全局配置
     * Conn constructor.
     */
    function __construct()
    {
        if(empty($this->tb)) {
            throw new \Exception(sprintf('%s,table name must be defined', get_called_class()));
        }

        global $config;
        $this->config = $config;
        $this->tb = $this->config['database']['tb_prefix'] . $this->tb;
    }

    /**
     * 获取读链接
     * @return null|Mysql
     */
    function getReadConnection()
    {
        if (!self::$read) {
            $rand_key = array_rand($this->config['database']['read']);
            $cfg = $this->config['database']['read'][$rand_key];
            self::$read = new Mysql(
                [
                    "host" => $cfg['host'],
                    "username" => $cfg['username'],
                    "password" => $cfg['password'],
                    "dbname" => $cfg['name'],
                    "options" => $cfg['options'],
                ]
            );
        }
        return self::$read;
    }

    /**
     * 获取写链接
     * @return null|Mysql
     */
    function getWriteConnection()
    {
        if (!self::$write) {
            $cfg = $this->config['database']['write'];
            self::$write = new Mysql(
                [
                    "host" => $cfg['host'],
                    "username" => $cfg['username'],
                    "password" => $cfg['password'],
                    "dbname" => $cfg['name'],
                    "options" => $cfg['options'],
                ]
            );
        }
        return self::$write;
    }

    /**
     * 设置强制从主库读取
     * @param bool $master
     */
    public function setMaster($master = false)
    {
        self::$is_master = $master;
    }

    /**
     * 链接数据库
     * @param bool $is_master
     */
    protected function conn($is_master = false)
    {
        if (self::$is_master) {
            $is_master = true;
        }
        if ($is_master) {
            $this->db = $this->getWriteConnection();
        } else {
            $this->db = $this->getReadConnection();
        }
    }
}