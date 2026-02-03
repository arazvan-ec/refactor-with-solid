<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Port;

use App\Infrastructure\Port\HttpClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpClientInterface::class)]
final class HttpClientInterfaceTest extends TestCase
{
    #[Test]
    public function interface_exists(): void
    {
        $this->assertTrue(interface_exists(HttpClientInterface::class));
    }

    #[Test]
    public function interface_defines_request_method(): void
    {
        $reflection = new \ReflectionClass(HttpClientInterface::class);

        $this->assertTrue($reflection->hasMethod('request'));
        $this->assertTrue($reflection->getMethod('request')->isPublic());
    }

    #[Test]
    public function request_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(HttpClientInterface::class);
        $method = $reflection->getMethod('request');
        $parameters = $method->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertSame('method', $parameters[0]->getName());
        $this->assertSame('uri', $parameters[1]->getName());
        $this->assertSame('options', $parameters[2]->getName());
        $this->assertSame('transformer', $parameters[3]->getName());
    }

    #[Test]
    public function interface_defines_request_async_method(): void
    {
        $reflection = new \ReflectionClass(HttpClientInterface::class);

        $this->assertTrue($reflection->hasMethod('requestAsync'));
        $this->assertTrue($reflection->getMethod('requestAsync')->isPublic());
    }
}
