<?php
/**
 * Add your routes here
 */
$app->get('/', function () {
    return $this->json->ok(['url' => '/']);
});

$app->post('/', function () {
    return $this->json->created(['id' => 888]);
});