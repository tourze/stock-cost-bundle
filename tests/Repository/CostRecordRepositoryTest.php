<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Repository\CostRecordRepository;

/**
 * @internal
 */
#[CoversClass(CostRecordRepository::class)]
#[RunTestsInSeparateProcesses]
class CostRecordRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): CostRecord
    {
        $record = new CostRecord();
        $record->setSkuId('SKU-001');
        $record->setBatchNo('BATCH-001');
        $record->setUnitCost(15.50);
        $record->setQuantity(100);
        $record->setTotalCost(1550.00);
        $record->setCostStrategy(CostStrategy::FIFO);
        $record->setCostType(CostType::DIRECT);
        $record->setOperator('test_user');

        return $record;
    }

    protected function getRepository(): CostRecordRepository
    {
        $repository = self::getContainer()->get(CostRecordRepository::class);
        $this->assertInstanceOf(CostRecordRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    public function testSaveAndFind(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();
        $record = $this->createNewEntity();

        // 保存实体
        $repository->save($record);

        $this->assertNotNull($record->getId());

        // 通过ID查找实体
        $found = $repository->find($record->getId());

        $this->assertInstanceOf(CostRecord::class, $found);
        $this->assertEquals($record->getId(), $found->getId());
        $this->assertEquals('SKU-001', $found->getSkuId());
        $this->assertEquals('BATCH-001', $found->getBatchNo());
        $this->assertEquals(15.50, $found->getUnitCost());
        $this->assertEquals(100, $found->getQuantity());
        $this->assertEquals(1550.00, $found->getTotalCost());
    }

    public function testRemove(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();
        $record = $this->createNewEntity();

        // 先保存
        $repository->save($record);
        $id = $record->getId();

        // 确认已保存
        $found = $repository->find($id);
        $this->assertInstanceOf(CostRecord::class, $found);

        // 删除实体
        $repository->remove($record);

        // 确认已删除
        $notFound = $repository->find($id);
        $this->assertNull($notFound);
    }

    public function testGetAverageActualCost(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建多个成本记录
        $costs = [10.00, 15.00, 20.00];
        foreach ($costs as $index => $cost) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-AVG');
            $record->setBatchNo('BATCH-' . ($index + 1));
            $record->setUnitCost($cost);
            $record->setQuantity(100);
            $record->setTotalCost($cost * 100);
            $repository->save($record);
        }

        $avgCost = $repository->getAverageActualCost('SKU-AVG');

        $this->assertIsFloat($avgCost);
        $this->assertEquals(15.00, $avgCost); // (10 + 15 + 20) / 3 = 15
    }

    public function testGetAverageActualCostWithNonExistentSku(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $avgCost = $repository->getAverageActualCost('NON-EXISTENT-SKU');

        $this->assertNull($avgCost);
    }

    public function testGetCostHistoryForSku(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建不同日期的成本记录
        $dates = [
            '2024-01-01' => 10.00,
            '2024-01-02' => 12.00,
            '2024-01-03' => 15.00,
        ];

        foreach ($dates as $dateStr => $cost) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-HISTORY');
            $record->setUnitCost($cost);
            $record->setQuantity(100);
            $record->setTotalCost($cost * 100);

            // 设置记录时间
            $recordedAt = new \DateTimeImmutable($dateStr);
            $record->setRecordedAt($recordedAt);

            $repository->save($record);
        }

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-03');

        $history = $repository->getCostHistoryForSku('SKU-HISTORY', $startDate, $endDate);

        $this->assertIsArray($history);
        $this->assertCount(3, $history);

        foreach ($history as $entry) {
            $this->assertArrayHasKey('date', $entry);
            $this->assertArrayHasKey('avgCost', $entry);
            $this->assertIsString($entry['date']);
            $this->assertIsFloat($entry['avgCost']);
        }
    }

    public function testFindLatestCostRecord(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建不同时间的记录
        $dates = ['2024-01-01', '2024-01-05', '2024-01-10'];
        $records = [];

        foreach ($dates as $dateStr) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-LATEST');
            $record->setBatchNo('BATCH-' . $dateStr);
            $record->setRecordedAt(new \DateTimeImmutable($dateStr));
            $repository->save($record);
            $records[] = $record;
        }

        // 查找2024-01-08之前的最新记录（应该是2024-01-05的记录）
        $beforeDate = new \DateTimeImmutable('2024-01-08');
        $latestRecord = $repository->findLatestCostRecord('SKU-LATEST', $beforeDate);

        $this->assertInstanceOf(CostRecord::class, $latestRecord);
        $this->assertEquals('BATCH-2024-01-05', $latestRecord->getBatchNo());
    }

    public function testFindLatestCostRecordWithNoResults(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $beforeDate = new \DateTimeImmutable('2024-01-01');
        $latestRecord = $repository->findLatestCostRecord('NON-EXISTENT-SKU', $beforeDate);

        $this->assertNull($latestRecord);
    }

    public function testFindByDateRange(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建不同日期的记录
        $dates = ['2024-01-01', '2024-01-15', '2024-02-01'];

        foreach ($dates as $dateStr) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-RANGE');
            $record->setBatchNo('BATCH-' . $dateStr);
            $record->setRecordedAt(new \DateTimeImmutable($dateStr));
            $repository->save($record);
        }

        // 查找1月份的记录
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $records = $repository->findByDateRange($startDate, $endDate);

        $this->assertIsArray($records);
        $this->assertCount(2, $records); // 只有2条1月份的记录

        foreach ($records as $record) {
            $this->assertInstanceOf(CostRecord::class, $record);
            $this->assertStringStartsWith('2024-01', $record->getRecordedAt()->format('Y-m-d'));
        }
    }

    public function testGetTotalCostForSku(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建多个成本记录
        $costs = [100.00, 200.00, 300.00];

        foreach ($costs as $index => $totalCost) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-TOTAL');
            $record->setBatchNo('BATCH-' . ($index + 1));
            $record->setTotalCost($totalCost);
            $repository->save($record);
        }

        $totalCost = $repository->getTotalCostForSku('SKU-TOTAL');

        $this->assertIsFloat($totalCost);
        $this->assertEquals(600.00, $totalCost); // 100 + 200 + 300
    }

    public function testGetTotalCostForSkuWithAsOfDate(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建不同日期的成本记录
        $records = [
            ['date' => '2024-01-01', 'cost' => 100.00],
            ['date' => '2024-01-15', 'cost' => 200.00],
            ['date' => '2024-02-01', 'cost' => 300.00],
        ];

        foreach ($records as $index => $recordData) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-ASOF');
            $record->setBatchNo('BATCH-' . ($index + 1));
            $record->setTotalCost($recordData['cost']);
            $record->setRecordedAt(new \DateTimeImmutable($recordData['date']));
            $repository->save($record);
        }

        // 查找2024-01-20之前的总成本（应该只包括前两条记录）
        $asOfDate = new \DateTimeImmutable('2024-01-20');
        $totalCost = $repository->getTotalCostForSku('SKU-ASOF', $asOfDate);

        $this->assertIsFloat($totalCost);
        $this->assertEquals(300.00, $totalCost); // 100 + 200
    }

    public function testGetTotalCostForSkuWithNonExistentSku(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $totalCost = $repository->getTotalCostForSku('NON-EXISTENT-SKU');

        $this->assertEquals(0.0, $totalCost);
    }

    public function testRepositoryWithCostPeriod(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        // 创建成本期间
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period->setStatus(CostPeriodStatus::OPEN);
        $period->setDefaultStrategy(CostStrategy::FIFO);

        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        // 创建关联期间的成本记录
        $record = $this->createNewEntity();
        $record->setPeriod($period);
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertInstanceOf(CostRecord::class, $found);
        $foundPeriod = $found->getPeriod();
        $this->assertNotNull($foundPeriod);
        $this->assertSame($period->getId(), $foundPeriod->getId());
    }

    public function testRepositoryInheritsFromCorrectBaseClass(): void
    {
        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryWithDifferentCostTypes(): void
    {
        // 清理数据库中已存在的数据
        $query = self::getEntityManager()->createQuery('DELETE FROM ' . CostRecord::class);
        $query->execute();

        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $costTypes = CostType::cases();

        foreach ($costTypes as $costType) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-' . $costType->value);
            $record->setCostType($costType);
            $repository->save($record);
        }

        // 验证所有成本类型的记录都已保存
        foreach ($costTypes as $costType) {
            $records = $repository->findBy(['costType' => $costType]);
            $this->assertCount(1, $records);
            $this->assertEquals($costType, $records[0]->getCostType());
        }
    }

    public function testRepositoryWithDifferentCostStrategies(): void
    {
        // 清理数据库中已存在的数据
        $query = self::getEntityManager()->createQuery('DELETE FROM ' . CostRecord::class);
        $query->execute();

        /** @var CostRecordRepository $repository */
        $repository = $this->getRepository();

        $strategies = CostStrategy::cases();

        foreach ($strategies as $strategy) {
            $record = $this->createNewEntity();
            $record->setSkuId('SKU-' . $strategy->value);
            $record->setCostStrategy($strategy);
            $repository->save($record);
        }

        // 验证所有成本策略的记录都已保存
        foreach ($strategies as $strategy) {
            $records = $repository->findBy(['costStrategy' => $strategy]);
            $this->assertCount(1, $records);
            $this->assertEquals($strategy, $records[0]->getCostStrategy());
        }
    }
}
