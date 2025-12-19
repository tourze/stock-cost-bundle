<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\CostPeriodException;
use Tourze\StockCostBundle\Repository\CostPeriodRepository;
use Tourze\StockCostBundle\Service\CostPeriodService;
use Tourze\StockCostBundle\Service\CostPeriodServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostPeriodService::class)]
#[RunTestsInSeparateProcesses]
class CostPeriodServiceTest extends AbstractIntegrationTestCase
{
    private CostPeriodService $service;

    private CostPeriodRepository $repository;

    protected function onSetUp(): void
    {
        $this->service = self::getService(CostPeriodService::class);
        $this->repository = self::getService(CostPeriodRepository::class);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(CostPeriodServiceInterface::class, $this->service);
    }

    public function testCreatePeriod(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $strategy = CostStrategy::FIFO;

        $period = $this->service->createPeriod($periodStart, $periodEnd, $strategy);

        $this->assertInstanceOf(CostPeriod::class, $period);
        $this->assertEquals($periodStart, $period->getPeriodStart());
        $this->assertEquals($periodEnd, $period->getPeriodEnd());
        $this->assertEquals($strategy, $period->getDefaultStrategy());
        $this->assertEquals(CostPeriodStatus::OPEN, $period->getStatus());
        $this->assertNotNull($period->getId());
    }

    public function testCreatePeriodWithDefaultStrategy(): void
    {
        $periodStart = new \DateTimeImmutable('2024-02-01');
        $periodEnd = new \DateTimeImmutable('2024-02-29');

        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $this->assertEquals(CostStrategy::FIFO, $period->getDefaultStrategy());
        $this->assertNotNull($period->getId());
    }

    public function testClosePeriod(): void
    {
        // 创建一个 OPEN 状态的 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $result = $this->service->closePeriod($period);

        $this->assertEquals(CostPeriodStatus::CLOSED, $result->getStatus());

        // 重新从数据库获取，验证状态已保存
        $savedPeriod = $this->repository->find($period->getId());
        $this->assertEquals(CostPeriodStatus::CLOSED, $savedPeriod->getStatus());
    }

    public function testCannotCloseNonOpenPeriod(): void
    {
        // 创建一个 period 并关闭它
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);
        $this->service->closePeriod($period);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only OPEN periods can be closed');

        // 尝试再次关闭应该失败
        $this->service->closePeriod($period);
    }

    public function testFreezePeriod(): void
    {
        // 创建并关闭一个 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);
        $this->service->closePeriod($period);

        $result = $this->service->freezePeriod($period);

        $this->assertEquals(CostPeriodStatus::FROZEN, $result->getStatus());

        // 重新从数据库获取，验证状态已保存
        $savedPeriod = $this->repository->find($period->getId());
        $this->assertEquals(CostPeriodStatus::FROZEN, $savedPeriod->getStatus());
    }

    public function testCannotFreezeOpenPeriod(): void
    {
        // 创建一个 OPEN 状态的 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only CLOSED periods can be frozen');

        $this->service->freezePeriod($period);
    }

    public function testUnfreezePeriod(): void
    {
        // 创建、关闭并冻结一个 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);
        $this->service->closePeriod($period);
        $this->service->freezePeriod($period);

        $result = $this->service->unfreezePeriod($period);

        $this->assertEquals(CostPeriodStatus::CLOSED, $result->getStatus());

        // 重新从数据库获取，验证状态已保存
        $savedPeriod = $this->repository->find($period->getId());
        $this->assertEquals(CostPeriodStatus::CLOSED, $savedPeriod->getStatus());
    }

    public function testCannotUnfreezeNonFrozenPeriod(): void
    {
        // 创建一个 OPEN 状态的 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only FROZEN periods can be unfrozen');

        $this->service->unfreezePeriod($period);
    }

    public function testFindPeriodByDate(): void
    {
        // 创建一个独特时间范围的 period
        $periodStart = new \DateTimeImmutable('2025-05-01');
        $periodEnd = new \DateTimeImmutable('2025-05-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);

        // 查找日期在 period 范围内的记录
        $dateInRange = new \DateTimeImmutable('2025-05-15');
        $result = $this->service->findPeriodByDate($dateInRange);

        $this->assertNotNull($result);
        $this->assertEquals($period->getId(), $result->getId());

        // 查找日期在 period 范围外的记录
        $dateOutOfRange = new \DateTimeImmutable('2025-06-15');
        $resultNotFound = $this->service->findPeriodByDate($dateOutOfRange);

        $this->assertNull($resultNotFound);
    }

    public function testCanClosePeriodReturnsTrueForOpenPeriod(): void
    {
        // 创建一个 OPEN 状态的 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $this->assertTrue($this->service->canClosePeriod($period));
    }

    public function testCanClosePeriodReturnsFalseForNonOpenPeriod(): void
    {
        // 创建并关闭一个 period
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');
        $period = $this->service->createPeriod($periodStart, $periodEnd);
        $this->service->closePeriod($period);

        $this->assertFalse($this->service->canClosePeriod($period));
    }
}
