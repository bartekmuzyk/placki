<?php

namespace App\Middleware;

use App\App;
use App\Entities\User;
use App\Services\AccountService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Exception\NoSuchServiceException;
use Framework\Http\Response;
use Framework\Middleware\MiddlewareInterface;

class CheckAuth implements MiddlewareInterface
{
	private static array $blacklist = ['/', '/rejestracja', '/login', '/media/plik/udostepnione'];

	/**
	 * @param App $app
	 * @return Response|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 * @throws NoSuchServiceException
	 */
	public function run(App $app): ?Response
	{
		$req = $app->getRequest();
		$session = $app->getSessionManager();

		$ignore = in_array($req->route, self::$blacklist);

		if ($session->has('user')) {
			$username = $session->get('user')['username'];

			/** @var AccountService $accountService */
			$accountService = $app->getService(AccountService::class);
			$user = $accountService->getUser($username);

			if ($user instanceof User) {
				$accountService->currentLoggedInUser = $user;
			} else if (!$ignore) {
				$session->remove('user');

				return $app->redirect('/', [
					'error' => 'nie udało się automatycznie zalogować na konto, zaloguj się ponownie'
				]);
			}

			return null;
		}

		return $ignore ? null : $app->redirect('/');
	}
}