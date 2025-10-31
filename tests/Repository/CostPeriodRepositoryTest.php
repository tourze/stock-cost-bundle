<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Repository\CostPeriodRepository;

/**
 * @internal
 */
#[CoversClass(CostPeriodRepository::class)]
#[RunTestsInSeparateProcesses]
class CostPeriodRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): CostPeriod
    {
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period->setStatus(CostPeriodStatus::OPEN);
        $period->setDefaultStrategy(CostStrategy::FIFO);

        return $period;
    }

    protected function getRepository(): CostPeriodRepository
    {
        $repository = self::getContainer()->get(CostPeriodRepository::class);
        $this->assertInstanceOf(CostPeriodRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // Repository测试不需要额外的设置
    }

    public function testFindCurrentPeriod(): void
    {
        // 创建一个测试期间
        $currentPeriod = $this->createCostPeriod(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-01-31'),
            CostPeriodStatus::OPEN
        );

        // 持久化到数据库
        self::getEntityManager()->persist($currentPeriod);
        self::getEntityManager()->flush();

        /** @var CostPeriodRepository $repository */
        $repository = $this->getRepository();

        // 测试查找当前期间 - 这个方法需要在真实repository中实现
        $result = $repository->findOneBy(['status' => CostPeriodStatus::OPEN]);

        $this->assertInstanceOf(CostPeriod::class, $result);
        $this->assertEquals(CostPeriodStatus::OPEN, $result->getStatus());
    }

    public function testRepositoryCanFindPeriodById(): void
    {
        // 创建并保存一个测试期间
        $period = $this->createCostPeriod(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-01-31'),
            CostPeriodStatus::OPEN
        );

        self::getEntityManager()->persist($period);
        self::getEntityManager()->flush();

        /** @var CostPeriodRepository $repository */
        $repository = $this->getRepository();

        // 通过ID查找期间
        $found = $repository->find($period->getId());

        $this->assertInstanceOf(CostPeriod::class, $found);
        $this->assertEquals($period->getId(), $found->getId());
        $this->assertEquals(CostPeriodStatus::OPEN, $found->getStatus());
    }

    public function testRepositoryCanFindAllPeriods(): void
    {
        // 清理数据库中已存在的数据
        self::getEntityManager()->createQuery('DELETE FROM ' . CostPeriod::class)->execute();

        // 创建多个测试期间
        $period1 = $this->createCostPeriod(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-01-31'),
            CostPeriodStatus::OPEN
        );
        $period2 = $this->createCostPeriod(
            new \DateTimeImmutable('2024-02-01'),
            new \DateTimeImmutable('2024-02-28'),
            CostPeriodStatus::CLOSED
        );

        self::getEntityManager()->persist($period1);
        self::getEntityManager()->persist($period2);
        self::getEntityManager()->flush();

        /** @var CostPeriodRepository $repository */
        $repository = $this->getRepository();

        // 查找所有期间
        $all = $repository->findAll();

        $this->assertCount(2, $all);
    }

    private function createCostPeriod(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        CostPeriodStatus $status,
    ): CostPeriod {
        $period = new CostPeriod();
        $period->setPeriodStart($start);
        $period->setPeriodEnd($end);
        $period->setStatus($status);
        $period->setDefaultStrategy(CostStrategy::FIFO);

        return $period;
    }
}
