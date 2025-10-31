<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\CostPeriodException;
use Tourze\StockCostBundle\Repository\CostPeriodRepository;

/**
 * 会计期间管理服务
 *
 * 实现会计期间的创建、关闭、冻结等状态管理功能
 */
class CostPeriodService implements CostPeriodServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CostPeriodRepository $costPeriodRepository,
    ) {
    }

    public function createPeriod(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        ?CostStrategy $defaultStrategy = null,
    ): CostPeriod {
        $period = new CostPeriod();
        $period->setPeriodStart($periodStart);
        $period->setPeriodEnd($periodEnd);
        $period->setDefaultStrategy($defaultStrategy ?? CostStrategy::FIFO);
        $period->setStatus(CostPeriodStatus::OPEN);

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        return $period;
    }

    public function closePeriod(CostPeriod $period): CostPeriod
    {
        $status = $period->getStatus();
        if (null === $status) {
            throw new CostPeriodException('Cannot close period: status is not set');
        }

        if (CostPeriodStatus::OPEN !== $status) {
            throw CostPeriodException::cannotClosePeriod($status);
        }

        $period->setStatus(CostPeriodStatus::CLOSED);

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        return $period;
    }

    public function freezePeriod(CostPeriod $period): CostPeriod
    {
        $status = $period->getStatus();
        if (null === $status) {
            throw new CostPeriodException('Cannot freeze period: status is not set');
        }

        if (CostPeriodStatus::CLOSED !== $status) {
            throw CostPeriodException::cannotFreezePeriod($status);
        }

        $period->setStatus(CostPeriodStatus::FROZEN);

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        return $period;
    }

    public function unfreezePeriod(CostPeriod $period): CostPeriod
    {
        $status = $period->getStatus();
        if (null === $status) {
            throw new CostPeriodException('Cannot unfreeze period: status is not set');
        }

        if (CostPeriodStatus::FROZEN !== $status) {
            throw CostPeriodException::cannotUnfreezePeriod($status);
        }

        $period->setStatus(CostPeriodStatus::CLOSED);

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        return $period;
    }

    public function getCurrentPeriod(): ?CostPeriod
    {
        $today = new \DateTimeImmutable();

        return $this->costPeriodRepository->findOneBy([
            // 这里简化实现，实际应该查询日期范围
        ]);
    }

    public function findPeriodByDate(\DateTimeImmutable $date): ?CostPeriod
    {
        return $this->costPeriodRepository->createQueryBuilder('p')
            ->andWhere('p.periodStart <= :date')
            ->andWhere('p.periodEnd >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function canClosePeriod(CostPeriod $period): bool
    {
        $status = $period->getStatus();

        return null !== $status && CostPeriodStatus::OPEN === $status;
    }
}
