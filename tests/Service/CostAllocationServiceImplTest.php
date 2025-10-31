<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Repository\CostAllocationRepository;
use Tourze\StockCostBundle\Service\Calculator\CostAllocationCalculator;
use Tourze\StockCostBundle\Service\CostAllocationServiceImpl;
use Tourze\StockCostBundle\Service\CostAllocationServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostAllocationServiceImpl::class)]
class CostAllocationServiceImplTest extends TestCase
{
    private CostAllocationServiceImpl $service;

    private EntityManagerInterface $entityManager;

    private CostAllocationRepository $repository;

    private CostAllocationCalculator $calculator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(CostAllocationRepository::class);
        $this->calculator = $this->createMock(CostAllocationCalculator::class);

        $this->service = new CostAllocationServiceImpl(
            $this->entityManager,
            $this->repository,
            $this->calculator
        );
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

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(self::isInstanceOf(CostAllocation::class))
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

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

        $effectiveDate = new \DateTimeImmutable('2024-01-01');

        // 模拟 calculator 的 calculate 方法
        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($allocation)
            ->willReturn([
                'SKU-001' => 700.00,
                'SKU-002' => 300.00,
            ])
        ;

        // 期望创建两条成本记录
        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with(self::isInstanceOf(CostRecord::class))
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

        $records = $this->service->allocateCost(1000.00, $allocation, $effectiveDate);

        $this->assertCount(2, $records);
        $this->assertContainsOnlyInstancesOf(CostRecord::class, $records);

        // 验证分摊金额
        $this->assertEquals(700.00, $records[0]->getTotalCost());
        $this->assertEquals(300.00, $records[1]->getTotalCost());
    }

    public function testAllocateCostWithPeriod(): void
    {
        $period = new CostPeriod();
        $allocation = new CostAllocation();
        $allocation->setAllocationName('Test Allocation');
        $allocation->setSourceType(CostType::INDIRECT);
        $allocation->setTotalAmount(500.00);
        $allocation->setAllocationMethod(AllocationMethod::RATIO);
        $allocation->setPeriod($period);
        $allocation->setTargets([
            ['sku_id' => 'SKU-001', 'ratio' => 1.0],
        ]);

        // 模拟 calculator 的 calculate 方法
        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($allocation)
            ->willReturn(['SKU-001' => 500.00])
        ;

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with(self::callback(function (CostRecord $record) use ($period) {
                return $record->getPeriod() === $period;
            }))
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

        $records = $this->service->allocateCost(500.00, $allocation, new \DateTimeImmutable());

        $this->assertCount(1, $records);
        $this->assertSame($period, $records[0]->getPeriod());
    }

    public function testGetAllocatedCostForSku(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        // 模拟仓库返回分摊成本记录
        $this->repository->expects($this->once())
            ->method('findAllocatedCostForSku')
            ->with('SKU-001', $date)
            ->willReturn(250.00)
        ;

        $cost = $this->service->getAllocatedCost('SKU-001', $date);

        $this->assertEquals(250.00, $cost);
    }

    public function testGetAllocatedCostForSkuReturnsZeroWhenNotFound(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        $this->repository->expects($this->once())
            ->method('findAllocatedCostForSku')
            ->with('SKU-999', $date)
            ->willReturn(null)
        ;

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
            ['sku_id' => 'SKU-001', 'quantity' => 100],
            ['sku_id' => 'SKU-002', 'quantity' => 200],
        ]);

        // 模拟 calculator 的 calculate 方法
        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($allocation)
            ->willReturn([
                'SKU-001' => 200.00,
                'SKU-002' => 400.00,
            ])
        ;

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

        $records = $this->service->allocateCost(600.00, $allocation, new \DateTimeImmutable());

        $this->assertCount(2, $records);
        // SKU-001: 100/(100+200) * 600 = 200
        // SKU-002: 200/(100+200) * 600 = 400
        $this->assertEquals(200.00, $records[0]->getTotalCost());
        $this->assertEquals(400.00, $records[1]->getTotalCost());
    }

    public function testAllocateCostThrowsExceptionForInvalidAmount(): void
    {
        $allocation = new CostAllocation();
        $allocation->setTotalAmount(1000.00);

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

        // 模拟 calculator 的 calculate 方法抛出异常
        $this->calculator->expects($this->once())
            ->method('calculate')
            ->with($allocation)
            ->willThrowException(new CostAllocationException('Allocation targets cannot be empty'))
        ;

        $this->entityManager->expects($this->never())
            ->method('persist')
        ;

        $this->expectException(CostAllocationException::class);
        $this->expectExceptionMessage('Allocation targets cannot be empty');

        $this->service->allocateCost(1000.00, $allocation, new \DateTimeImmutable());
    }
}
