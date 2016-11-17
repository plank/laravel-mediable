<?php

namespace Plank\Mediable;

use Exception;
use Plank\Mediable\Exceptions\MediaUpload\FileSizeException;
use Plank\Mediable\Exceptions\MediaUpload\FileExistsException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotFoundException;
use Plank\Mediable\Exceptions\MediaUpload\ForbiddenException;
use Plank\Mediable\Exceptions\MediaUpload\FileNotSupportedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait HandlesMediaExceptions
{
    /**
     * Table of HTTP status codes associated with the exception codes.
     *
     * @var array
     */
    protected $statusCodes = [
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
     * @return \Symfony\Component\HttpKernel\Exception\HttpException|\Exception
     */
    protected function transformToHttpException(Exception $e)
    {
        if ($statusCode = $this->getStatusCodeForMediaException($e)) {
            return new HttpException($statusCode, $e->getMessage(), $e);
        }

        return $e;
    }

    /**
     * Get the appropriate HTTP status code for the exception.
     *
     * It accepts a generic \Exception so the trait can be extended to
     * handle more exception types like MediaUrlException in the future.
     *
     * @param  \Exception $e
     * @return int|bool
     */
    protected function getStatusCodeForMediaException(Exception $e)
    {
        foreach ($this->statusCodes as $statusCode => $exceptions) {
            if (in_array(get_class($e), $exceptions)) {
                return $statusCode;
            }
        }

        return false;
    }
}
