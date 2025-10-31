<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;

/**
 * @internal
 */
#[CoversClass(CostRecord::class)]
class CostRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CostRecord();
    }

    /**
     * @return array<array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['skuId', 'SKU-001'],
            ['batchNo', 'BATCH-001'],
            ['unitCost', 15.50],
            ['quantity', 100],
            ['totalCost', 1550.00],
            ['costStrategy', CostStrategy::FIFO],
            ['costType', CostType::DIRECT],
            ['period', new CostPeriod()],
            ['operator', 'system'],
        ];
    }

    public function testCostRecordCanBeInstantiated(): void
    {
        $record = new CostRecord();

        $this->assertInstanceOf(CostRecord::class, $record);
    }

    public function testCostRecordHasRequiredProperties(): void
    {
        $record = new CostRecord();
        $period = new CostPeriod();

        $record->setSkuId('SKU-001');
        $record->setBatchNo('BATCH-001');
        $record->setUnitCost(15.50);
        $record->setQuantity(100);
        $record->setTotalCost(1550.00);
        $record->setCostStrategy(CostStrategy::FIFO);
        $record->setCostType(CostType::DIRECT);
        $record->setPeriod($period);
        $record->setOperator('system');

        $this->assertEquals('SKU-001', $record->getSkuId());
        $this->assertEquals('BATCH-001', $record->getBatchNo());
        $this->assertEquals(15.50, $record->getUnitCost());
        $this->assertEquals(100, $record->getQuantity());
        $this->assertEquals(1550.00, $record->getTotalCost());
        $this->assertEquals(CostStrategy::FIFO, $record->getCostStrategy());
        $this->assertEquals(CostType::DIRECT, $record->getCostType());
        $this->assertSame($period, $record->getPeriod());
        $this->assertEquals('system', $record->getOperator());
    }

    public function testCostRecordHasTimestamps(): void
    {
        $record = new CostRecord();

        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getRecordedAt());
        $this->assertNull($record->getUpdatedAt());

        // 模拟更新
        $record->setUpdatedAt(new \DateTimeImmutable());
        $this->assertInstanceOf(\DateTimeImmutable::class, $record->getUpdatedAt());
    }

    public function testCostRecordHasMetadata(): void
    {
        $record = new CostRecord();
        $metadata = ['source' => 'import', 'batch_date' => '2024-01-01'];

        $record->setMetadata($metadata);

        $this->assertEquals($metadata, $record->getMetadata());
    }

    public function testCostRecordValidatesPositiveQuantity(): void
    {
        $record = new CostRecord();

        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        $record->setQuantity(-10);
    }

    public function testCostRecordCalculatesTotalCostFromUnitAndQuantity(): void
    {
        $record = new CostRecord();
        $record->setUnitCost(15.50);
        $record->setQuantity(100);

        $calculatedTotal = $record->calculateTotalCost();

        $this->assertEquals(1550.00, $calculatedTotal);
    }

    public function testCostRecordSupportsStringable(): void
    {
        $record = new CostRecord();
        $record->setSkuId('SKU-001');
        $record->setQuantity(100);
        $record->setUnitCost(15.50);

        $string = $record->__toString();

        $this->assertStringContainsString('SKU-001', $string);
        $this->assertStringContainsString('100', $string);
        $this->assertStringContainsString('15.50', $string);
    }

    public function testCostRecordTotalCostPrecision(): void
    {
        $record = new CostRecord();
        $record->setUnitCost(10.333);
        $record->setQuantity(3);

        // 总成本应该精确到2位小数
        $record->setTotalCost($record->calculateTotalCost());

        $this->assertEquals(30.999, round($record->calculateTotalCost(), 3)); // 原始计算
        $this->assertEquals(31.00, round($record->getTotalCost(), 2)); // 存储时四舍五入
    }
}
