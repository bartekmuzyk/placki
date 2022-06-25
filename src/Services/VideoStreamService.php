<?php

namespace App\Services;

use App\Exceptions\CannotOpenVideoStreamException;
use App\Exceptions\MimeTypeNotProvidedException;
use Framework\Http\Response;
use Framework\Service\Service;

/**
 * @author Rana
 * @link http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 */
class VideoStreamService extends Service
{
    private string $path = "";

    /** @var string|resource  */
    private $stream = "";

    private int $buffer = 102400;

    private int $start = -1;

    private int $end = -1;

    private string $mimeType;

    public function setFilePath(string $filePath): void
    {
        $this->path = $filePath;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return void
     * @throws CannotOpenVideoStreamException
     */
    private function openFileStream(): void
    {
        if (!($this->stream = fopen($this->path, 'rb'))) {
            throw new CannotOpenVideoStreamException();
        }
    }

    /**
     * @param Response $response
     * @return void
     * @throws MimeTypeNotProvidedException
     */
    private function setHeadersAndCode(Response $response): void
    {
        if (!$this->mimeType) {
            throw new MimeTypeNotProvidedException();
        }

        $response->headers = [
            'Cache-Control' => 'max-age=2592000 public',
            'Content-Type' => $this->mimeType
        ];

//        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
//        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $size = filesize($this->path);
        $this->end = $size - 1;

        $response->headers['Accept-Ranges'] = "0-$this->end";

        if (isset($_SERVER['HTTP_RANGE'])) {

            $c_start = $this->start;
            $c_end = $this->end;

            [, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (str_contains($range, ',')) {
                $response->code = 416;  // Requested Range Not Satisfiable
                $response->headers['Content-Range'] = "bytes $this->start-$this->end/$size";

                return;
            }

            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }

            $c_end = ($c_end > $this->end) ? $this->end : $c_end;

            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                $response->code = 416;  // Requested Range Not Satisfiable
                $response->headers['Content-Range'] = "bytes $this->start-$this->end/$size";

                return;
            }

            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;

            fseek($this->stream, $this->start);

            $response->code = 206;  // Partial Content
            $response->headers['Content-Length'] = $length;
            $response->headers['Content-Range'] = "bytes $this->start-$this->end/$size";
        }
        else
        {
            $response->headers['Content-Length'] = $size;
        }
    }

    private function closeFileStream(): void
    {
        fclose($this->stream);
    }

    private function readFileFragmentToResponse(Response $response): void
    {
        $i = $this->start;
        $buffer = "";

        while(!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $this->buffer;

            if(($i + $bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }

            $data = fread($this->stream, $bytesToRead);
            $buffer .= $data;
            $i += $bytesToRead;
        }

        $response->content = $buffer;
    }

    /**
     * @return Response
     * @throws CannotOpenVideoStreamException
     * @throws MimeTypeNotProvidedException
     */
    public function getStreamResponse(): Response
    {
        $response = new Response();

        $this->openFileStream();
        $this->setHeadersAndCode($response);
        $this->readFileFragmentToResponse($response);
        $this->closeFileStream();

        return $response;
    }
}