<?php
namespace App\Core;

use Phalcon\Logger\Adapter as LoggerAdapter;

class ErrorHandler
{

    const ERROR_HANDLER = 'errorHandler';

    const EXCEPTION_HANDLER = 'exceptionHandler';

    const SHUTDOWN_HANDLER = 'shutdownHandler';

    protected static $logger = null;

    public function __construct()
    {
        switch (ENV) {
            case 'pro':
            default:
                ini_set('display_errors', 0);
                error_reporting(0);
                break;
            case 'dev':
            case 'test':
                ini_set('display_errors', 1);
                error_reporting(-1);
                break;
        }
    }

    public static function register($logger = null)
    {
        if (isset($logger) && ($logger instanceof LoggerAdapter)) {
            self::$logger = $logger;
        }

        $handler = new static();
        set_error_handler([$handler, self::ERROR_HANDLER]);
        set_exception_handler([$handler, self::EXCEPTION_HANDLER]);
        register_shutdown_function([$handler, self::SHUTDOWN_HANDLER]);
        return $handler;
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // Only handle errors that match the error reporting level.
        /*if (!($errno & error_reporting())) {
            if(null !== self::$logger) {
                self::$logger->warning(sprintf("%s, errno=%s, file=%s, line=%s", $errstr, $errno, $errfile, $errline));
            }
            return true;
        }*/

        if (null !== self::$logger) {
            $errMsg = sprintf("%s, errno=%s, file=%s, line=%s", $errstr, $errno, $errfile, $errline);
            switch ($errno) {
                case E_ERROR:
                case E_RECOVERABLE_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                default:
                    self::$logger->error($errMsg);
                    break;
                case E_WARNING:
                case E_USER_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                    self::$logger->warning($errMsg);
                    return;
                case E_NOTICE:
                case E_USER_NOTICE:
                    self::$logger->notice($errMsg);
                    return;
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    self::$logger->info($errMsg);
                    return;
            }

        }
        $code = 500;
        $error = [
            'code' => $code,
            'error' => "Internal Server Error",
        ];
        if (ENV != 'pro') {
            $error['detail'] = [
                'errstr' => $errstr,
                'errno' => $errno,
                'errfile' => $errfile,
                'errline' => $errline,
            ];
        }
        $this->displayError($error, $code);
    }

    public function exceptionHandler($e)
    {
        $code = $e->getCode();
        if ($code > 600 || $code < 400) {
            $code = 500;
        }

        if (null !== self::$logger) {
            self::$logger->error(sprintf("%s, errno=%s, file=%s, line=%s", $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine()));
        }

        $this->displayError($this->formatException($e), $code);
    }

    /**
     * 输出错误到页面
     * @param string $error
     * @param int $code
     */
    public function displayError($error = '', $code)
    {
        if (0 === strpos(PHP_SAPI, 'cli')) {
            print_r($error);
            exit(1);
        }

        ob_get_level() and ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json;charset=utf-8;', true, $code);
        }
        print json_encode($error, JSON_PRETTY_PRINT);
        exit;
    }

    public function shutdownHandler()
    {
        // We can't throw exceptions in the shutdown handler.
        $error = error_get_last();
        if ($error) {
            $this->errorHandler(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }

    public function formatException($e)
    {
        $error = [
            'code' => $e->getCode(),
            'error' => $e->getMessage(),
        ];

        if (ENV != 'pro') {
            $error['exception'] = [];
            do {
                $error['exception'][] = [
                    'type' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            } while ($exception = $e->getPrevious());
        }
        return $error;
    }

    public function __destruct()
    {
        if (null !== self::$logger) {
            self::$logger->commit();
        }
    }
}
