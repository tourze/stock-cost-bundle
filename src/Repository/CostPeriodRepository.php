<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\StockCostBundle\Entity\CostPeriod;

/**
 * @extends ServiceEntityRepository<CostPeriod>
 */
#[AsRepository(entityClass: CostPeriod::class)]
final class CostPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CostPeriod::class);
    }

    /**
     * 保存实体到数据库
     */
    public function save(CostPeriod $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(CostPeriod $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
