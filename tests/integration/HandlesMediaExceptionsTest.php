<?php

use Plank\Mediable\Exceptions\MediaSizeException;
use Plank\Mediable\Exceptions\MediaExistsException;
use Plank\Mediable\Exceptions\MediaNotFoundException;
use Plank\Mediable\Exceptions\MediaForbiddenException;
use Plank\Mediable\Exceptions\MediaNotSupportedException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HandlesMediaExceptionsTest extends TestCase
{
    public function test_it_returns_a_403_for_dissalowed_disk()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaForbiddenException::diskNotAllowed('foo')
        );

        $this->assertHttpException($e, 403);
    }

    public function test_it_returns_a_404_for_missing_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotFoundException::fileNotFound('non/existing.jpg')
        );

        $this->assertHttpException($e, 404);
    }

    public function test_it_returns_a_409_on_duplicate_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaExistsException::fileExists('already/existing.jpg')
        );

        $this->assertHttpException($e, 409);
    }

    public function test_it_returns_a_413_for_too_big_file()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaSizeException::fileIsTooBig(3, 2)
        );

        $this->assertHttpException($e, 413);
    }

    public function test_it_returns_a_415_for_type_mismatch()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotSupportedException::strictTypeMismatch('text/foo', 'bar')
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_unknown_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotSupportedException::unrecognizedFileType('text/foo', 'bar')
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotSupportedException::mimeRestricted('text/foo', ['text/bar'])
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_extension()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotSupportedException::extensionRestricted('foo', ['bar'])
        );

        $this->assertHttpException($e, 415);
    }

    public function test_it_returns_a_415_for_restricted_aggregate_type()
    {
        $e = (new SampleExceptionHandler())->render(
            MediaNotSupportedException::aggregateTypeRestricted('foo', ['bar'])
        );

        $this->assertHttpException($e, 415);
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
