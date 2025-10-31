<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\EventDispatcher\Event;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\StockCostBundle\Event\CostVarianceExceededEvent;

/**
 * @internal
 */
#[CoversClass(CostVarianceExceededEvent::class)]
class CostVarianceExceededEventTest extends AbstractEventTestCase
{
    public function testEventCanBeInstantiated(): void
    {
        $skuId = 'SKU-001';
        $actualCost = 100.00;
        $standardCost = 90.00;
        $absoluteVariance = 10.00;
        $relativeVariance = 0.1111;
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $event = new CostVarianceExceededEvent(
            $skuId,
            $actualCost,
            $standardCost,
            $absoluteVariance,
            $relativeVariance,
            $occurredAt
        );

        $this->assertInstanceOf(CostVarianceExceededEvent::class, $event);
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testEventUsesDefaultOccurredAtIfNotProvided(): void
    {
        $before = new \DateTimeImmutable();

        $event = new CostVarianceExceededEvent(
            'SKU-001',
            100.00,
            90.00,
            10.00,
            0.1111
        );

        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $event->getOccurredAt());
        $this->assertLessThanOrEqual($after, $event->getOccurredAt());
    }

    public function testGetters(): void
    {
        $skuId = 'SKU-001';
        $actualCost = 100.00;
        $standardCost = 90.00;
        $absoluteVariance = 10.00;
        $relativeVariance = 0.1111;
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $event = new CostVarianceExceededEvent(
            $skuId,
            $actualCost,
            $standardCost,
            $absoluteVariance,
            $relativeVariance,
            $occurredAt
        );

        $this->assertEquals($skuId, $event->getSkuId());
        $this->assertEquals($actualCost, $event->getActualCost());
        $this->assertEquals($standardCost, $event->getStandardCost());
        $this->assertEquals($absoluteVariance, $event->getAbsoluteVariance());
        $this->assertEquals($relativeVariance, $event->getRelativeVariance());
        $this->assertEquals($occurredAt, $event->getOccurredAt());
    }

    public function testGetVariancePercentage(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-001',
            100.00,
            90.00,
            10.00,
            0.1111
        );

        $this->assertEqualsWithDelta(11.11, $event->getVariancePercentage(), 0.01);
    }

    public function testIsUnfavorableVarianceWhenActualCostIsHigher(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-001',
            100.00, // 实际成本更高
            90.00,  // 标准成本
            10.00,
            0.1111
        );

        $this->assertTrue($event->isUnfavorableVariance());
        $this->assertFalse($event->isFavorableVariance());
    }

    public function testIsFavorableVarianceWhenActualCostIsLower(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-001',
            80.00, // 实际成本更低
            90.00, // 标准成本
            -10.00,
            -0.1111
        );

        $this->assertFalse($event->isUnfavorableVariance());
        $this->assertTrue($event->isFavorableVariance());
    }

    public function testEqualCostsAreNeitherFavorableNorUnfavorable(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-001',
            90.00, // 相等成本
            90.00,
            0.00,
            0.00
        );

        $this->assertFalse($event->isUnfavorableVariance());
        $this->assertFalse($event->isFavorableVariance());
    }

    public function testToArray(): void
    {
        $skuId = 'SKU-001';
        $actualCost = 100.00;
        $standardCost = 90.00;
        $absoluteVariance = 10.00;
        $relativeVariance = 0.1111;
        $occurredAt = new \DateTimeImmutable('2024-01-15 10:30:00');

        $event = new CostVarianceExceededEvent(
            $skuId,
            $actualCost,
            $standardCost,
            $absoluteVariance,
            $relativeVariance,
            $occurredAt
        );

        $array = $event->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('sku_id', $array);
        $this->assertArrayHasKey('actual_cost', $array);
        $this->assertArrayHasKey('standard_cost', $array);
        $this->assertArrayHasKey('absolute_variance', $array);
        $this->assertArrayHasKey('relative_variance', $array);
        $this->assertArrayHasKey('variance_percentage', $array);
        $this->assertArrayHasKey('is_unfavorable', $array);
        $this->assertArrayHasKey('occurred_at', $array);

        $this->assertEquals($skuId, $array['sku_id']);
        $this->assertEquals($actualCost, $array['actual_cost']);
        $this->assertEquals($standardCost, $array['standard_cost']);
        $this->assertEquals($absoluteVariance, $array['absolute_variance']);
        $this->assertEquals($relativeVariance, $array['relative_variance']);
        $this->assertEqualsWithDelta(11.11, $array['variance_percentage'], 0.01);
        $this->assertTrue($array['is_unfavorable']);
        $this->assertEquals($occurredAt, $array['occurred_at']);
    }

    public function testToArrayWithFavorableVariance(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-001',
            80.00,
            90.00,
            -10.00,
            -0.1111
        );

        $array = $event->toArray();

        $this->assertFalse($array['is_unfavorable']);
        $this->assertEquals(-11.11, round($array['variance_percentage'], 2));
    }

    public function testEventWithLargeNumbers(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-EXPENSIVE',
            10000.00,
            9500.00,
            500.00,
            0.0526
        );

        $this->assertEquals(5.26, $event->getVariancePercentage());
        $this->assertTrue($event->isUnfavorableVariance());
        $this->assertFalse($event->isFavorableVariance());
    }

    public function testEventWithSmallNumbers(): void
    {
        $event = new CostVarianceExceededEvent(
            'SKU-CHEAP',
            1.05,
            1.00,
            0.05,
            0.05
        );

        $this->assertEquals(5.00, $event->getVariancePercentage());
        $this->assertTrue($event->isUnfavorableVariance());
        $this->assertFalse($event->isFavorableVariance());
    }

    public function testEventWithZeroStandardCost(): void
    {
        // 虽然业务上不太可能，但测试边界情况
        $event = new CostVarianceExceededEvent(
            'SKU-FREE',
            10.00,
            0.00,
            10.00,
            PHP_FLOAT_MAX // 无穷大的相对差异
        );

        $this->assertEquals(PHP_FLOAT_MAX * 100, $event->getVariancePercentage());
        $this->assertTrue($event->isUnfavorableVariance());
    }
}
