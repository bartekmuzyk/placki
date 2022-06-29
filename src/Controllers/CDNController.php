<?php

namespace App\Controllers;

use App\Exceptions\CDNFileNotFoundException;
use App\Services\CDNService;
use Framework\Controller\Controller;
use Framework\Http\Response;

class CDNController extends Controller
{
    private const INCLUDE_CONTENT_TYPE_FOR = ['media_sources'];

    public function configureRoutes()
    {
        $this->get('/*', 'getFile');
    }

    public function getFile(CDNService $CDNService): Response
    {
        $req = $this->getRequest();
        $filePath = urldecode(str_replace('/cdn/', '', $req->route));
        $dirName = preg_split('/\//', $filePath, 2)[0];
        $includeContentType = in_array($dirName, self::INCLUDE_CONTENT_TYPE_FOR);

        try {
            return new Response(
                $CDNService->readFile($filePath),
                200,
                $includeContentType ? [
                    'Content-Type' => image_type_to_mime_type(exif_imagetype($CDNService->getFilePath($filePath)))
                ] : []);
        } catch (CDNFileNotFoundException) {
            return Response::code(404);
        }
    }
}