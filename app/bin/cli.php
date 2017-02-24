<?php
define('APP_PATH', dirname(__DIR__));
define('ENV', $_SERVER['ENV'] ?? 'dev');
set_time_limit(0);

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\DI\FactoryDefault;
use Phalcon\Logger\Adapter\File as FileAdapter;

/**
 * Read the configuration
 */
$config = include APP_PATH . '/config/config.php';

/**
 * Registering an autoloader
 */
$loader = new Loader();
/*$loader->registerDirs([
    $config->application->modelsDir,
]);*/

$loader->registerNamespaces([
    'App\Core' => $config['application']['coreDirs'],
    'App\Model' => $config['application']['modelsDir'],
    'App\Library' => $config['application']['libraryDirs'],
    'Qiniu' => APP_PATH . '/../vendor/qiniu/php-sdk/src/Qiniu',
    'Dmkit' => APP_PATH . '/../vendor/dmkit/phalcon-jwt-auth/src',
    'Firebase\JWT' => APP_PATH . '/../vendor/firebase/php-jwt/src',
    'Phalcon' => APP_PATH. '/../vendor/phalcon/incubator/Library/Phalcon/',
    'duncan3dc\Forker' => APP_PATH . '/../vendor/duncan3dc/fork-helper/src',
]);
$loader->register();

//define logger and start transactions
$logger = new FileAdapter($config['application']['logDir'] . 'app.log');
$logger->setFormatter(new App\Core\LoggerLineFormatter());
$logger->begin();

//register error handler
App\Core\ErrorHandler::register($logger);

//$di = new FactoryDefault();
//
////$logger->info(sprintf("request begin, method=%s, body(%s)", $di['request']->getMethod(), $di['request']->getRawBody()));
//
///**
// * set logger
// */
//$di->set('logger', $logger);
//
///**
// * JsonResponse extends Response
// */
//$di->set('json', function () {
//    $response = new App\Core\JsonResponse();
//    return $response;
//});