<?php

namespace App\Interfaces;

use App\Entities\FileUploadToken;
use App\Entities\MediaElement;
use App\Entities\VideoUploadToken;

interface PostUploadMediaElementConfigurator
{
	function configure(FileUploadToken|VideoUploadToken $uploadToken, MediaElement $mediaElement): void;
}