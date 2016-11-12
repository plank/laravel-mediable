<?php

namespace Plank\Mediable;

use Exception;
use Plank\Mediable\Exceptions\MediaUploadException;
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
            MediaUploadException::DISK_NOT_ALLOWED,
        ],

        // 404
        Response::HTTP_NOT_FOUND => [
            MediaUploadException::DISK_NOT_FOUND,
            MediaUploadException::FILE_NOT_FOUND,
        ],

        // 409
        Response::HTTP_CONFLICT => [
            MediaUploadException::FILE_EXISTS,
        ],

        // 413
        Response::HTTP_REQUEST_ENTITY_TOO_LARGE => [
            MediaUploadException::FILE_IS_TOO_BIG,
        ],

        // 415
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => [
            MediaUploadException::STRICT_TYPE_MISMATCH,
            MediaUploadException::UNRECOGNIZED_FILE_TYPE,
            MediaUploadException::MIME_RESTRICTED,
            MediaUploadException::EXTENSION_RESTRICTED,
            MediaUploadException::AGGREGATE_TYPE_RESTRICTED,
        ],

        // 422
        Response::HTTP_UNPROCESSABLE_ENTITY => [
            MediaUploadException::CANNOT_SET_ADAPTER,
            MediaUploadException::CANNOT_SET_MODEL,
            MediaUploadException::UNRECOGNIZED_SOURCE,
            MediaUploadException::NO_SOURCE_PROVIDED,
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
        if (! $e instanceof MediaUploadException) {
            return $e;
        }

        return new HttpException($this->getStatusCodeForMediaException($e), $e->getMessage(), $e);
    }

    /**
     * Get the appropriate HTTP status code for the exception.
     *
     * It accepts a generic \Exception so the trait can be extended to
     * handle more exception types like MediaUrlException in the future.
     *
     * If there is no match a 500 error is returned.
     *
     * @param  \Exception $e
     * @return int
     */
    protected function getStatusCodeForMediaException(Exception $e)
    {
        foreach ($this->statusCodes as $statusCode => $codes) {
            if (in_array($e->getCode(), $codes)) {
                return $statusCode;
            }
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
