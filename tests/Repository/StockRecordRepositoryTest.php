<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Repository\StockRecordRepository;

/**
 * @internal
 */
#[CoversClass(StockRecordRepository::class)]
#[RunTestsInSeparateProcesses]
class StockRecordRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): StockRecord
    {
        $record = new StockRecord();
        $record->setSku('SKU-001');
        $record->setRecordDate(new \DateTimeImmutable('2024-01-15'));
        $record->setOriginalQuantity(100);
        $record->setCurrentQuantity(80);
        $record->setUnitCost(15.50);

        return $record;
    }

    protected function getRepository(): StockRecordRepository
    {
        $repository = self::getContainer()->get(StockRecordRepository::class);
        $this->assertInstanceOf(StockRecordRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    public function testSaveAndFind(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();
        $record = $this->createNewEntity();

        // 保存实体
        $repository->save($record);

        $this->assertNotNull($record->getId());

        // 通过ID查找实体
        $found = $repository->find($record->getId());

        $this->assertInstanceOf(StockRecord::class, $found);
        $this->assertEquals($record->getId(), $found->getId());
        $this->assertEquals('SKU-001', $found->getSku());
        $this->assertEquals(100, $found->getOriginalQuantity());
        $this->assertEquals(80, $found->getCurrentQuantity());
        $this->assertEquals(15.50, $found->getUnitCost());
        $this->assertEquals(1240.00, $found->getTotalCost()); // 80 * 15.50
    }

    public function testRemove(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();
        $record = $this->createNewEntity();

        // 先保存
        $repository->save($record);
        $id = $record->getId();

        // 确认已保存
        $found = $repository->find($id);
        $this->assertInstanceOf(StockRecord::class, $found);

        // 删除实体
        $repository->remove($record);

        // 确认已删除
        $notFound = $repository->find($id);
        $this->assertNull($notFound);
    }

    public function testRepositoryInheritsFromCorrectBaseClass(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $this->assertInstanceOf(ServiceEntityRepository::class, $repository);
    }

    public function testRepositoryWithDifferentSkus(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $skus = ['SKU-001', 'SKU-002', 'SKU-003'];
        $savedRecords = [];

        foreach ($skus as $sku) {
            $record = $this->createNewEntity();
            $record->setSku($sku);
            $repository->save($record);
            $savedRecords[] = $record;
        }

        // 验证所有记录都已保存
        $this->assertCount(count($skus), $savedRecords);

        foreach ($savedRecords as $record) {
            $found = $repository->find($record->getId());
            $this->assertInstanceOf(StockRecord::class, $found);
            $this->assertContains($found->getSku(), $skus);
        }
    }

    public function testRepositoryWithDifferentDates(): void
    {
        // 清理数据库中已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . StockRecord::class)->execute();

        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-06-15'),
            new \DateTimeImmutable('2024-12-31'),
        ];

        foreach ($dates as $index => $date) {
            $record = $this->createNewEntity();
            $record->setSku('SKU-DATE-' . ($index + 1));
            $record->setRecordDate($date);
            $repository->save($record);
        }

        // 验证所有记录都已保存
        $allRecords = $repository->findAll();
        $this->assertCount(3, $allRecords);

        // 验证日期设置正确
        foreach ($allRecords as $record) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $record->getRecordDate());
            $this->assertContains($record->getRecordDate()->format('Y-m-d'), [
                '2024-01-01',
                '2024-06-15',
                '2024-12-31',
            ]);
        }
    }

    public function testRepositoryWithDifferentQuantities(): void
    {
        // 清理数据库中已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . StockRecord::class)->execute();

        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $quantities = [
            ['original' => 100, 'current' => 80],
            ['original' => 200, 'current' => 150],
            ['original' => 50, 'current' => 0],
        ];

        foreach ($quantities as $index => $quantity) {
            $record = $this->createNewEntity();
            $record->setSku('SKU-QTY-' . ($index + 1));
            $record->setOriginalQuantity($quantity['original']);
            $record->setCurrentQuantity($quantity['current']);
            $repository->save($record);
        }

        // 验证所有记录都已保存并且数量正确
        $allRecords = $repository->findAll();
        $this->assertCount(3, $allRecords);

        foreach ($allRecords as $record) {
            $this->assertGreaterThanOrEqual(0, $record->getCurrentQuantity());
            $this->assertGreaterThanOrEqual($record->getCurrentQuantity(), $record->getOriginalQuantity());
        }
    }

    public function testRepositoryWithDifferentCosts(): void
    {
        // 清理数据库中已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . StockRecord::class)->execute();

        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $costs = [10.50, 25.75, 5.00];

        foreach ($costs as $index => $unitCost) {
            $record = $this->createNewEntity();
            $record->setSku('SKU-COST-' . ($index + 1));
            $record->setUnitCost($unitCost);
            $record->setCurrentQuantity(100);
            $repository->save($record);
        }

        // 验证所有记录都已保存并且成本计算正确
        $allRecords = $repository->findAll();
        $this->assertCount(3, $allRecords);

        foreach ($allRecords as $record) {
            $expectedTotalCost = $record->getCurrentQuantity() * $record->getUnitCost();
            $this->assertEquals($expectedTotalCost, $record->getTotalCost());
        }
    }

    public function testRepositoryWithZeroQuantity(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $record = $this->createNewEntity();
        $record->setCurrentQuantity(0);
        $record->setUnitCost(15.50);
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertInstanceOf(StockRecord::class, $found);
        $this->assertEquals(0, $found->getCurrentQuantity());
        $this->assertEquals(0.00, $found->getTotalCost());
        $this->assertFalse($found->isAvailable());
    }

    public function testRepositoryWithHighQuantities(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $record = $this->createNewEntity();
        $record->setOriginalQuantity(1000000);
        $record->setCurrentQuantity(999999);
        $record->setUnitCost(0.01);
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertInstanceOf(StockRecord::class, $found);
        $this->assertEquals(1000000, $found->getOriginalQuantity());
        $this->assertEquals(999999, $found->getCurrentQuantity());
        $this->assertEquals(9999.99, $found->getTotalCost());
        $this->assertTrue($found->isAvailable());
    }

    public function testRepositoryWithPreciseDecimals(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $record = $this->createNewEntity();
        $record->setCurrentQuantity(333);
        $record->setUnitCost(10.333); // 会产生精确的小数
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertInstanceOf(StockRecord::class, $found);
        $this->assertEquals(333, $found->getCurrentQuantity());
        $this->assertEquals(10.333, $found->getUnitCost());
        $this->assertEquals(3440.889, $found->getTotalCost()); // 333 * 10.333
    }

    public function testRepositoryStringableOutput(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $record = $this->createNewEntity();
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertNotNull($found);
        $string = $found->__toString();

        $this->assertIsString($string);
        $this->assertStringContainsString('SKU-001', $string);
        $this->assertStringContainsString('2024-01-15', $string);
        $this->assertStringContainsString('80', $string);
        $this->assertStringContainsString('15.50', $string);
    }

    public function testRepositoryWithComplexSku(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $complexSkus = [
            'SKU-COMPLEX-001-ABC',
            'SKU-WITH-SPECIAL-CHARS-@#$',
            'SKU_WITH_UNDERSCORES_123',
            'SKU.WITH.DOTS.456',
        ];

        foreach ($complexSkus as $sku) {
            $record = $this->createNewEntity();
            $record->setSku($sku);
            $repository->save($record);

            $found = $repository->find($record->getId());
            $this->assertNotNull($found);
            $this->assertEquals($sku, $found->getSku());
        }
    }

    public function testRepositoryMultipleRecordsForSameSku(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $sameSku = 'SKU-MULTI';
        $recordCount = 5;

        for ($i = 0; $i < $recordCount; ++$i) {
            $record = $this->createNewEntity();
            $record->setSku($sameSku);
            $record->setRecordDate(new \DateTimeImmutable('2024-01-' . ($i + 1)));
            $record->setCurrentQuantity(100 - ($i * 10));
            $repository->save($record);
        }

        // 验证所有记录都已保存
        $records = $repository->findBy(['sku' => $sameSku]);
        $this->assertCount($recordCount, $records);

        // 验证每个记录的SKU都相同
        foreach ($records as $record) {
            $this->assertEquals($sameSku, $record->getSku());
        }
    }

    public function testRepositoryTimestampsAreSet(): void
    {
        /** @var StockRecordRepository $repository */
        $repository = $this->getRepository();

        $record = $this->createNewEntity();
        $repository->save($record);

        $found = $repository->find($record->getId());
        $this->assertInstanceOf(StockRecord::class, $found);
        $this->assertInstanceOf(\DateTimeImmutable::class, $found->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $found->getUpdatedAt());

        // 创建时间和更新时间应该是相近的时间
        $timeDiff = $found->getUpdatedAt()->getTimestamp() - $found->getCreatedAt()->getTimestamp();
        $this->assertLessThanOrEqual(1, abs($timeDiff)); // 差异应该小于1秒
    }
}
