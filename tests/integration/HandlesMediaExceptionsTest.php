<?php

use Plank\Mediable\Exceptions\MediaUploadException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlesMediaExceptionsTest extends TestCase
{
    public function test_it_returns_a_403_for_dissalowed_disk()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::diskNotAllowed('foo')
        );

        $this->assertHttpException($e, 403);
    }

    public function test_it_returns_a_404_for_non_existent_disk()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::diskNotFound('foo')
        );

        $this->assertHttpException($e, 404);
    }

    public function test_it_returns_a_404_for_missing_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::fileNotFound('non/existing.jpg')
        );

        $this->assertHttpException($e, 404);
    }

    public function test_it_returns_a_409_on_duplicate_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::fileExists('already/existing.jpg')
        );

        $this->assertHttpException($e, 409);
    }

    public function test_it_returns_a_413_for_too_big_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::fileIsTooBig(3, 2)
        );

        $this->assertHttpException($e, 413);
    }

    public function test_it_returns_a_415_for_type_mismatch()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::strictTypeMismatch('text/foo', 'bar')
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_unknown_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::unrecognizedFileType('text/foo', 'bar')
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::mimeRestricted('text/foo', ['text/bar'])
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_extension()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::extensionRestricted('foo', ['bar'])
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_aggregate_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::aggregateTypeRestricted('foo', ['bar'])
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_422_if_invalid_adapter_for_class()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::cannotSetAdapter(stdClass::class)
        );

        $this->assertHttpException($e, 422);
    }

    public function test_it_returns_a_422_for_invalid_model()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::cannotSetModel(stdClass::class)
        );

        $this->assertHttpException($e, 422);
    }

    public function test_it_returns_a_422_for_unrecognized_source()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::unrecognizedSource('foo')
        );

        $this->assertHttpException($e, 422);
    }

    public function test_it_returns_a_422_if_no_source_is_set()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaUploadException::noSourceProvided()
        );

        $this->assertHttpException($e, 422);
    }

    public function test_it_returns_a_500_for_a_generic_media_upload_exception()
    {
        $e = (new SampleExceptionHandler())->render(
            new MediaUploadException()
        );

        $this->assertHttpException($e, 500);
    }

    public function test_it_skips_any_other_exception()
    {
        $e = (new SampleExceptionHandler())->render(
            new Exception()
        );

        $this->assertFalse($e instanceof HttpException);
    }

    protected function assertHttpException($e, $code)
    {
        $this->assertInstanceOf(HttpException::class, $e);
        $this->assertEquals($code, $e->getStatusCode());
    }
}
