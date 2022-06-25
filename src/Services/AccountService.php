<?php

namespace App\Services;

use App\Entities\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;

class AccountService extends Service
{
    public const DEFAULT_PROFILE_PICTURE = '/assets/img/no-pic.png';
    private const PROFILE_PICTURES_DIR = PUBLIC_DIR . '/pfp/';

	public ?User $currentLoggedInUser = null;

	public function isLoggedIn(): bool
	{
		return $this->currentLoggedInUser instanceof User;
	}

	private function hashPassword(string $password): string
	{
		return hash('sha256', $password);
	}

	public function login(string $username, string $password): ?array
	{
		$hashedPassword = $this->hashPassword($password);
		$db = $this->getApp()->getDBManager();

		/** @noinspection PhpUnhandledExceptionInspection */
		$user = $db->query('u', User::class)
			->andWhere('u.username = :username')
			->andWhere('u.password = :password')
			->setParameters([
				'username' => $username,
				'password' => $hashedPassword
			])
			->getQuery()
			->getOneOrNullResult();

		return $user instanceof User ? ['username' => $user->username] : null;
	}

	private function createUser(string $username, string $hashedPassword): User
	{
		$user = new User();

		$user->username = $username;
		$user->password = $hashedPassword;
		$user->profilePic = self::DEFAULT_PROFILE_PICTURE;

		return $user;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @return bool <code>true</true> if user already exists, <code>false</code> if not.
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function register(string $username, string $password): bool
	{
		$db = $this->getApp()->getDBManager();
		$hashedPassword = $this->hashPassword($password);
		$user = $this->createUser($username, $hashedPassword);

		try {
			$db->persistAndFlush($user);
		} /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $ex) {
			return true;
		}

		return false;
	}

    /**
     * @param string $username
     * @return User|null
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
	public function getUser(string $username): ?User
	{
		$db = $this->getApp()->getDBManager();
		/** @var User $user */
		$user = $db->find(User::class, $username);

		return $user;
	}

    /**
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->getApp()->getDBManager()->getAll(User::class, 0, null, [
            'order_by' => 'username'
        ]);
    }

    /**
     * @param User $user
     * @param UploadedFile $picture
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function setCustomProfilePicture(User $user, UploadedFile $picture): void
    {
        $db = $this->getApp()->getDBManager();

        $picture->move(self::PROFILE_PICTURES_DIR . $user->username);
        $user->profilePic = "/pfp/$user->username";

        $db->persistAndFlush($user);
    }

    /**
     * @param User $user
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function resetProfilePicture(User $user): void
    {
        $db = $this->getApp()->getDBManager();

        unlink(self::PROFILE_PICTURES_DIR . $user->username);
        $user->profilePic = self::DEFAULT_PROFILE_PICTURE;

        $db->persistAndFlush($user);
    }
}