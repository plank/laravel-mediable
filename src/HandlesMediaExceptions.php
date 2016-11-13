<?php

namespace Plank\Mediable;

use Exception;
use Plank\Mediable\Exceptions\MediaSizeException;
use Plank\Mediable\Exceptions\MediaExistsException;
use Plank\Mediable\Exceptions\MediaNotFoundException;
use Plank\Mediable\Exceptions\MediaForbiddenException;
use Plank\Mediable\Exceptions\MediaNotSupportedException;
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
            MediaForbiddenException::class,
        ],

        // 404
        Response::HTTP_NOT_FOUND => [
            MediaNotFoundException::class,
        ],

        // 409
        Response::HTTP_CONFLICT => [
            MediaExistsException::class,
        ],

        // 413
        Response::HTTP_REQUEST_ENTITY_TOO_LARGE => [
            MediaSizeException::class,
        ],

        // 415
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => [
            MediaNotSupportedException::class,
        ],
    ];

    /**
     * Prepare MediaUploadException for rendering.
     *
     * @param  \Exception  $e
     * @return \Exception
     */
    protected function prepareMediaUploadException(Exception $e)
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
