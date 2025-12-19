<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * @extends ServiceEntityRepository<StockRecord>
 */
#[AsRepository(entityClass: StockRecord::class)]
final class StockRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockRecord::class);
    }

    /**
     * 保存实体到数据库
     */
    public function save(StockRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 从数据库删除实体
     */
    public function remove(StockRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
