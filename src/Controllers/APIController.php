<?php

namespace App\Controllers;

use Framework\Controller\Controller;
use Framework\Http\Response;

class APIController extends Controller
{
    public function configureRoutes()
    {
        $this->post('/login', 'login');
    }

    public function login(): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('username') || !$req->hasPayload('password')) {
            return Response::code(400);
        }


    }
}