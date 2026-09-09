<?php

namespace Plank\Mediable\Tests\Integration\SourceAdapters;

use PHPUnit\Framework\Attributes\DataProvider;
use Plank\Mediable\Exceptions\MediaUpload\ConfigurationException;
use Plank\Mediable\SourceAdapters\RemoteUrlAdapter;
use Plank\Mediable\Tests\TestCase;

class RemoteUrlAdapterTest extends TestCase
{
    public function test_it_restricts_url_schema_with_no_restriction(): void
    {
        config()->set('mediable.allowed_remote_schemes', []);

        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter(TestCase::remoteFilePath())
        );
        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter('http://example.com')
        );
    }

    public function test_it_restricts_url_schema_with_restriction(): void
    {
        config()->set('mediable.allowed_remote_schemes', ['https']);

        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter(TestCase::remoteFilePath())
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Remote URL scheme 'http' is not allowed.");

        new RemoteUrlAdapter('http://example.com/image.jpg');
    }

    public function test_it_restricts_allowed_hosts(): void
    {
        config()->set('mediable.allowed_remote_hosts', [TestCase::remoteHost()]);

        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter(TestCase::remoteFilePath())
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Remote URL host is not in the allowlist.');

        $adapter = new RemoteUrlAdapter('https://notallowed.com/image.jpg');
    }

    public function test_it_restricts_allowed_hosts_with_wildcard(): void
    {
        config()->set('mediable.allowed_remote_hosts', ['*.githubusercontent.com']);

        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter(TestCase::remoteFilePath())
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Remote URL host is not in the allowlist.');

        new RemoteUrlAdapter('https://notallowed.com/image.jpg');
    }

    public function test_it_restricts_allowed_hosts_no_restriction(): void
    {
        config()->set('mediable.allowed_remote_hosts', []);

        $this->assertInstanceOf(
            RemoteUrlAdapter::class,
            new RemoteUrlAdapter(TestCase::remoteFilePath())
        );
    }

    public static function privateHostProvider(): array
    {
        return [
            'localhost' => ['https://localhost/image.jpg'],
            'AWS IMDSv1' => ['http://169.254.169.254'],
            'local loopback' => ['http://10.0.0.1/'],
            'internal api' => ['http://10.0.0.5:8080/'],
            'elasticsearch' => ['http://10.0.0.9:9200/'],
            'redis' => ['http://10.0.0.12:6379/'],
        ];
    }

    #[DataProvider('privateHostProvider')]
    public function test_it_restricts_private_hosts_when_no_allowed_host_restrictions_provided(string $privateHost): void
    {
        config()->set('mediable.allowed_remote_hosts', []);
        config()->set('mediable.allowed_remote_schemes', ['https', 'http']);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Private IP ranges are not permitted for remote URLs.'
        );

        new RemoteUrlAdapter($privateHost);
    }

    public function test_it_validates_hostname_on_redirects()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Remote URL host is not in the allowlist.'
        );
        config()->set('mediable.max_remote_url_redirects', 1);
        config()->set('mediable.allowed_remote_hosts', ['httpbin.org']);
        new RemoteUrlAdapter('https://httpbin.org/redirect-to?url=https://evil.com/foo');
    }

    public function test_it_validates_ip_range_on_redirects()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Private IP ranges are not permitted for remote URLs.'
        );
        config()->set('mediable.max_remote_url_redirects', 1);
        new RemoteUrlAdapter('https://httpbin.org/redirect-to?url=https://localhost/image.jpg');
    }

    public function test_it_limits_redirects()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Too many redirects for a remote URL.'
        );
        config()->set('mediable.max_remote_url_redirects', 2);
        new RemoteUrlAdapter('https://httpbin.org/redirect/3');
    }
}
