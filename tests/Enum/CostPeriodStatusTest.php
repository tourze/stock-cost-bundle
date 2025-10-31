<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;

/**
 * @internal
 */
#[CoversClass(CostPeriodStatus::class)]
class CostPeriodStatusTest extends AbstractEnumTestCase
{
    public function testEnumHasThreeStatuses(): void
    {
        $statuses = CostPeriodStatus::cases();

        $this->assertCount(3, $statuses);
        $this->assertEquals([
            CostPeriodStatus::OPEN,
            CostPeriodStatus::CLOSED,
            CostPeriodStatus::FROZEN,
        ], $statuses);
    }

    public function testEnumValues(): void
    {
        $this->assertEquals('OPEN', CostPeriodStatus::OPEN->value);
        $this->assertEquals('CLOSED', CostPeriodStatus::CLOSED->value);
        $this->assertEquals('FROZEN', CostPeriodStatus::FROZEN->value);
    }

    public function testFromString(): void
    {
        $this->assertEquals(CostPeriodStatus::OPEN, CostPeriodStatus::from('OPEN'));
        $this->assertEquals(CostPeriodStatus::CLOSED, CostPeriodStatus::from('CLOSED'));
        $this->assertEquals(CostPeriodStatus::FROZEN, CostPeriodStatus::from('FROZEN'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        CostPeriodStatus::from('INVALID_STATUS');
    }

    public function testTryFromString(): void
    {
        $this->assertEquals(CostPeriodStatus::OPEN, CostPeriodStatus::tryFrom('OPEN'));
        $this->assertEquals(CostPeriodStatus::CLOSED, CostPeriodStatus::tryFrom('CLOSED'));
        $this->assertEquals(CostPeriodStatus::FROZEN, CostPeriodStatus::tryFrom('FROZEN'));
        $this->assertNull(CostPeriodStatus::tryFrom('INVALID_STATUS'));
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('开放', CostPeriodStatus::OPEN->getDescription());
        $this->assertEquals('已关闭', CostPeriodStatus::CLOSED->getDescription());
        $this->assertEquals('已冻结', CostPeriodStatus::FROZEN->getDescription());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('开放', CostPeriodStatus::OPEN->getLabel());
        $this->assertEquals('已关闭', CostPeriodStatus::CLOSED->getLabel());
        $this->assertEquals('已冻结', CostPeriodStatus::FROZEN->getLabel());
    }

    public function testLabelEqualsDescription(): void
    {
        foreach (CostPeriodStatus::cases() as $status) {
            $this->assertEquals($status->getDescription(), $status->getLabel());
        }
    }

    public function testIsModifiable(): void
    {
        $this->assertTrue(CostPeriodStatus::OPEN->isModifiable());
        $this->assertFalse(CostPeriodStatus::CLOSED->isModifiable());
        $this->assertFalse(CostPeriodStatus::FROZEN->isModifiable());
    }

    public function testCanClose(): void
    {
        $this->assertTrue(CostPeriodStatus::OPEN->canClose());
        $this->assertFalse(CostPeriodStatus::CLOSED->canClose());
        $this->assertFalse(CostPeriodStatus::FROZEN->canClose());
    }

    public function testCanFreeze(): void
    {
        $this->assertFalse(CostPeriodStatus::OPEN->canFreeze());
        $this->assertTrue(CostPeriodStatus::CLOSED->canFreeze());
        $this->assertFalse(CostPeriodStatus::FROZEN->canFreeze());
    }

    public function testCanUnfreeze(): void
    {
        $this->assertFalse(CostPeriodStatus::OPEN->canUnfreeze());
        $this->assertFalse(CostPeriodStatus::CLOSED->canUnfreeze());
        $this->assertTrue(CostPeriodStatus::FROZEN->canUnfreeze());
    }

    public function testGetChoices(): void
    {
        $choices = CostPeriodStatus::getChoices();

        $this->assertIsArray($choices);
        $this->assertArrayHasKey('开放', $choices);
        $this->assertArrayHasKey('已关闭', $choices);
        $this->assertArrayHasKey('已冻结', $choices);

        $this->assertEquals('OPEN', $choices['开放']);
        $this->assertEquals('CLOSED', $choices['已关闭']);
        $this->assertEquals('FROZEN', $choices['已冻结']);
    }

    public function testToArray(): void
    {
        $array = CostPeriodStatus::OPEN->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('OPEN', $array['value']);
        $this->assertEquals('开放', $array['label']);
    }

    public function testAllStatusesHaveUniqueValues(): void
    {
        $values = array_map(fn (CostPeriodStatus $status) => $status->value, CostPeriodStatus::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues, '所有状态的值都应该是唯一的');
    }

    public function testAllStatusesHaveUniqueDescriptions(): void
    {
        $descriptions = array_map(fn (CostPeriodStatus $status) => $status->getDescription(), CostPeriodStatus::cases());
        $uniqueDescriptions = array_unique($descriptions);

        $this->assertCount(count($descriptions), $uniqueDescriptions, '所有状态的描述都应该是唯一的');
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Itemable::class, CostPeriodStatus::OPEN);
        $this->assertInstanceOf(Labelable::class, CostPeriodStatus::OPEN);
        $this->assertInstanceOf(Selectable::class, CostPeriodStatus::OPEN);
    }

    public function testStatusTransitionsLogic(): void
    {
        // 测试状态转换逻辑的完整性

        // OPEN状态：可修改，可关闭，不可冻结，不可解冻
        $this->assertTrue(CostPeriodStatus::OPEN->isModifiable());
        $this->assertTrue(CostPeriodStatus::OPEN->canClose());
        $this->assertFalse(CostPeriodStatus::OPEN->canFreeze());
        $this->assertFalse(CostPeriodStatus::OPEN->canUnfreeze());

        // CLOSED状态：不可修改，不可关闭，可冻结，不可解冻
        $this->assertFalse(CostPeriodStatus::CLOSED->isModifiable());
        $this->assertFalse(CostPeriodStatus::CLOSED->canClose());
        $this->assertTrue(CostPeriodStatus::CLOSED->canFreeze());
        $this->assertFalse(CostPeriodStatus::CLOSED->canUnfreeze());

        // FROZEN状态：不可修改，不可关闭，不可冻结，可解冻
        $this->assertFalse(CostPeriodStatus::FROZEN->isModifiable());
        $this->assertFalse(CostPeriodStatus::FROZEN->canClose());
        $this->assertFalse(CostPeriodStatus::FROZEN->canFreeze());
        $this->assertTrue(CostPeriodStatus::FROZEN->canUnfreeze());
    }
}
