<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Infrastructure\Adapter\GuzzlePromiseResolver;
use App\Infrastructure\Port\PromiseResolverInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GuzzlePromiseResolver::class)]
final class GuzzlePromiseResolverTest extends TestCase
{
    #[Test]
    public function implements_promise_resolver_interface(): void
    {
        $this->assertTrue(
            is_a(GuzzlePromiseResolver::class, PromiseResolverInterface::class, true)
        );
    }

    #[Test]
    public function settle_all_waits_for_all_promises(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $promises = [
            'first' => new FulfilledPromise(['data' => 1]),
            'second' => new FulfilledPromise(['data' => 2]),
            'third' => new FulfilledPromise(['data' => 3]),
        ];

        $results = $resolver->settleAll($promises);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('first', $results);
        $this->assertArrayHasKey('second', $results);
        $this->assertArrayHasKey('third', $results);
    }

    #[Test]
    public function settle_all_returns_state_for_each_promise(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $promises = [
            'success' => new FulfilledPromise(['ok' => true]),
            'failure' => new RejectedPromise(new \RuntimeException('Error')),
        ];

        $results = $resolver->settleAll($promises);

        $this->assertSame('fulfilled', $results['success']['state']);
        $this->assertSame('rejected', $results['failure']['state']);
    }

    #[Test]
    public function fulfilled_only_filters_rejected_promises(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $results = [
            'success1' => ['state' => 'fulfilled', 'value' => ['data' => 1]],
            'failure' => ['state' => 'rejected', 'reason' => new \RuntimeException('Error')],
            'success2' => ['state' => 'fulfilled', 'value' => ['data' => 2]],
        ];

        $fulfilled = $resolver->fulfilledOnly($results);

        $this->assertCount(2, $fulfilled);
        $this->assertArrayHasKey('success1', $fulfilled);
        $this->assertArrayHasKey('success2', $fulfilled);
        $this->assertArrayNotHasKey('failure', $fulfilled);
    }

    #[Test]
    public function fulfilled_only_returns_values_not_full_result(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $results = [
            'item' => ['state' => 'fulfilled', 'value' => ['id' => 123, 'name' => 'Test']],
        ];

        $fulfilled = $resolver->fulfilledOnly($results);

        $this->assertSame(['id' => 123, 'name' => 'Test'], $fulfilled['item']);
    }

    #[Test]
    public function wait_all_combines_settle_and_filter(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $promises = [
            'first' => new FulfilledPromise(['data' => 'one']),
            'second' => new RejectedPromise(new \RuntimeException('Skip this')),
            'third' => new FulfilledPromise(['data' => 'three']),
        ];

        $values = $resolver->waitAll($promises);

        $this->assertCount(2, $values);
        $this->assertSame(['data' => 'one'], $values['first']);
        $this->assertSame(['data' => 'three'], $values['third']);
        $this->assertArrayNotHasKey('second', $values);
    }

    #[Test]
    public function wait_all_returns_empty_array_when_all_rejected(): void
    {
        $resolver = new GuzzlePromiseResolver();

        $promises = [
            'fail1' => new RejectedPromise(new \RuntimeException('Error 1')),
            'fail2' => new RejectedPromise(new \RuntimeException('Error 2')),
        ];

        $values = $resolver->waitAll($promises);

        $this->assertSame([], $values);
    }
}
