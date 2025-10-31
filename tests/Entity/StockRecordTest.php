<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * @internal
 */
#[CoversClass(StockRecord::class)]
class StockRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new StockRecord();
    }

    /**
     * @return array<array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['sku', 'SKU-001'],
            ['recordDate', new \DateTimeImmutable('2024-01-15')],
            ['originalQuantity', 100],
            ['currentQuantity', 80],
            ['unitCost', 15.50],
        ];
    }

    public function testStockRecordCanBeInstantiated(): void
    {
        $record = new StockRecord();

        $this->assertInstanceOf(StockRecord::class, $record);
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getUpdatedAt());
    }

    public function testStockRecordHasRequiredProperties(): void
    {
        $record = new StockRecord();
        $recordDate = new \DateTimeImmutable('2024-01-15');

        $record->setSku('SKU-001');
        $record->setRecordDate($recordDate);
        $record->setOriginalQuantity(100);
        $record->setCurrentQuantity(80);
        $record->setUnitCost(15.50);

        $this->assertEquals('SKU-001', $record->getSku());
        $this->assertEquals($recordDate, $record->getRecordDate());
        $this->assertEquals(100, $record->getOriginalQuantity());
        $this->assertEquals(80, $record->getCurrentQuantity());
        $this->assertEquals(15.50, $record->getUnitCost());
        $this->assertEquals(1240.00, $record->getTotalCost()); // 80 * 15.50
    }

    public function testStockRecordHasTimestamps(): void
    {
        $record = new StockRecord();
        $initialUpdatedAt = $record->getUpdatedAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $initialUpdatedAt);

        // 模拟数量更新会自动更新时间戳
        $record->setCurrentQuantity(50);
        $newUpdatedAt = $record->getUpdatedAt();

        $this->assertGreaterThanOrEqual($initialUpdatedAt, $newUpdatedAt);
    }

    public function testSetUnitCostCalculatesTotalCost(): void
    {
        $record = new StockRecord();
        $record->setCurrentQuantity(100);
        $record->setUnitCost(15.50);

        $this->assertEquals(1550.00, $record->getTotalCost());

        // 更新单位成本应该重新计算总成本
        $record->setUnitCost(20.00);
        $this->assertEquals(2000.00, $record->getTotalCost());
    }

    public function testSetCurrentQuantityUpdatesTotalCost(): void
    {
        $record = new StockRecord();
        $record->setUnitCost(15.50);
        $record->setCurrentQuantity(100);

        $this->assertEquals(1550.00, $record->getTotalCost());

        // 更新当前数量后总成本应该自动更新
        $record->setCurrentQuantity(80);
        $this->assertEquals(1240.00, $record->getTotalCost());
    }

    public function testIsAvailableMethod(): void
    {
        $record = new StockRecord();

        // 默认情况下数量为0，应该不可用
        $this->assertFalse($record->isAvailable());

        // 设置数量大于0后应该可用
        $record->setCurrentQuantity(50);
        $this->assertTrue($record->isAvailable());

        // 数量为0时应该不可用
        $record->setCurrentQuantity(0);
        $this->assertFalse($record->isAvailable());
    }

    public function testReduceQuantityMethod(): void
    {
        $record = new StockRecord();
        $record->setCurrentQuantity(100);
        $record->setUnitCost(10.00);

        // 减少数量
        $record->reduceQuantity(30);

        $this->assertEquals(70, $record->getCurrentQuantity());
        $this->assertEquals(700.00, $record->getTotalCost()); // 70 * 10.00
    }

    public function testReduceQuantityDoesNotGoBelowZero(): void
    {
        $record = new StockRecord();
        $record->setCurrentQuantity(50);
        $record->setUnitCost(10.00);

        // 尝试减少超过现有数量
        $record->reduceQuantity(80);

        $this->assertEquals(0, $record->getCurrentQuantity());
        $this->assertEquals(0.00, $record->getTotalCost());
    }

    public function testStringableInterface(): void
    {
        $record = new StockRecord();
        $recordDate = new \DateTimeImmutable('2024-01-15');

        $record->setSku('SKU-001');
        $record->setRecordDate($recordDate);
        $record->setCurrentQuantity(100);
        $record->setUnitCost(15.50);

        $string = $record->__toString();

        $this->assertStringContainsString('SKU-001', $string);
        $this->assertStringContainsString('2024-01-15', $string);
        $this->assertStringContainsString('100', $string);
        $this->assertStringContainsString('15.50', $string);
    }

    public function testStringableWithNullRecordDate(): void
    {
        $record = new StockRecord();
        $record->setSku('SKU-001');
        $record->setCurrentQuantity(100);
        $record->setUnitCost(15.50);
        // recordDate 保持为 null

        $string = $record->__toString();

        $this->assertStringContainsString('SKU-001', $string);
        $this->assertStringContainsString('N/A', $string);
        $this->assertStringContainsString('100', $string);
        $this->assertStringContainsString('15.50', $string);
    }

    public function testTotalCostPrecision(): void
    {
        $record = new StockRecord();
        $record->setCurrentQuantity(3);
        $record->setUnitCost(10.333);

        // 总成本应该保持精度
        $this->assertEqualsWithDelta(30.999, $record->getTotalCost(), 0.001);
    }

    public function testIndividualSetterCalls(): void
    {
        $record = new StockRecord();
        $recordDate = new \DateTimeImmutable('2024-01-15');

        // 测试独立的 setter 方法调用
        $record->setSku('SKU-001');
        $record->setRecordDate($recordDate);
        $record->setOriginalQuantity(100);
        $record->setCurrentQuantity(80);
        $record->setUnitCost(15.50);

        $this->assertEquals('SKU-001', $record->getSku());
        $this->assertEquals($recordDate, $record->getRecordDate());
        $this->assertEquals(100, $record->getOriginalQuantity());
        $this->assertEquals(80, $record->getCurrentQuantity());
        $this->assertEquals(15.50, $record->getUnitCost());
        $this->assertEquals(1240.00, $record->getTotalCost());
    }
}
