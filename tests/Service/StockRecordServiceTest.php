<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Repository\StockRecordRepository;
use Tourze\StockCostBundle\Service\StockRecordService;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * @internal
 */
#[CoversClass(StockRecordService::class)]
#[RunTestsInSeparateProcesses]
class StockRecordServiceTest extends AbstractIntegrationTestCase
{
    private StockRecordService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(StockRecordService::class);
        // 直接使用 self::getEntityManager() 和 self::getEntityManager()->getRepository()
        // 无需存储为属性，因为从未被读取
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(StockRecordServiceInterface::class, $this->service);
    }

    public function testGetStockRecordsForSku(): void
    {
        // 创建测试数据
        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00);
        $record2 = $this->createStockRecord('2024-01-02', 50, 15.00);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $result = $this->service->getStockRecordsForSku('TEST-SKU');

        $this->assertCount(2, $result);
        $this->assertEquals(100, $result[0]->getCurrentQuantity());
        $this->assertEquals(50, $result[1]->getCurrentQuantity());
    }

    public function testGetCurrentStock(): void
    {
        // 创建测试数据
        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00);
        $record2 = $this->createStockRecord('2024-01-02', 50, 15.00);

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->flush();

        $result = $this->service->getCurrentStock('TEST-SKU');

        $this->assertEquals(150, $result); // 100 + 50
    }

    public function testGetCurrentStockWithNoRecords(): void
    {
        $result = $this->service->getCurrentStock('NON-EXISTENT-SKU');

        $this->assertEquals(0, $result);
    }

    public function testCreateStockRecord(): void
    {
        $recordDate = new \DateTimeImmutable('2024-01-01');

        $result = $this->service->createStockRecord('SKU-NEW', 100, 15.50, $recordDate);

        $this->assertInstanceOf(StockRecord::class, $result);
        $this->assertEquals('SKU-NEW', $result->getSku());
        $this->assertEquals(100, $result->getCurrentQuantity());
        $this->assertEquals(100, $result->getOriginalQuantity());
        $this->assertEquals(15.50, $result->getUnitCost());
        $this->assertEquals($recordDate, $result->getRecordDate());

        // 验证记录已保存到数据库
        self::getEntityManager()->clear();
        $savedRecord = self::getEntityManager()->getRepository(StockRecord::class)
            ->findOneBy(['sku' => 'SKU-NEW'])
        ;

        $this->assertInstanceOf(StockRecord::class, $savedRecord);
        $this->assertEquals('SKU-NEW', $savedRecord->getSku());
    }

    public function testUpdateStockQuantity(): void
    {
        $record = $this->createStockRecord('2024-01-01', 100, 15.50);
        $record->setOriginalQuantity(100);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        $result = $this->service->updateStockQuantity($record, 75);

        $this->assertSame($record, $result);
        $this->assertEquals(75, $result->getCurrentQuantity());

        // 验证更新已保存到数据库
        self::getEntityManager()->clear();
        $updatedRecord = self::getEntityManager()->getRepository(StockRecord::class)
            ->find($record->getId())
        ;

        $this->assertInstanceOf(StockRecord::class, $updatedRecord);
        $this->assertEquals(75, $updatedRecord->getCurrentQuantity());
    }

    public function testUpdateStockQuantityToZero(): void
    {
        $record = $this->createStockRecord('2024-01-01', 50, 20.00);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        $result = $this->service->updateStockQuantity($record, 0);

        $this->assertEquals(0, $result->getCurrentQuantity());
    }

    public function testUpdateStockQuantityWithNegativeValue(): void
    {
        $record = $this->createStockRecord('2024-01-01', 30, 25.00);

        self::getEntityManager()->persist($record);
        self::getEntityManager()->flush();

        $result = $this->service->updateStockQuantity($record, -10);

        $this->assertEquals(0, $result->getCurrentQuantity());
    }

    public function testGetStockRecordsForSkuWithNoRecords(): void
    {
        $result = $this->service->getStockRecordsForSku('NON-EXISTENT');

        $this->assertEmpty($result);
    }

    public function testGetCurrentStockWithMultipleSkus(): void
    {
        // 创建不同SKU的记录
        $record1 = $this->createStockRecord('2024-01-01', 100, 10.00);
        $record2 = $this->createStockRecord('2024-01-02', 50, 15.00);
        $record3 = $this->createStockRecord('2024-01-03', 75, 12.00);

        // 设置不同的SKU
        $record1->setSku('SKU-A');
        $record2->setSku('SKU-A');
        $record3->setSku('SKU-B');

        self::getEntityManager()->persist($record1);
        self::getEntityManager()->persist($record2);
        self::getEntityManager()->persist($record3);
        self::getEntityManager()->flush();

        $stockA = $this->service->getCurrentStock('SKU-A');
        $stockB = $this->service->getCurrentStock('SKU-B');

        $this->assertEquals(150, $stockA); // 100 + 50
        $this->assertEquals(75, $stockB);  // Only record3
    }

    private function createStockRecord(string $date, int $quantity, float $unitCost): StockRecord
    {
        $record = new StockRecord();
        $record->setSku('TEST-SKU');
        $record->setRecordDate(new \DateTimeImmutable($date));
        $record->setCurrentQuantity($quantity);
        $record->setUnitCost($unitCost);
        $record->setOriginalQuantity($quantity);

        return $record;
    }
}
