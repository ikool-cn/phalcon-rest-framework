<?php
$app->get('/user/{id:[0-9]+}', function ($id) {
    $get = $this->request->get();
    return $this->json->ok(['id' => $id, 'get' => $get]);
});

$app->get('/user2', function () {
    return $this->json->go('http://www.baidu.com');
});

$app->put('/user/{id:[0-9]+}', function () {
    return $this->json->noContent();
});

$app->delete('/user/{uid:[0-9]+}/status/{id:[0-9]+}', function () {
    return $this->json->noContent();
});

$app->post('/user/{uid:[0-9]+}/profile', function() {
    $order = new \App\Model\Order();
    $order->setMaster(true);
    $user = new \App\Model\User();
    $user->inc(['id' => 2], ['tinyint' => -1]);
    $this->json->ok(\App\Library\Helper::getQiniuToken());
});

$app->get('/user', function() {
    $user = new \App\Model\User();
    $this->json->ok($user->fetchOne(95475));
});