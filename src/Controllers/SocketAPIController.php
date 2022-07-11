<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace App\Controllers;

use App\Services\AccountService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class SocketAPIController extends Controller
{
    public function configureRoutes()
    {
        $this->post('/authenticate', 'authenticate');
    }

    public function authenticate(AccountService $accountService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('token')) {
            return Response::code(400);
        }

        $token = $req->payload['token'];
        $user = $accountService->getUserByApiToken($token);

        return $this->serialize($user, 'socket');
    }
}