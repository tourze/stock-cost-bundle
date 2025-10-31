<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * @internal
 */
#[CoversClass(CostRecord::class)]
class CostRecordStockBatchTest extends AbstractEntityTestCase
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
            ['stockBatch', new StockBatch()],
        ];
    }

    public function testCostRecordCanAssociateWithStockBatch(): void
    {
        $costRecord = new CostRecord();
        $stockBatch = new StockBatch();

        $costRecord->setStockBatch($stockBatch);

        $this->assertSame($stockBatch, $costRecord->getStockBatch());
    }

    public function testCostRecordCanHaveNullStockBatch(): void
    {
        $costRecord = new CostRecord();

        $this->assertNull($costRecord->getStockBatch());

        // 可以设置为null
        $costRecord->setStockBatch(null);
        $this->assertNull($costRecord->getStockBatch());
    }

    public function testCostRecordCanDeriveInfoFromStockBatch(): void
    {
        $stockBatch = new StockBatch();
        $stockBatch->setBatchNo('BATCH-001');
        $stockBatch->setUnitCost(15.50);

        $costRecord = new CostRecord();
        $costRecord->setStockBatch($stockBatch);

        // CostRecord应该能从StockBatch获取信息
        $this->assertEquals('BATCH-001', $costRecord->getStockBatch()?->getBatchNo());
        $this->assertEquals(15.50, $costRecord->getStockBatch()?->getUnitCost());
    }

    public function testCostRecordBatchNoSyncsWithStockBatch(): void
    {
        $stockBatch = new StockBatch();
        $stockBatch->setBatchNo('BATCH-002');

        $costRecord = new CostRecord();
        $costRecord->setStockBatch($stockBatch);

        // 当设置StockBatch时，应该自动同步批次号
        $costRecord->syncFromStockBatch();

        $this->assertEquals('BATCH-002', $costRecord->getBatchNo());
    }

    public function testCostRecordCanSyncDataFromStockBatch(): void
    {
        $stockBatch = new StockBatch();
        $stockBatch->setBatchNo('BATCH-003');
        $stockBatch->setUnitCost(25.75);

        $costRecord = new CostRecord();
        $costRecord->setStockBatch($stockBatch);
        $costRecord->setQuantity(100);

        // 同步数据
        $costRecord->syncFromStockBatch();

        $this->assertEquals('BATCH-003', $costRecord->getBatchNo());
        $this->assertEquals(25.75, $costRecord->getUnitCost());
        $this->assertEquals(2575.00, $costRecord->calculateTotalCost());
    }
}
