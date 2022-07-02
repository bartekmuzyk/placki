<?php

namespace App\Middleware\App;

use App\App;
use App\Entities\User;
use App\Services\AccountService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Exception\NoSuchServiceException;
use Framework\Http\Response;
use Framework\Middleware\AppMiddlewareInterface;

class CheckAuth implements AppMiddlewareInterface
{
	private static array $blacklist = [
        '/',
        '/rejestracja',
        '/login',
        '/media/plik/udostepnione',
        '/weryfikuj_kod_odzyskiwania',
        '/zmien_haslo'
    ];

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

		$ignore = in_array($req->route, self::$blacklist) || str_starts_with($req->route, '/api') || str_starts_with($req->route, '/cdn');

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