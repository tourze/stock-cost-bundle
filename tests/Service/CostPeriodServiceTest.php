<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
class CostPeriodServiceTest extends TestCase
{
    private CostPeriodService $service;

    private EntityManagerInterface $mockEntityManager;

    /** @var CostPeriodRepository&MockObject */
    private CostPeriodRepository $mockRepository;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockRepository = $this->createMock(CostPeriodRepository::class);

        $this->service = new CostPeriodService($this->mockEntityManager, $this->mockRepository);
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

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $period = $this->service->createPeriod($periodStart, $periodEnd, $strategy);

        $this->assertInstanceOf(CostPeriod::class, $period);
        $this->assertEquals($periodStart, $period->getPeriodStart());
        $this->assertEquals($periodEnd, $period->getPeriodEnd());
        $this->assertEquals($strategy, $period->getDefaultStrategy());
        $this->assertEquals(CostPeriodStatus::OPEN, $period->getStatus());
    }

    public function testCreatePeriodWithDefaultStrategy(): void
    {
        $periodStart = new \DateTimeImmutable('2024-02-01');
        $periodEnd = new \DateTimeImmutable('2024-02-29');

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $period = $this->service->createPeriod($periodStart, $periodEnd);

        $this->assertEquals(CostStrategy::FIFO, $period->getDefaultStrategy());
    }

    public function testClosePeriod(): void
    {
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period->setStatus(CostPeriodStatus::OPEN);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($period)
        ;

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->closePeriod($period);

        $this->assertEquals(CostPeriodStatus::CLOSED, $result->getStatus());
    }

    public function testCannotCloseNonOpenPeriod(): void
    {
        $period = new CostPeriod();
        $period->setStatus(CostPeriodStatus::CLOSED);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only OPEN periods can be closed');

        $this->service->closePeriod($period);
    }

    public function testFreezePeriod(): void
    {
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period->setStatus(CostPeriodStatus::CLOSED);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($period)
        ;

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->freezePeriod($period);

        $this->assertEquals(CostPeriodStatus::FROZEN, $result->getStatus());
    }

    public function testCannotFreezeOpenPeriod(): void
    {
        $period = new CostPeriod();
        $period->setStatus(CostPeriodStatus::OPEN);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only CLOSED periods can be frozen');

        $this->service->freezePeriod($period);
    }

    public function testUnfreezePeriod(): void
    {
        $period = new CostPeriod();
        $period->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period->setStatus(CostPeriodStatus::FROZEN);

        $this->mockEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($period)
        ;

        $this->mockEntityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->unfreezePeriod($period);

        $this->assertEquals(CostPeriodStatus::CLOSED, $result->getStatus());
    }

    public function testCannotUnfreezeNonFrozenPeriod(): void
    {
        $period = new CostPeriod();
        $period->setStatus(CostPeriodStatus::OPEN);

        $this->expectException(CostPeriodException::class);
        $this->expectExceptionMessage('Only FROZEN periods can be unfrozen');

        $this->service->unfreezePeriod($period);
    }

    public function testGetCurrentPeriod(): void
    {
        $currentDate = new \DateTimeImmutable();
        $period = new CostPeriod();

        $this->mockRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($period)
        ;

        $result = $this->service->getCurrentPeriod();

        $this->assertSame($period, $result);
    }

    public function testGetCurrentPeriodReturnsNull(): void
    {
        $this->mockRepository
            ->method('findOneBy')
            ->willReturn(null)
        ;

        $result = $this->service->getCurrentPeriod();

        $this->assertNull($result);
    }

    public function testFindPeriodByDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $period = new CostPeriod();

        $mockQuery = $this->createMock(Query::class);
        $mockQueryBuilder = $this->createMock(QueryBuilder::class);

        // Configure the repository to return the query builder
        $this->mockRepository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($mockQueryBuilder)
        ;

        // Configure the query builder
        $mockQueryBuilder
            ->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf()
        ;

        $mockQueryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('date', $date)
            ->willReturnSelf()
        ;

        $mockQueryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($mockQuery)
        ;

        // Configure the query to return the period
        $mockQuery
            ->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($period)
        ;

        $result = $this->service->findPeriodByDate($date);

        $this->assertSame($period, $result);
    }

    public function testCanClosePeriodReturnsTrueForOpenPeriod(): void
    {
        $period = new CostPeriod();
        $period->setStatus(CostPeriodStatus::OPEN);

        $this->assertTrue($this->service->canClosePeriod($period));
    }

    public function testCanClosePeriodReturnsFalseForNonOpenPeriod(): void
    {
        $period = new CostPeriod();
        $period->setStatus(CostPeriodStatus::CLOSED);

        $this->assertFalse($this->service->canClosePeriod($period));
    }
}
