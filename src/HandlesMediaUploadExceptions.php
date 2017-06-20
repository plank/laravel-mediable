<?php

namespace Plank\Mediable;

use Exception;
use Plank\Mediable\Exceptions\MediaUploadException;
use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait HandlesMediaUploadExceptions
{
    /**
     * Table of HTTP status codes associated with the exception codes.
     *
     * @var array
     */
    protected $status_codes = [
        // 403
        Response::HTTP_FORBIDDEN => [
            ForbiddenException::class,
        ],

        // 404
        Response::HTTP_NOT_FOUND => [
            FileNotFoundException::class,
        ],

        // 409
        Response::HTTP_CONFLICT => [
            FileExistsException::class,
        ],

        // 413
        Response::HTTP_REQUEST_ENTITY_TOO_LARGE => [
            FileSizeException::class,
        ],

        // 415
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => [
            FileNotSupportedException::class,
        ],
    ];

    /**
     * Transform a MediaUploadException into an HttpException.
     *
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function transformMediaUploadException(Exception $e)
    {
        if ($e instanceof MediaUploadException) {
            $status_code = $this->getStatusCodeForMediaUploadException($e);
            return new HttpException($status_code, $e->getMessage(), $e);
        }

        return $e;
    }

    /**
     * Get the appropriate HTTP status code for the exception.
     *
     * @param  \Plank\Mediable\Exceptions\MediaUploadException $e
     * @return integer
     */
    protected function getStatusCodeForMediaUploadException(MediaUploadException $e)
    {
        foreach ($this->status_codes as $status_code => $exceptions) {
            if (in_array(get_class($e), $exceptions)) {
                return $status_code;
            }
        }

        return 500;
    }
}
