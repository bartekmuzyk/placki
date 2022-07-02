<?php

namespace App\Services;

use App\Entities\User;
use App\Exceptions\CDNFileCreationFailureException;
use App\Exceptions\CDNFileDeletionFailureException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use Framework\TempFileUtil\Exception\TempFileReadException;

class AccountService extends Service
{
    public const DEFAULT_PROFILE_PICTURE = '/assets/img/no-pic.png';

	public ?User $currentLoggedInUser = null;

    public CDNService $CDNService;

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

    private function generateRecoveryCode(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return bin2hex(random_bytes(4));
    }

	private function createUser(string $username, string $hashedPassword): User
	{
		$user = new User();

		$user->username = $username;
		$user->password = $hashedPassword;
		$user->profilePic = self::DEFAULT_PROFILE_PICTURE;
        $user->recoveryCode = $this->generateRecoveryCode();

		return $user;
	}

    /**
     * @param User $user
     * @param string $newPassword
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $db = $this->getApp()->getDBManager();
        $user->password = $this->hashPassword($newPassword);
        $user->recoveryCode = $this->generateRecoveryCode();
        $db->persistAndFlush($user);
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
     * @param string $recoveryCode
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function getUserByRecoveryCode(string $recoveryCode): ?User
    {
        $db = $this->getApp()->getDBManager();
        /** @var ?User $user */
        $user = $db->query('u', User::class)
            ->andWhere('u.recoveryCode = :recoveryCode')
            ->setParameter('recoveryCode', $recoveryCode)
            ->getQuery()
            ->getOneOrNullResult();

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
     * @throws CDNFileCreationFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TempFileReadException
     */
    public function setCustomProfilePicture(User $user, UploadedFile $picture): void
    {
        $db = $this->getApp()->getDBManager();

        $this->CDNService->writeFileFrom("pfp/$user->username", $picture);
        $user->profilePic = "/cdn/pfp/$user->username";

        $db->persistAndFlush($user);
    }

    /**
     * @param User $user
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws CDNFileDeletionFailureException
     */
    public function resetProfilePicture(User $user): void
    {
        $db = $this->getApp()->getDBManager();

        $this->CDNService->deleteFile("pfp/$user->username");
        $user->profilePic = self::DEFAULT_PROFILE_PICTURE;

        $db->persistAndFlush($user);
    }
}