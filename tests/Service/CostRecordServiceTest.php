<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\CostRecordService;

/**
 * @internal
 */
#[CoversClass(CostRecordService::class)]
#[RunTestsInSeparateProcesses]
class CostRecordServiceTest extends AbstractIntegrationTestCase
{
    private CostRecordService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CostRecordService::class);
        // 直接使用 self::getEntityManager() 和 self::getEntityManager()->getRepository()
        // 无需存储为属性，因为从未被读取
    }

    public function testGetCostRecordsForSku(): void
    {
        // 创建测试数据
        $record1 = $this->createCostRecord('SKU-001', 10.50, 100);
        $record2 = $this->createCostRecord('SKU-001', 11.00, 50);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $records = $this->service->getCostRecordsForSku('SKU-001');

        $this->assertCount(2, $records);
        $this->assertEquals(100, $records[0]->getQuantity());
        $this->assertEquals(50, $records[1]->getQuantity());
    }

    public function testCreateCostRecord(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-NEW');
        $costRecord->setUnitCost(12.50);
        $costRecord->setQuantity(80);
        $costRecord->setTotalCost(1000.00);
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test_user');

        $result = $this->service->createCostRecord($costRecord);

        $this->assertSame($costRecord, $result);

        // 验证记录已保存到数据库
        self::getEntityManager()->clear();
        $savedRecord = self::getEntityManager()->getRepository(CostRecord::class)
            ->findOneBy(['skuId' => 'SKU-NEW'])
        ;

        $this->assertInstanceOf(CostRecord::class, $savedRecord);
        $this->assertEquals('SKU-NEW', $savedRecord->getSkuId());
        $this->assertEquals(12.50, $savedRecord->getUnitCost());
    }

    public function testGetTotalCostForSku(): void
    {
        // 创建测试数据
        $record1 = $this->createCostRecord('SKU-TOTAL', 10.00, 100);
        $record2 = $this->createCostRecord('SKU-TOTAL', 15.00, 50);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $totalCost = $this->service->getTotalCostForSku('SKU-TOTAL');

        $this->assertEquals(1750.00, $totalCost); // 1000 + 750
    }

    public function testGetTotalCostForSkuWithDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        // 创建不同日期的记录
        $record1 = $this->createCostRecord('SKU-DATE', 10.00, 100);
        $record1->setRecordedAt(new \DateTimeImmutable('2023-12-31'));

        $record2 = $this->createCostRecord('SKU-DATE', 15.00, 50);
        $record2->setRecordedAt(new \DateTimeImmutable('2024-01-15'));

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $totalCost = $this->service->getTotalCostForSku('SKU-DATE', $date);

        $this->assertEquals(1000.00, $totalCost); // Only the record from 2023-12-31
    }

    public function testGetAverageCostForSku(): void
    {
        // 创建测试数据
        $record1 = $this->createCostRecord('SKU-AVG', 10.00, 100);
        $record2 = $this->createCostRecord('SKU-AVG', 20.00, 100);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $averageCost = $this->service->getAverageCostForSku('SKU-AVG');

        $this->assertEquals(15.00, $averageCost); // (10 + 20) / 2
    }

    public function testGetLatestCostRecord(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        // 创建不同时间的记录
        $oldRecord = $this->createCostRecord('SKU-LATEST', 10.00, 100);
        $oldRecord->setRecordedAt(new \DateTimeImmutable('2023-12-01'));

        $newRecord = $this->createCostRecord('SKU-LATEST', 12.00, 100);
        $newRecord->setRecordedAt(new \DateTimeImmutable('2024-01-15'));

        self::getEntityManager()->persist($oldRecord);
        self::getEntityManager()->persist($newRecord);
        self::getEntityManager()->flush();

        $record = $this->service->getLatestCostRecord('SKU-LATEST', $date);

        $this->assertInstanceOf(CostRecord::class, $record);
        $this->assertEquals(10.00, $record->getUnitCost()); // The older record
    }

    public function testUpdateCostRecord(): void
    {
        $costRecord = $this->createCostRecord('SKU-UPDATE', 10.50, 100);

        self::getEntityManager()->persist($costRecord);
        self::getEntityManager()->flush();

        $costRecord->setUnitCost(11.00);
        $costRecord->setTotalCost(1100.00);

        $result = $this->service->updateCostRecord($costRecord);

        $this->assertSame($costRecord, $result);

        // 验证更新已保存到数据库
        self::getEntityManager()->clear();
        $updatedRecord = self::getEntityManager()->getRepository(CostRecord::class)
            ->find($costRecord->getId())
        ;
        $this->assertNotNull($updatedRecord);

        $this->assertEquals(11.00, $updatedRecord->getUnitCost());
        $this->assertEquals(1100.00, $updatedRecord->getTotalCost());
    }

    public function testDeleteCostRecord(): void
    {
        $costRecord = $this->createCostRecord('SKU-DELETE', 10.50, 100);

        self::getEntityManager()->persist($costRecord);
        self::getEntityManager()->flush();

        $recordId = $costRecord->getId();

        $this->service->deleteCostRecord($costRecord);

        // 验证记录已从数据库中删除
        $deletedRecord = self::getEntityManager()->getRepository(CostRecord::class)
            ->find($recordId)
        ;

        $this->assertNull($deletedRecord);
    }

    public function testGetCostRecordsByDateRange(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        // 创建不同日期的记录
        $record1 = $this->createCostRecord('SKU-RANGE', 10.50, 100);
        $record1->setRecordedAt(new \DateTimeImmutable('2024-01-15'));

        $record2 = $this->createCostRecord('SKU-RANGE-2', 12.00, 75);
        $record2->setRecordedAt(new \DateTimeImmutable('2024-01-20'));

        $record3 = $this->createCostRecord('SKU-RANGE-3', 15.00, 50);
        $record3->setRecordedAt(new \DateTimeImmutable('2024-02-15')); // Outside range

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $records = $this->service->getCostRecordsByDateRange($startDate, $endDate);

        $this->assertCount(2, $records);
        // Results are ordered by recordedAt DESC, so record2 (01-20) comes before record1 (01-15)
        $this->assertEquals('SKU-RANGE-2', $records[0]->getSkuId());
        $this->assertEquals('SKU-RANGE', $records[1]->getSkuId());
    }

    public function testGetCostRecordsForSkuWithNoRecords(): void
    {
        $records = $this->service->getCostRecordsForSku('NON-EXISTENT');

        $this->assertEmpty($records);
    }

    public function testGetTotalCostForSkuWithNoRecords(): void
    {
        $totalCost = $this->service->getTotalCostForSku('NON-EXISTENT');

        $this->assertEquals(0.0, $totalCost);
    }

    public function testCalculateTotalCost(): void
    {
        $costRecord = $this->createCostRecord('SKU-CALC', 12.50, 100);

        $totalCost = $this->service->calculateTotalCost($costRecord);

        $this->assertEquals(1250.00, $totalCost);
    }

    public function testCalculateTotalCostWithSmallQuantity(): void
    {
        $costRecord = $this->createCostRecord('SKU-SMALL', 10.00, 1);

        $totalCost = $this->service->calculateTotalCost($costRecord);

        $this->assertEquals(10.0, $totalCost);
    }

    public function testFormatCostRecordString(): void
    {
        $costRecord = $this->createCostRecord('SKU-FORMAT', 12.50, 100);

        $formattedString = $this->service->formatCostRecordString($costRecord);

        $this->assertEquals(
            'CostRecord(sku=SKU-FORMAT, qty=100, unitCost=12.50, total=1250.00)',
            $formattedString
        );
    }

    public function testFormatCostRecordStringWithSpecialCharacters(): void
    {
        $costRecord = $this->createCostRecord('SKU-特殊字符-123', 15.75, 25);

        $formattedString = $this->service->formatCostRecordString($costRecord);

        $this->assertEquals(
            'CostRecord(sku=SKU-特殊字符-123, qty=25, unitCost=15.75, total=393.75)',
            $formattedString
        );
    }

    public function testSyncFromStockBatchWithNullBatch(): void
    {
        $costRecord = $this->createCostRecord('SKU-SYNC', 10.00, 100);

        // StockBatch 为 null，不应该有任何变化
        $this->service->syncFromStockBatch($costRecord);

        $this->assertEquals('SKU-SYNC', $costRecord->getSkuId());
        $this->assertEquals(10.00, $costRecord->getUnitCost());
    }

    public function testSyncFromStockBatchSyncsBatchNoAndUnitCost(): void
    {
        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-ORIGINAL');
        $costRecord->setUnitCost(10.00);
        $costRecord->setQuantity(100);
        $costRecord->setTotalCost(1000.00);
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test_user');

        // 创建一个 StockBatch mock
        $stockBatch = $this->createMock(\Tourze\StockManageBundle\Entity\StockBatch::class);
        $stockBatch->method('getBatchNo')->willReturn('BATCH-123');
        $stockBatch->method('getUnitCost')->willReturn(15.50);
        $stockBatch->method('getSku')->willReturn(null);

        $costRecord->setStockBatch($stockBatch);

        $this->service->syncFromStockBatch($costRecord);

        $this->assertEquals('BATCH-123', $costRecord->getBatchNo());
        $this->assertEquals(15.50, $costRecord->getUnitCost());
        $this->assertEquals('SKU-ORIGINAL', $costRecord->getSkuId()); // SKU ID should remain unchanged
    }

    public function testSyncFromStockBatchSyncsSkuIdWhenNeeded(): void
    {
        // 测试 syncFromStockBatch 方法会同步 SKU ID 的逻辑
        // 由于验证器不允许空 SKU ID，我们测试已有 SKU ID 被 StockBatch 更新的场景
        $costRecord = $this->createCostRecord('SKU-TEMP', 10.00, 100);

        // 创建 SKU mock
        $sku = $this->createMock(\Tourze\ProductServiceContracts\SKU::class);
        $sku->method('getId')->willReturn('SKU-FROM-BATCH');

        // 创建 StockBatch mock
        $stockBatch = $this->createMock(\Tourze\StockManageBundle\Entity\StockBatch::class);
        $stockBatch->method('getBatchNo')->willReturn('BATCH-456');
        $stockBatch->method('getUnitCost')->willReturn(20.00);
        $stockBatch->method('getSku')->willReturn($sku);

        $costRecord->setStockBatch($stockBatch);

        $this->service->syncFromStockBatch($costRecord);

        $this->assertEquals('BATCH-456', $costRecord->getBatchNo());
        $this->assertEquals(20.00, $costRecord->getUnitCost());
        // SKU ID remains unchanged because it was not empty initially
        $this->assertEquals('SKU-TEMP', $costRecord->getSkuId());
    }

    private function createCostRecord(string $skuId, float $unitCost, int $quantity): CostRecord
    {
        $record = new CostRecord();
        $record->setSkuId($skuId);
        $record->setUnitCost($unitCost);
        $record->setQuantity($quantity);
        $record->setTotalCost($unitCost * $quantity);
        $record->setCostStrategy(CostStrategy::FIFO);
        $record->setCostType(CostType::DIRECT);
        $record->setOperator('test_user');
        $record->setRecordedAt(new \DateTimeImmutable());

        return $record;
    }
}
