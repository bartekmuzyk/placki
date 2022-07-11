<?php /** @noinspection PhpUnused */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Controllers;

use App\Entities\Post;
use App\Entities\PostComment;
use App\Entities\User;
use App\Exceptions\AttachmentTooLargeException;
use App\Exceptions\CannotWriteAttachmentToDiskException;
use App\Services\AccountService;
use App\Services\PostService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class APIController extends Controller
{
    public function configureRoutes()
    {
        $this->post('/login', 'login');
        $this->post('/login/cookie', 'loginWithCookie');

        $this->get('/self', 'getSelf');

        $this->get('/posts', 'posts');
        $this->post('/posts', 'postPost');
        $this->delete('/posts', 'deletePost');
        $this->post('/posts/like', 'likePost');
        $this->post('/posts/dislike', 'dislikePost');
        $this->get('/posts/comments', 'getPostComments');
        $this->post('/posts/comments', 'postPostComment');
        $this->delete('/posts/comments', 'deletePostComment');
    }

    public function login(AccountService $accountService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('username') || !$req->hasPayload('password')) {
            return Response::code(400);
        }

        $token = $accountService->loginToApi($req->payload['username'], $req->payload['password']);

        if ($token === null) {
            return Response::code(401);
        }

        return new Response($token);
    }

    public function loginWithCookie(AccountService $accountService): Response
    {
        if (!$accountService->currentLoggedInUser) {
            return Response::code(401);
        }

        $token = $accountService->loginUserToApi($accountService->currentLoggedInUser);

        return new Response($token);
    }

    public function getSelf(AccountService $accountService): Response
    {
        return $accountService->currentLoggedInUser instanceof User ?
            $this->serialize($accountService->currentLoggedInUser, 'basic')
            :
            Response::code(401);
    }

    public function posts(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('limit')) {
            return Response::code(400);
        }

        $posts = $postService->getPosts((int)$req->query['limit'], $accountService->currentLoggedInUser);

        return $this->serialize($posts);
    }

    public function postPost(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();
        $attachments = $req->getFilesArray('attachments');

        if (!$req->hasPayload('content') && count($attachments) === 0) {
            return Response::code(400);
        }

        try {
            $postService->createPost(
                $req->payload['content'] ?? '',
                null,
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

    public function likePost(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('postId')) {
            return Response::code(400);
        }

        $postService->like((int)$req->payload['postId'], $accountService->currentLoggedInUser);

        return new Response();
    }

    public function dislikePost(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('postId')) {
            return Response::code(400);
        }

        $postService->dislike((int)$req->payload['postId'], $accountService->currentLoggedInUser);

        return new Response();
    }

    public function deletePost(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('postId')) {
            return Response::code(400);
        }

        $post = $postService->getPost((int)$req->query['postId']);

        if (!($post instanceof Post)) {
            return Response::code(404);
        } else if ($post->author !== $accountService->currentLoggedInUser) {
            return Response::code(403);
        }

        $postService->deletePost($post);

        return new Response();
    }

    public function getPostComments(PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('postId')) {
            return Response::code(400);
        }

        $comments = $postService->getComments((int)$req->query['postId']);

        return $this->serialize($comments);
    }

    public function postPostComment(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasPayload('postId') || !$req->hasPayload('content')) {
            return Response::code(400);
        }

        $content = trim($req->payload['content']);

        if (strlen($content) === 0) {
            return Response::code(400);
        }

        $post = $postService->getPost((int)$req->payload['postId']);

        if (!$postService->authorizeToPost($post, $accountService->currentLoggedInUser)) {
            return Response::code(403);
        }

        $comment = $postService->postComment($post, $req->payload['content'], $accountService->currentLoggedInUser);

        return $this->serialize($comment);
    }

    public function deletePostComment(AccountService $accountService, PostService $postService): Response
    {
        $req = $this->getRequest();

        if (!$req->hasQuery('commentId')) {
            return Response::code(400);
        }

        $comment = $postService->getPostComment((int)$req->query['commentId']);

        if (!($comment instanceof PostComment)) {
            return Response::code(404);
        } else if ($comment->author !== $accountService->currentLoggedInUser) {
            return Response::code(403);
        }

        $this->getDBManager()->removeAndFlush($comment);

        return new Response();
    }
}