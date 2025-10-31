<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\StockCostBundle\Entity\StockRecord;
use Tourze\StockCostBundle\Repository\StockRecordRepository;

/**
 * 库存记录服务
 *
 * 提供库存记录的查询和管理功能
 */
readonly class StockRecordService implements StockRecordServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StockRecordRepository $stockRecordRepository,
    ) {
    }

    /**
     * @return array<StockRecord>
     */
    public function getStockRecordsForSku(string $sku): array
    {
        return $this->stockRecordRepository->findBy(['sku' => $sku], ['recordDate' => 'ASC']);
    }

    public function getCurrentStock(string $sku): int
    {
        $result = $this->stockRecordRepository->createQueryBuilder('sr')
            ->select('SUM(sr.currentQuantity)')
            ->where('sr.sku = :sku')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    public function createStockRecord(
        string $sku,
        int $quantity,
        float $unitCost,
        \DateTimeImmutable $recordDate,
    ): StockRecord {
        $record = new StockRecord();
        $record->setSku($sku);
        $record->setCurrentQuantity($quantity);
        $record->setOriginalQuantity($quantity);
        $record->setUnitCost($unitCost);
        $record->setRecordDate($recordDate);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function updateStockQuantity(StockRecord $record, int $newQuantity): StockRecord
    {
        $actualQuantity = max(0, $newQuantity);
        $record->setCurrentQuantity($actualQuantity);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }
}
