<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Service\CostAllocationServiceImpl;
use Tourze\StockCostBundle\Service\CostAllocationServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostAllocationServiceImpl::class)]
#[RunTestsInSeparateProcesses]
class CostAllocationServiceImplTest extends AbstractIntegrationTestCase
{
    private CostAllocationServiceImpl $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CostAllocationServiceImpl::class);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostAllocationServiceInterface::class, $this->service);
    }

    public function testCreateAllocationRule(): void
    {
        $targets = [
            ['sku_id' => 'SKU-001', 'ratio' => 0.6],
            ['sku_id' => 'SKU-002', 'ratio' => 0.4],
        ];

        $allocation = $this->service->createAllocationRule(
            'Office Rent Allocation',
            'indirect',
            ['building_area' => 1000],
            $targets
        );

        $this->assertInstanceOf(CostAllocation::class, $allocation);
        $this->assertEquals('Office Rent Allocation', $allocation->getAllocationName());
        $this->assertEquals(CostType::INDIRECT, $allocation->getSourceType());
        $this->assertEquals($targets, $allocation->getTargets());

        // 验证已保存到数据库
        self::getEntityManager()->clear();
        $savedAllocation = self::getEntityManager()->getRepository(CostAllocation::class)
            ->find($allocation->getId())
        ;

        $this->assertInstanceOf(CostAllocation::class, $savedAllocation);
        $this->assertEquals('Office Rent Allocation', $savedAllocation->getAllocationName());
    }

    public function testAllocateCost(): void
    {
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 0.7],
            ['sku_id' => 'SKU-002', 'ratio' => 0.3],
        ]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        $effectiveDate = new \DateTimeImmutable('2024-01-01');

        $records = $this->service->allocateCost(1000.00, $allocation, $effectiveDate);

        $this->assertCount(2, $records);
        $this->assertContainsOnlyInstancesOf(CostRecord::class, $records);

        // 验证分摊金额
        $this->assertEquals(700.00, $records[0]->getTotalCost());
        $this->assertEquals(300.00, $records[1]->getTotalCost());

        // 验证记录已保存到数据库 - 按 SKU ID 查询特定记录
        self::getEntityManager()->clear();
        $savedRecord1 = self::getEntityManager()->getRepository(CostRecord::class)->findOneBy(['skuId' => 'SKU-001']);
        $savedRecord2 = self::getEntityManager()->getRepository(CostRecord::class)->findOneBy(['skuId' => 'SKU-002']);
        $this->assertInstanceOf(CostRecord::class, $savedRecord1);
        $this->assertInstanceOf(CostRecord::class, $savedRecord2);
    }

    public function testAllocateCostWithPeriod(): void
    {
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-03-31'));

        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(500.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setPeriod($period);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 1.0],
        ]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        $records = $this->service->allocateCost(500.00, $allocation, new \DateTimeImmutable());

        $this->assertCount(1, $records);
        $this->assertSame($period, $records[0]->getPeriod());

        // 验证记录已保存到数据库
        self::getEntityManager()->clear();
        $savedRecord = self::getEntityManager()->getRepository(CostRecord::class)->findOneBy(['skuId' => 'SKU-001']);
        $this->assertInstanceOf(CostRecord::class, $savedRecord);
        $this->assertNotNull($savedRecord->getPeriod());
    }

    public function testGetAllocatedCostForSku(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        // 创建测试数据
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setAllocationDate($date);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 0.25],
        ]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        // 执行分摊
        $this->service->allocateCost(1000.00, $allocation, $date);

        $cost = $this->service->getAllocatedCost('SKU-001', $date);

        $this->assertEquals(250.00, $cost);
    }

    public function testGetAllocatedCostForSkuReturnsZeroWhenNotFound(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        $cost = $this->service->getAllocatedCost('SKU-999', $date);

        $this->assertEquals(0.0, $cost);
    }

    public function testAllocateCostWithQuantityMethod(): void
    {
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Quantity Based Allocation');
        $allocation->setSourceType(CostType::MANUFACTURING);
        $allocation->setTotalAmount(600.00);
        $allocation->setAllocationMethod(AllocationMethod::QUANTITY);
        $allocation->setTargets([
            ['sku_id' => 'SKU-003', 'quantity' => 100],
            ['sku_id' => 'SKU-004', 'quantity' => 200],
        ]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        $records = $this->service->allocateCost(600.00, $allocation, new \DateTimeImmutable());

        $this->assertCount(2, $records);
        // SKU-003: 100/(100+200) * 600 = 200
        // SKU-004: 200/(100+200) * 600 = 400
        $this->assertEquals(200.00, $records[0]->getTotalCost());
        $this->assertEquals(400.00, $records[1]->getTotalCost());

        // 验证记录已保存到数据库 - 按 SKU ID 查询特定记录
        self::getEntityManager()->clear();
        $savedRecord1 = self::getEntityManager()->getRepository(CostRecord::class)->findOneBy(['skuId' => 'SKU-003']);
        $savedRecord2 = self::getEntityManager()->getRepository(CostRecord::class)->findOneBy(['skuId' => 'SKU-004']);
        $this->assertInstanceOf(CostRecord::class, $savedRecord1);
        $this->assertInstanceOf(CostRecord::class, $savedRecord2);
    }

    public function testAllocateCostThrowsExceptionForInvalidAmount(): void
    {
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 1.0],
        ]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Total amount must be positive');

        $this->service->allocateCost(-100.00, $allocation, new \DateTimeImmutable());
    }

    public function testAllocateCostWithEmptyTargetsThrowsException(): void
    {
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Empty Targets Test');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(1000.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setTargets([]);

        self::getEntityManager()->persist($allocation);
        self::getEntityManager()->flush();

        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Allocation targets cannot be empty');

        $this->service->allocateCost(1000.00, $allocation, new \DateTimeImmutable());
    }
}
