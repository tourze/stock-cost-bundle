<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\StockCostBundle\Enum\CostType;

/**
 * @internal
 */
#[CoversClass(CostType::class)]
class CostTypeTest extends AbstractEnumTestCase
{
    public function testEnumHasFiveCostTypes(): void
    {
        $costTypes = CostType::cases();

        $this->assertCount(5, $costTypes);
        $this->assertEquals([
            CostType::DIRECT,
            CostType::INDIRECT,
            CostType::MANUFACTURING,
            CostType::OVERHEAD,
            CostType::LABOR,
        ], $costTypes);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('DIRECT', CostType::DIRECT->value);
        $this->assertEquals('INDIRECT', CostType::INDIRECT->value);
        $this->assertEquals('MANUFACTURING', CostType::MANUFACTURING->value);
    }

    public function testFromString(): void
    {
        $this->assertEquals(CostType::DIRECT, CostType::from('DIRECT'));
        $this->assertEquals(CostType::INDIRECT, CostType::from('INDIRECT'));
        $this->assertEquals(CostType::MANUFACTURING, CostType::from('MANUFACTURING'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        CostType::from('INVALID_TYPE');
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(CostType::DIRECT, CostType::tryFrom('DIRECT'));
        $this->assertEquals(CostType::INDIRECT, CostType::tryFrom('INDIRECT'));
        $this->assertNull(CostType::tryFrom('INVALID_TYPE'));
    }

    public function testToString(): void
    {
        $this->assertEquals('DIRECT', CostType::DIRECT->value);
        $this->assertEquals('INDIRECT', CostType::INDIRECT->value);
        $this->assertEquals('MANUFACTURING', CostType::MANUFACTURING->value);
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('直接成本', CostType::DIRECT->getDescription());
        $this->assertEquals('间接成本', CostType::INDIRECT->getDescription());
        $this->assertEquals('制造成本', CostType::MANUFACTURING->getDescription());
    }

    public function testToArray(): void
    {
        $array = CostType::DIRECT->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('DIRECT', $array['value']);
        $this->assertEquals('直接成本', $array['label']);
    }
}
