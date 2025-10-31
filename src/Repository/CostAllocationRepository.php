<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\StockCostBundle\Entity\CostAllocation;

/**
 * @extends ServiceEntityRepository<CostAllocation>
 */
#[AsRepository(entityClass: CostAllocation::class)]
class CostAllocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CostAllocation::class);
    }

    /**
     * 保存实体到数据库
     */
    public function save(CostAllocation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(CostAllocation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找指定SKU在指定日期的分摊成本总额
     */
    public function findAllocatedCostForSku(string $skuId, \DateTimeImmutable $date): ?float
    {
        $qb = $this->createQueryBuilder('ca');

        $qb->select('SUM(cr.totalCost) as totalAllocatedCost')
            ->leftJoin('Tourze\StockCostBundle\Entity\CostRecord', 'cr', 'WITH', 'cr.skuId = :skuId')
            ->where($qb->expr()->gte('ca.allocationDate', ':dateStart'))
            ->andWhere($qb->expr()->lt('ca.allocationDate', ':dateEnd'))
            ->andWhere($qb->expr()->eq('cr.costType', ':indirect'))
            ->setParameter('skuId', $skuId)
            ->setParameter('dateStart', $date->setTime(0, 0))
            ->setParameter('dateEnd', $date->setTime(0, 0)->modify('+1 day'))
            ->setParameter('indirect', 'INDIRECT')
        ;

        $result = $qb->getQuery()->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }
}
