<?php

namespace App\Services;

use App\Entities\Group;
use App\Entities\User;
use App\Exceptions\CDNFileCreationFailureException;
use App\Exceptions\CDNFileDeletionFailureException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Http\UploadedFile;
use Framework\Service\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GroupsService extends Service
{
	public const ACCESS_PUBLIC = 0;
	public const ACCESS_NEEDS_PERMISSION = 1;
	public const ACCESS_INVITE_ONLY = 2;

    public CDNService $CDNService;

	/**
	 * @return Group[]
	 */
	public function getGroups(): array
	{
		return $this->getApp()->getDBManager()->getAll(Group::class);
	}

	/**
	 * @param int $id
	 * @return Group|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function getGroup(int $id): ?Group
	{
		/** @var ?Group $group */
		$group = $this->getApp()->getDBManager()->find(Group::class, $id);

		return $group;
	}

    /**
     * @param Group $group
     * @return string|null path of the generated picture file
     * @throws GuzzleException
     * @throws CDNFileCreationFailureException
     */
	private function generatePicture(Group $group): ?string
	{
		$client = new Client();
		$response = $client->get('https://ui-avatars.com/api', [
			'query' => [
				'name' => $group->name,
				'background' => '2196f3',
				'color' => 'fff',
				'size' => '200',
				'bold' => 'true',
				'uppercase' => 'false',
				'format' => 'png',
				'length' => '3'
			]
		]);

		$filename = uniqid() . '.png';
        $this->CDNService->writeFile("group_pics/$filename", $response->getBody()->getContents());

		return $filename;
	}

    /**
     * @param string $name
     * @param User $owner
     * @return int created group id
     * @throws CDNFileCreationFailureException
     * @throws GuzzleException
     * @throws ORMException
     * @throws OptimisticLockException
     */
	public function createGroup(string $name, User $owner): int
	{
		$db = $this->getApp()->getDBManager();

		$group = new Group();
		$group->name = $name;
		$group->owner = $owner;
		$group->members->add($owner);
		$group->accessLevel = self::ACCESS_INVITE_ONLY;
		$group->picFilename = $this->generatePicture($group);

		$db->persistAndFlush($group);

		return $group->id;
	}

    /**
     * @param Group $group
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws CDNFileDeletionFailureException
     */
	public function deleteGroup(Group $group): void
    {
		$db = $this->getApp()->getDBManager();

        $this->CDNService->deleteFile("group_pics/$group->picFilename");

		// TODO remove safe files and referenced safe record when deleting a group to prevent leaks
		$db->removeAndFlush($group);
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function ban(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		if (!$group->bans->contains($user) && $user !== $group->owner) {
			$group->members->removeElement($user);
			$group->bans->add($user);

			$db->flush();
		}
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function unban(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		if (!$group->members->contains($user) && $user !== $group->owner) {
			$group->bans->removeElement($user);
			$group->members->add($user);

			$db->flush();
		}
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function kick(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		if ($user !== $group->owner) {
			$group->members->removeElement($user);
			$db->flush();
		}
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function setNewAdmin(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		if ($group->owner !== $user) {
			$group->owner = $user;

			$db->persistAndFlush($group);
		}
	}

	public function isBanned(Group $group, User $user): bool
	{
		return $group->bans->contains($user);
	}

	public function isMember(Group $group, User $user): bool
	{
		return $group->members->contains($user);
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function joinGroup(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		$group->members[] = $user;

		$db->flush();
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function requestJoin(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		$group->joinRequests->add($user);

		$db->flush();
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return bool
	 */
	public function joinRequested(Group $group, User $user): bool
	{
		return $group->joinRequests->contains($user);
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @param bool $flush
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function approveJoinRequest(Group $group, User $user, bool $flush = true): void
    {
		$db = $this->getApp()->getDBManager();

		$group->joinRequests->removeElement($user);
		$group->members->add($user);

		if ($flush) $db->flush();
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @param bool $flush
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function rejectJoinRequest(Group $group, User $user, bool $flush = true): void
    {
		$db = $this->getApp()->getDBManager();

		$group->joinRequests->removeElement($user);

		if ($flush) $db->flush();
	}

    /**
     * @param Group $group
     * @param string $name
     * @param string $description
     * @param UploadedFile $picFile
     * @return void
     * @throws CDNFileCreationFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
	public function updateLook(Group $group, string $name, string $description, UploadedFile $picFile): void
    {
		$db = $this->getApp()->getDBManager();

        $this->CDNService->writeFileFrom("group_pics/$group->picFilename", $picFile);
		$group->name = $name;
		$group->description = $description;

		$db->persistAndFlush($group);
	}

	/**
	 * @param Group $group
	 * @param User $user
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function leave(Group $group, User $user): void
    {
		$db = $this->getApp()->getDBManager();

		if ($group->members->contains($user)) {
			$group->members->removeElement($user);

			$db->flush();
		}
	}

	/**
	 * @param Group $group
	 * @param int $accessLevel
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function updateJoinPolicy(Group $group, int $accessLevel): void
    {
		$db = $this->getApp()->getDBManager();

		$group->accessLevel = $accessLevel;

		switch ($accessLevel) {
			case self::ACCESS_PUBLIC:  // if switched to public access level, approve all join requests
				foreach ($group->joinRequests as $user) {
					$this->approveJoinRequest($group, $user, false);
				}
				break;
			case self::ACCESS_INVITE_ONLY:  // if switched to invite only access level, reject all join requests
				foreach ($group->joinRequests as $user) {
					$this->rejectJoinRequest($group, $user, false);
				}
				break;
		}

		$db->persistAndFlush($group);
	}

	/**
	 * a utility function used when rendering the group panel to prevent reloading every time information about members,
	 * bans or joins requests changes. every user is rendered everywhere instead and JavaScript shows/hides them in
	 * different parts of the page on demand using the <code>UserList</code> class in <code>panel_grupy.js</code>.
	 * @param Group $group
	 * @return User[] list of all possible users to be rendered on a page
	 */
	public function possibleUsers(Group $group): array
	{
		return array_merge(
			$group->members->toArray(),
			$group->bans->toArray(),
			$group->joinRequests->toArray()
		);
	}
}