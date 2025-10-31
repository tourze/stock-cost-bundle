<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\ConsistencyFixer;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * @internal
 */
#[CoversClass(ConsistencyFixer::class)]
class ConsistencyFixerTest extends TestCase
{
    private ConsistencyFixer $fixer;

    private CostRecordRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CostRecordRepository::class);
        $this->fixer = new ConsistencyFixer($this->repository);
    }

    public function testFixSingleRecordInconsistencyNoIssues(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertFalse($result); // 没有修复任何内容
    }

    public function testFixSingleRecordInconsistencyWithSkuMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-002'); // 不匹配
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertTrue($result); // 修复了SKU不匹配
        $this->assertEquals('SKU-001', $costRecord->getSkuId()); // SKU被修复
        $this->assertEquals(525.00, $costRecord->getTotalCost()); // 总成本被重新计算
    }

    public function testFixSingleRecordInconsistencyWithBatchNoMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B002'); // 不匹配
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertTrue($result); // 修复了批次号不匹配
        $this->assertEquals('B001', $costRecord->getBatchNo()); // 批次号被修复
        $this->assertEquals(525.00, $costRecord->getTotalCost()); // 总成本被重新计算
    }

    public function testFixSingleRecordInconsistencyWithUnitCostMismatch(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(12.00); // 不匹配
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(600.00);
        $costRecord->setStockBatch($stockBatch);

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertTrue($result); // 修复了单位成本不匹配
        $this->assertEquals(10.50, $costRecord->getUnitCost()); // 单位成本被修复
        $this->assertEquals(525.00, $costRecord->getTotalCost()); // 总成本被重新计算
    }

    public function testFixSingleRecordInconsistencyWithoutStockBatch(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        // 不设置 StockBatch

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertFalse($result); // 没有StockBatch，无法修复
    }

    public function testFixInconsistentRecords(): void
    {
        $stockBatch1 = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);
        $stockBatch2 = $this->createStockBatch('B002', 'SKU-001', 12.00, 50);

        // 创建有问题的记录
        $costRecord1 = new CostRecord();
        $costRecord1->setSkuId('SKU-002'); // SKU不匹配
        $costRecord1->setBatchNo('B001');
        $costRecord1->setUnitCost(10.50);
        $costRecord1->setQuantity(30);
        $costRecord1->setTotalCost(315.00);
        $costRecord1->setStockBatch($stockBatch1);

        $costRecord2 = new CostRecord();
        $costRecord2->setSkuId('SKU-001');
        $costRecord2->setBatchNo('B003'); // 批次号不匹配
        $costRecord2->setUnitCost(12.00);
        $costRecord2->setQuantity(20);
        $costRecord2->setTotalCost(240.00);
        $costRecord2->setStockBatch($stockBatch2);

        $costRecords = [$costRecord1, $costRecord2];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($costRecords);

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(2, $result['fixed']); // 修复了2条记录
        $this->assertEmpty($result['errors']);

        // 验证修复结果
        $this->assertEquals('SKU-001', $costRecord1->getSkuId()); // SKU被修复
        $this->assertEquals('B002', $costRecord2->getBatchNo()); // 批次号被修复
    }

    public function testFixInconsistentRecordsWithNoIssues(): void
    {
        $stockBatch = $this->createStockBatch('B001', 'SKU-001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setStockBatch($stockBatch);

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$costRecord]);

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(0, $result['fixed']); // 没有修复任何内容
        $this->assertEmpty($result['errors']);
    }

    public function testFixInconsistentRecordsWithEmptyList(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(0, $result['fixed']); // 没有记录需要修复
        $this->assertEmpty($result['errors']);
    }

    private function createStockBatch(string $batchNo, string $skuId, float $unitCost, int $quantity): StockBatch
    {
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn($skuId);

        $batch = new StockBatch();
        $batch->setBatchNo($batchNo);
        $batch->setSku($sku);
        $batch->setUnitCost($unitCost);
        $batch->setQuantity($quantity);

        return $batch;
    }
}
