<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\StockCostBundle\Enum\AllocationMethod;

/**
 * @internal
 */
#[CoversClass(AllocationMethod::class)]
class AllocationMethodTest extends AbstractEnumTestCase
{
    public function testEnumHasFourAllocationMethods(): void
    {
        $methods = AllocationMethod::cases();

        $this->assertCount(4, $methods);
        $this->assertEquals([
            AllocationMethod::RATIO,
            AllocationMethod::QUANTITY,
            AllocationMethod::VALUE,
            AllocationMethod::ACTIVITY,
        ], $methods);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('ratio', AllocationMethod::RATIO->value);
        $this->assertEquals('quantity', AllocationMethod::QUANTITY->value);
        $this->assertEquals('value', AllocationMethod::VALUE->value);
        $this->assertEquals('activity', AllocationMethod::ACTIVITY->value);
    }

    public function testFromString(): void
    {
        $this->assertEquals(AllocationMethod::RATIO, AllocationMethod::from('ratio'));
        $this->assertEquals(AllocationMethod::QUANTITY, AllocationMethod::from('quantity'));
        $this->assertEquals(AllocationMethod::VALUE, AllocationMethod::from('value'));
        $this->assertEquals(AllocationMethod::ACTIVITY, AllocationMethod::from('activity'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        AllocationMethod::from('invalid_method');
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(AllocationMethod::RATIO, AllocationMethod::tryFrom('ratio'));
        $this->assertEquals(AllocationMethod::QUANTITY, AllocationMethod::tryFrom('quantity'));
        $this->assertEquals(AllocationMethod::VALUE, AllocationMethod::tryFrom('value'));
        $this->assertEquals(AllocationMethod::ACTIVITY, AllocationMethod::tryFrom('activity'));
        $this->assertNull(AllocationMethod::tryFrom('invalid_method'));
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('比例分摊', AllocationMethod::RATIO->getDescription());
        $this->assertEquals('数量分摊', AllocationMethod::QUANTITY->getDescription());
        $this->assertEquals('价值分摊', AllocationMethod::VALUE->getDescription());
        $this->assertEquals('活动基础分摊', AllocationMethod::ACTIVITY->getDescription());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('比例分摊', AllocationMethod::RATIO->getLabel());
        $this->assertEquals('数量分摊', AllocationMethod::QUANTITY->getLabel());
        $this->assertEquals('价值分摊', AllocationMethod::VALUE->getLabel());
        $this->assertEquals('活动基础分摊', AllocationMethod::ACTIVITY->getLabel());
    }

    public function testLabelEqualsDescription(): void
    {
        foreach (AllocationMethod::cases() as $method) {
            $this->assertEquals($method->getDescription(), $method->getLabel());
        }
    }

    public function testToArray(): void
    {
        $array = AllocationMethod::RATIO->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('ratio', $array['value']);
        $this->assertEquals('比例分摊', $array['label']);
    }

    public function testAllMethodsHaveUniqueValues(): void
    {
        $values = array_map(fn (AllocationMethod $method) => $method->value, AllocationMethod::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues, '所有分摊方法的值都应该是唯一的');
    }

    public function testAllMethodsHaveUniqueDescriptions(): void
    {
        $descriptions = array_map(fn (AllocationMethod $method) => $method->getDescription(), AllocationMethod::cases());
        $uniqueDescriptions = array_unique($descriptions);

        $this->assertCount(count($descriptions), $uniqueDescriptions, '所有分摊方法的描述都应该是唯一的');
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, AllocationMethod::RATIO);
        $this->assertInstanceOf(Labelable::class, AllocationMethod::RATIO);
        $this->assertInstanceOf(Selectable::class, AllocationMethod::RATIO);
    }
}
