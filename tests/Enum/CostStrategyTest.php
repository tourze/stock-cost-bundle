<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * @internal
 */
#[CoversClass(CostStrategy::class)]
class CostStrategyTest extends AbstractEnumTestCase
{
    public function testEnumHasFourStrategies(): void
    {
        $strategies = CostStrategy::cases();

        $this->assertCount(4, $strategies);
        $this->assertEquals([
            CostStrategy::FIFO,
            CostStrategy::LIFO,
            CostStrategy::WEIGHTED_AVERAGE,
            CostStrategy::STANDARD_COST,
        ], $strategies);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('FIFO', CostStrategy::FIFO->value);
        $this->assertEquals('LIFO', CostStrategy::LIFO->value);
        $this->assertEquals('WEIGHTED_AVERAGE', CostStrategy::WEIGHTED_AVERAGE->value);
        $this->assertEquals('STANDARD_COST', CostStrategy::STANDARD_COST->value);
    }

    public function testFromString(): void
    {
        $this->assertEquals(CostStrategy::FIFO, CostStrategy::from('FIFO'));
        $this->assertEquals(CostStrategy::LIFO, CostStrategy::from('LIFO'));
        $this->assertEquals(CostStrategy::WEIGHTED_AVERAGE, CostStrategy::from('WEIGHTED_AVERAGE'));
        $this->assertEquals(CostStrategy::STANDARD_COST, CostStrategy::from('STANDARD_COST'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        CostStrategy::from('INVALID_STRATEGY');
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(CostStrategy::FIFO, CostStrategy::tryFrom('FIFO'));
        $this->assertEquals(CostStrategy::LIFO, CostStrategy::tryFrom('LIFO'));
        $this->assertNull(CostStrategy::tryFrom('INVALID_STRATEGY'));
    }

    public function testToString(): void
    {
        $this->assertEquals('FIFO', CostStrategy::FIFO->value);
        $this->assertEquals('LIFO', CostStrategy::LIFO->value);
        $this->assertEquals('WEIGHTED_AVERAGE', CostStrategy::WEIGHTED_AVERAGE->value);
        $this->assertEquals('STANDARD_COST', CostStrategy::STANDARD_COST->value);
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('先进先出法', CostStrategy::FIFO->getDescription());
        $this->assertEquals('后进先出法', CostStrategy::LIFO->getDescription());
        $this->assertEquals('加权平均法', CostStrategy::WEIGHTED_AVERAGE->getDescription());
        $this->assertEquals('标准成本法', CostStrategy::STANDARD_COST->getDescription());
    }

    public function testIsInventoryBased(): void
    {
        $this->assertTrue(CostStrategy::FIFO->isInventoryBased());
        $this->assertTrue(CostStrategy::LIFO->isInventoryBased());
        $this->assertFalse(CostStrategy::WEIGHTED_AVERAGE->isInventoryBased());
        $this->assertFalse(CostStrategy::STANDARD_COST->isInventoryBased());
    }

    public function testGetValues(): void
    {
        $values = CostStrategy::getValues();

        $this->assertEquals([
            'FIFO',
            'LIFO',
            'WEIGHTED_AVERAGE',
            'STANDARD_COST',
        ], $values);
    }

    public function testToArray(): void
    {
        $array = CostStrategy::FIFO->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('FIFO', $array['value']);
        $this->assertEquals('先进先出法', $array['label']);
    }
}
