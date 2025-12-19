<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\ConsistencyFixer;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * @internal
 */
#[CoversClass(ConsistencyFixer::class)]
#[RunTestsInSeparateProcesses]
class ConsistencyFixerTest extends AbstractIntegrationTestCase
{
    private ConsistencyFixer $fixer;

    private CostRecordRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getContainer()->get(CostRecordRepository::class);
        $this->fixer = self::getContainer()->get(ConsistencyFixer::class);
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
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
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
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
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
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
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
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
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
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
        // 不设置 StockBatch

        $result = $this->fixer->fixSingleRecordInconsistency($costRecord);

        $this->assertFalse($result); // 没有StockBatch，无法修复
    }

    public function testFixInconsistentRecords(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . CostRecord::class)->execute();
        $em->createQuery('DELETE FROM ' . StockBatch::class)->execute();

        $stockBatch1 = $this->createAndPersistStockBatch('B001', 10.50, 100);
        $stockBatch2 = $this->createAndPersistStockBatch('B002', 12.00, 50);

        // 创建有问题的记录（批次号不匹配，单位成本不匹配）
        $costRecord1 = new CostRecord();
        $costRecord1->setSkuId('SKU-001');
        $costRecord1->setBatchNo('B999'); // 批次号不匹配
        $costRecord1->setUnitCost(10.50);
        $costRecord1->setQuantity(30);
        $costRecord1->setTotalCost(315.00);
        $costRecord1->setCostStrategy(CostStrategy::FIFO);
        $costRecord1->setCostType(CostType::DIRECT);
        $costRecord1->setOperator('test');
        $costRecord1->setStockBatch($stockBatch1);

        $costRecord2 = new CostRecord();
        $costRecord2->setSkuId('SKU-001');
        $costRecord2->setBatchNo('B002');
        $costRecord2->setUnitCost(15.00); // 单位成本不匹配
        $costRecord2->setQuantity(20);
        $costRecord2->setTotalCost(300.00);
        $costRecord2->setCostStrategy(CostStrategy::FIFO);
        $costRecord2->setCostType(CostType::DIRECT);
        $costRecord2->setOperator('test');
        $costRecord2->setStockBatch($stockBatch2);

        // 持久化记录
        $this->repository->save($costRecord1);
        $this->repository->save($costRecord2);

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(2, $result['fixed']); // 修复了2条记录
        $this->assertEmpty($result['errors']);

        // 验证修复结果
        $this->assertEquals('B001', $costRecord1->getBatchNo()); // 批次号被修复
        $this->assertEquals(12.00, $costRecord2->getUnitCost()); // 单位成本被修复
        $this->assertEquals(240.00, $costRecord2->getTotalCost()); // 总成本被重新计算
    }

    public function testFixInconsistentRecordsWithNoIssues(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . CostRecord::class)->execute();
        $em->createQuery('DELETE FROM ' . StockBatch::class)->execute();

        $stockBatch = $this->createAndPersistStockBatch('B001', 10.50, 100);

        $costRecord = new CostRecord();
        $costRecord->setSkuId('SKU-001');
        $costRecord->setBatchNo('B001');
        $costRecord->setUnitCost(10.50);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(525.00);
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setOperator('test');
        $costRecord->setStockBatch($stockBatch);

        $this->repository->save($costRecord);

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(0, $result['fixed']); // 没有修复任何内容
        $this->assertEmpty($result['errors']);
    }

    public function testFixInconsistentRecordsWithEmptyList(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . CostRecord::class)->execute();

        $result = $this->fixer->fixInconsistentRecords();

        $this->assertEquals(0, $result['fixed']); // 没有记录需要修复
        $this->assertEmpty($result['errors']);
    }

    /**
     * 创建内存中的 StockBatch（不持久化，用于不需要数据库的测试）
     */
    private function createStockBatch(string $batchNo, string $skuId, float $unitCost, int $quantity): StockBatch
    {
        $sku = new class($skuId) implements SKU {
            public function __construct(private readonly string $id)
            {
            }

            public function getId(): string
            {
                return $this->id;
            }

            public function getGtin(): ?string
            {
                return null;
            }

            public function getMpn(): ?string
            {
                return null;
            }

            public function getRemark(): ?string
            {
                return null;
            }

            public function isValid(): ?bool
            {
                return true;
            }
        };

        $batch = new StockBatch();
        $batch->setBatchNo($batchNo);
        $batch->setSku($sku);
        $batch->setUnitCost($unitCost);
        $batch->setQuantity($quantity);

        return $batch;
    }

    /**
     * 创建并持久化 StockBatch（用于需要保存 CostRecord 的测试）
     * 注意：由于 SKU 是接口而非实体，我们不设置 SKU 关联
     */
    private function createAndPersistStockBatch(string $batchNo, float $unitCost, int $quantity): StockBatch
    {
        $batch = new StockBatch();
        $batch->setBatchNo($batchNo);
        // 不设置 SKU，因为 SKU 接口的匿名实现不是 Doctrine 实体
        // $batch->setSku(null) 是默认值
        $batch->setUnitCost($unitCost);
        $batch->setQuantity($quantity);

        $em = self::getEntityManager();
        $em->persist($batch);
        $em->flush();

        return $batch;
    }
}
