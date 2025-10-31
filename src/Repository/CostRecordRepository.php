<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\StockCostBundle\Entity\CostRecord;

/**
 * @extends ServiceEntityRepository<CostRecord>
 */
#[AsRepository(entityClass: CostRecord::class)]
class CostRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CostRecord::class);
    }

    /**
     * 保存实体到数据库
     */
    public function save(CostRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(CostRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取SKU的平均实际成本
     */
    public function getAverageActualCost(string $skuId): ?float
    {
        $result = $this->createQueryBuilder('cr')
            ->select('AVG(cr.unitCost) as avgCost')
            ->where('cr.skuId = :skuId')
            ->setParameter('skuId', $skuId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null !== $result ? (float) $result : null;
    }

    /**
     * 获取SKU的成本历史数据
     *
     * @return array<int, array{date: string, avgCost: float}>
     */
    public function getCostHistoryForSku(string $skuId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->select('cr.recordedAt as date', 'AVG(cr.unitCost) as avgCost')
            ->where('cr.skuId = :skuId')
            ->andWhere('cr.recordedAt BETWEEN :startDate AND :endDate')
            ->groupBy('cr.recordedAt')
            ->orderBy('cr.recordedAt', 'ASC')
            ->setParameter('skuId', $skuId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
        ;

        /** @var array<int, array{date: \DateTimeImmutable, avgCost: string}> $results */
        $results = $qb->getQuery()->getResult();

        // 格式化结果中的日期字段
        return array_map(function (array $result): array {
            /** @var \DateTimeImmutable $date */
            $date = $result['date'];

            return [
                'date' => $date->format('Y-m-d'),
                'avgCost' => (float) $result['avgCost'],
            ];
        }, $results);
    }

    /**
     * 查找指定日期前最近的成本记录
     */
    public function findLatestCostRecord(string $skuId, \DateTimeImmutable $beforeDate): ?CostRecord
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.skuId = :skuId')
            ->andWhere('cr.recordedAt < :beforeDate')
            ->orderBy('cr.recordedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('skuId', $skuId)
            ->setParameter('beforeDate', $beforeDate)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 获取指定时间段内的所有成本记录
     *
     * @return CostRecord[]
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('cr')
            ->where('cr.recordedAt BETWEEN :startDate AND :endDate')
            ->orderBy('cr.recordedAt', 'DESC')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取SKU的总成本
     */
    public function getTotalCostForSku(string $skuId, ?\DateTimeImmutable $asOfDate = null): float
    {
        $qb = $this->createQueryBuilder('cr')
            ->select('SUM(cr.totalCost) as totalCost')
            ->where('cr.skuId = :skuId')
            ->setParameter('skuId', $skuId)
        ;

        if (null !== $asOfDate) {
            $qb->andWhere('cr.recordedAt <= :asOfDate')
                ->setParameter('asOfDate', $asOfDate)
            ;
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : 0.0;
    }
}
