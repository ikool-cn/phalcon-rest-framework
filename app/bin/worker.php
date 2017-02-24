#!/usr/bin/env php
<?php
require 'cli.php';
/**
 * Workers that handles queues related to the videos.
 */
use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use Phalcon\Queue\Beanstalk\Job;
use App\Library\Helper;

if (!function_exists("pcntl_fork")) {
    die('pcntl extension required');
}

$cfg = Helper::getConfig('beanstalk');
$beanstalk = new BeanstalkExtended([
    'host' => $cfg['host'],
    'prefix' => $cfg['prefix'],
]);

$beanstalk->addWorker('sendMail', function (Job $job) {
    // Here we should collect the meta information,
    // make the screenshots, convert the video to the FLV etc.
    $args = json_decode($job->getBody(), true);
    Helper::sendMail($args['mailto'], $args['subject'], $args['body']);
    // It's very important to send the right exit code!
    exit(0);
});

$beanstalk->addWorker('sendSMS', function (Job $job) {
    // Here we should collect the meta information,
    // make the screenshots, convert the video to the FLV etc.
    $args = json_decode($job->getBody(), true);
    print_r($args);
    //Helper::sendMail($args['mailto'], $args['subject'], $args['body']);
    // It's very important to send the right exit code!
    exit(0);
});

// Start processing queues
$beanstalk->doWork();