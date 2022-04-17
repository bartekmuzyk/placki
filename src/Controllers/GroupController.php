<?php

namespace App\Controllers;

use App\Entities\Group;
use App\Entities\Post;
use App\Entities\User;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use App\Services\AccountService;
use App\Services\AttachmentService;
use App\Services\GroupsService;
use App\Services\PostService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Framework\Controller\Controller;
use Framework\Http\Response;
use Framework\Http\UploadedFile;
use GuzzleHttp\Exception\GuzzleException;

class GroupController extends Controller
{
	public function configureRoutes()
	{
		$this->get('/', 'index');
		$this->post('/', 'createGroup');

		$this->get('/panel', 'panel');
		$this->post('/panel', 'joinGroup');
		$this->delete('/panel', 'deleteGroup');

		$this->get('/tablica', 'wall');
		$this->post('/tablica', 'postWallPost');
		$this->delete('/tablica', 'deleteWallPost');

		$this->post('/dajadmina', 'makeAdmin');
		$this->post('/zbanuj', 'ban');
		$this->post('/odbanuj', 'unban');
		$this->post('/wyrzuc', 'kick');
		$this->get('/wyjdz', 'leaveGroup');

		$this->post('/prosba/zatwierdz', 'approveJoinRequest');
		$this->post('/prosba/odrzuc', 'rejectJoinRequest');

		$this->post('/ustaw/wyglad', 'setLook');
		$this->post('/ustaw/polityka_przyjmowania_czlonkow', 'setJoinPolicy');
	}

	public function index(AccountService $accountService, GroupsService $groupsService): Response
	{
		$me = $accountService->currentLoggedInUser;
		$groups = $groupsService->getGroups();
		$myGroupsIds = array_map(
			fn(Group $group) => $group->id,
			array_filter(
				$groups,
				fn(Group $group) => $group->owner === $accountService->currentLoggedInUser
			)
		);
		$joinedGroupsIds = array_map(
			fn(Group $group) => $group->id,
			array_filter(
				$groups,
				fn(Group $group) => $groupsService->isMember($group, $accountService->currentLoggedInUser)
			)
		);

		return $this->template('grupy.twig', [
			'self' => $me,
			'groups' => $groups,
			'my_groups' => $myGroupsIds,
			'joined_groups' => $joinedGroupsIds
		]);
	}

	/**
	 * @param AccountService $accountService
	 * @param GroupsService $groupsService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws GuzzleException
	 */
	public function createGroup(AccountService $accountService, GroupsService $groupsService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasPayload('name')) {
			return Response::code(400);
		}

		$groupId = $groupsService->createGroup($req->payload['name'], $accountService->currentLoggedInUser);

		return new Response((string)$groupId);
	}

	/**
	 * @param AccountService $accountService
	 * @param GroupsService $groupsService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function panel(AccountService $accountService, GroupsService $groupsService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return $this->redirect('/grupy');
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return $this->redirect('/grupy');
		} else if ($groupsService->isBanned($group, $accountService->currentLoggedInUser)) {
			return $this->template('grupa_z_wiadomoscia.twig', [
				'self' => $accountService->currentLoggedInUser,
				'group' => $group,
				'message_title' => 'masz bana ¯\_(ツ)_/¯',
				'message_subtitle' => 'nie możesz uzyskać dostępu do tej grupy, ponieważ jej administrator zbanował twoje konto'
			]);
		} else if (!$groupsService->isMember($group, $accountService->currentLoggedInUser)) {
			switch ($group->accessLevel) {
				case GroupsService::ACCESS_PUBLIC:
				case GroupsService::ACCESS_NEEDS_PERMISSION:
					return $this->template('grupa_dolacz.twig', [
						'needs_permission' => $group->accessLevel === GroupsService::ACCESS_NEEDS_PERMISSION,
						'request_sent' => $groupsService->joinRequested($group, $accountService->currentLoggedInUser),
						'self' => $accountService->currentLoggedInUser,
						'group' => $group
					]);
				case GroupsService::ACCESS_INVITE_ONLY:
					return $this->template('grupa_z_wiadomoscia.twig', [
						'self' => $accountService->currentLoggedInUser,
						'group' => $group,
						'message_title' => 'dostęp do tej grupy jest ograniczony',
						'message_subtitle' => 'administrator tej grupy pozwolił na dołączanie do grupy wyłącznie za pomocą zaproszenia'
					]);
			}
		}

		return $this->template('panel_grupy.twig', [
			'self' => $accountService->currentLoggedInUser,
			'group' => $group,
			'all_users' => $groupsService->possibleUsers($group)
		]);
	}

	/**
	 * @param AccountService $accountService
	 * @param GroupsService $groupsService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function joinGroup(AccountService $accountService, GroupsService $groupsService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return Response::code(404);
		} else if ($groupsService->isBanned($group, $accountService->currentLoggedInUser)) {
			return Response::code(403);
		} else if (!$groupsService->isMember($group, $accountService->currentLoggedInUser)) {
			switch ($group->accessLevel) {
				case GroupsService::ACCESS_PUBLIC:
					$groupsService->joinGroup($group, $accountService->currentLoggedInUser);
					break;
				case GroupsService::ACCESS_NEEDS_PERMISSION:
					if (!$groupsService->joinRequested($group, $accountService->currentLoggedInUser)) {
						$groupsService->requestJoin($group, $accountService->currentLoggedInUser);
					}
					break;
			}
		}

		return $this->redirect('/grupy/panel', ['id' => (string)$group->id]);
	}

	/**
	 * @param AccountService $accountService
	 * @param GroupsService $groupsService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function deleteGroup(AccountService $accountService, GroupsService $groupsService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if ($group instanceof Group) {
			if ($group->owner !== $accountService->currentLoggedInUser) {
				return Response::code(403);
			}

			$groupsService->deleteGroup($group);
		}

		return new Response();
	}

	/**
	 * @param PostService $postService
	 * @param GroupsService $groupsService
	 * @param AttachmentService $attachmentService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function wall(PostService $postService,
						 GroupsService $groupsService,
						 AttachmentService $attachmentService,
						 AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!$groupsService->isMember($group, $accountService->currentLoggedInUser)) {
			return Response::code(403);
		}

		$posts = $postService->getPostsFromGroup($group->id, (int)($req->query['limit'] ?? 100));
		$attachmentSources = [];

		foreach ($posts as $post) {
			foreach ($post->attachments as $attachment) {
				$attachmentSources[$attachment->id] = $attachmentService->getAttachmentFilePath($attachment);
			}
		}

		return $this->template('posty.twig', [
			'hide_group_labels' => true,
			'self' => $accountService->currentLoggedInUser,
			'posts' => $posts,
			'attachmentSources' => $attachmentSources
		]);
	}

	/**
	 * @param PostService $postService
	 * @param AccountService $accountService
	 * @param GroupsService $groupsService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function postWallPost(PostService $postService, AccountService $accountService, GroupsService $groupsService): Response
	{
		$req = $this->getRequest();
		$attachments = $req->getFilesArray('attachments');

		if (!$req->hasQuery('id') || (!$req->hasPayload('content') && count($attachments) === 0)) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return Response::code(404);
		} else if (!$groupsService->isMember($group, $accountService->currentLoggedInUser)) {
			return Response::code(403);
		}

		try {
			$postService->createPost(
				$req->payload['content'] ?? '',
				$group,
				$accountService->currentLoggedInUser,
				$attachments
			);
		} catch (CannotWriteAttachmentToDiskException $e) {
			return $this->json([
				'filename' => $e->defectiveFile->getBasename(),
				'error' => 'cannot write to disk'
			], 400);
		} catch (AttachmentTooLargeException $e) {
			return $this->json([
				'filename' => $e->defectiveFile->getBasename(),
				'error' => 'too large'
			], 400);
		}

		return new Response();
	}

	/**
	 * @param PostService $postService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function deleteWallPost(PostService $postService, AccountService $accountService): Response
	{
		$req = $this->getRequest();
		$db = $this->getDBManager();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$post = $postService->getPost((int)$req->query['id']);

		if (!($post instanceof Post)) {
			return Response::code(404);
		} else if ($post->author !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$db->removeAndFlush($post);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function makeAdmin(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$newAdminUser = $accountService->getUser($req->payload['username']);

		if (!($newAdminUser instanceof User) || !$groupsService->isMember($group, $newAdminUser)) {
			return new Response('member not found', 404);
		}

		$groupsService->setNewAdmin($group, $newAdminUser);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function ban(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$userToBan = $accountService->getUser($req->payload['username']);

		if (!($userToBan instanceof User) || !$groupsService->isMember($group, $userToBan)) {
			return new Response('member not found', 404);
		}

		$groupsService->ban($group, $userToBan);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function unban(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$userToUnban = $accountService->getUser($req->payload['username']);

		if (!($userToUnban instanceof User) || !$groupsService->isBanned($group, $userToUnban)) {
			return new Response('member not found', 404);
		}

		$groupsService->unban($group, $userToUnban);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function kick(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$userToKick = $accountService->getUser($req->payload['username']);

		if (!($userToKick instanceof User) || !$groupsService->isMember($group, $userToKick)) {
			return new Response('member not found', 404);
		}

		$groupsService->kick($group, $userToKick);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function leaveGroup(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if ($group instanceof Group && $group->owner !== $accountService->currentLoggedInUser) {
			$groupsService->leave($group, $accountService->currentLoggedInUser);
		}

		return $this->redirect('/grupy');
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function setLook(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();
		$pictureFile = $req->getFile('pic');

		if (
			!$req->hasQuery('id') ||
			!($pictureFile instanceof UploadedFile) ||
			!$req->hasPayload('name') ||
			!$req->hasPayload('description')
		) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return Response::code(404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$trimmedName = trim($req->payload['name']);
		if (strlen($trimmedName) === 0) return Response::code(400);

		$trimmedDescription = trim($req->payload['description']);

		$groupsService->updateLook($group, $trimmedName, $trimmedDescription, $pictureFile);

		return $this->json([
			'picSrc' => '/group_pics/' . $group->picFilename,
			'name' => $trimmedName
		]);
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function setJoinPolicy(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('policy')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return Response::code(404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		switch ($req->payload['policy']) {
			case 'public':
				$groupsService->updateJoinPolicy($group, GroupsService::ACCESS_PUBLIC);
				break;
			case 'needs-permission':
				$groupsService->updateJoinPolicy($group, GroupsService::ACCESS_NEEDS_PERMISSION);
				break;
			case 'invite-only':
				$groupsService->updateJoinPolicy($group, GroupsService::ACCESS_INVITE_ONLY);
				break;
			default:
				return Response::code(400);
		}

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function approveJoinRequest(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$user = $accountService->getUser($req->payload['username']);

		if (!($user instanceof User) || !$group->joinRequests->contains($user)) {
			return new Response('user not found', 404);
		}

		$groupsService->approveJoinRequest($group, $user);

		return new Response();
	}

	/**
	 * @param GroupsService $groupsService
	 * @param AccountService $accountService
	 * @return Response
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function rejectJoinRequest(GroupsService $groupsService, AccountService $accountService): Response
	{
		$req = $this->getRequest();

		if (!$req->hasQuery('id') || !$req->hasPayload('username')) {
			return Response::code(400);
		}

		$group = $groupsService->getGroup((int)$req->query['id']);

		if (!($group instanceof Group)) {
			return new Response('group not found', 404);
		} else if ($group->owner !== $accountService->currentLoggedInUser) {
			return Response::code(403);
		}

		$user = $accountService->getUser($req->payload['username']);

		if (!($user instanceof User) || !$group->joinRequests->contains($user)) {
			return new Response('user not found', 404);
		}

		$groupsService->rejectJoinRequest($group, $user);

		return new Response();
	}
}