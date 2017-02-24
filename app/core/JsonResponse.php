<?php
namespace App\Core;
/**
 * 扩展系统response
 * Class Response
 */
class JsonResponse extends \Phalcon\Http\Response
{
    //GET
    public function ok($data)
    {
        $this->raw($data, 200);
    }

    //POST
    public function created($data)
    {
        $this->raw($data, 201);
    }

    public function accepted($data)
    {
        $this->raw($data, 202);
    }

    //PUT || DELETE
    public function noContent()
    {
        $this->raw(null, 204);
    }

    //404
    public function notFound()
    {
        $this->raw(null, 404);
    }

    public function raw($data = null, $code = 200)
    {
        $this->setStatusCode($code);
        if(!is_null($data)) {
            $this->setJsonContent($data);
        }
        $this->send();
        exit;
    }

    //redirect
    public function go($url, $status = 302)
    {
        return $this->redirect($url, $status);
    }
}