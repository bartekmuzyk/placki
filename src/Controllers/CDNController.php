<?php

namespace App\Controllers;

use App\Exceptions\CDNFileNotFoundException;
use App\Services\CDNService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class CDNController extends Controller
{
    public function configureRoutes()
    {
        $this->get('/*', 'getFile');
    }

    public function getFile(CDNService $CDNService): Response
    {
        $req = $this->getRequest();
        $filePath = str_replace('/cdn/', '', $req->route);

        try {
            return new Response($CDNService->readFile($filePath));
        } catch (CDNFileNotFoundException) {
            return Response::code(404);
        }
    }
}