<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * 成本记录服务
 *
 * 提供成本记录的查询和管理功能
 */
readonly class CostRecordService implements CostRecordServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CostRecordRepository $costRecordRepository,
    ) {
    }

    /**
     * @return array<CostRecord>
     */
    public function getCostRecordsForSku(string $skuId): array
    {
        return $this->costRecordRepository->findBy(['skuId' => $skuId], ['recordedAt' => 'ASC']);
    }

    public function createCostRecord(CostRecord $costRecord): CostRecord
    {
        $this->entityManager->persist($costRecord);
        $this->entityManager->flush();

        return $costRecord;
    }

    public function getTotalCostForSku(string $skuId, ?\DateTimeImmutable $asOfDate = null): float
    {
        return $this->costRecordRepository->getTotalCostForSku($skuId, $asOfDate);
    }

    public function getAverageCostForSku(string $skuId): ?float
    {
        return $this->costRecordRepository->getAverageActualCost($skuId);
    }

    public function getLatestCostRecord(string $skuId, \DateTimeImmutable $beforeDate): ?CostRecord
    {
        return $this->costRecordRepository->findLatestCostRecord($skuId, $beforeDate);
    }

    public function updateCostRecord(CostRecord $costRecord): CostRecord
    {
        $this->entityManager->flush();

        return $costRecord;
    }

    public function deleteCostRecord(CostRecord $costRecord): void
    {
        $this->entityManager->remove($costRecord);
        $this->entityManager->flush();
    }

    /**
     * @return CostRecord[]
     */
    public function getCostRecordsByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->costRecordRepository->findByDateRange($startDate, $endDate);
    }

    /**
     * 从 StockBatch 同步数据到 CostRecord
     */
    public function syncFromStockBatch(CostRecord $costRecord): void
    {
        $stockBatch = $costRecord->getStockBatch();
        if (null === $stockBatch) {
            return;
        }

        $this->syncBatchNo($costRecord, $stockBatch);
        $this->syncUnitCost($costRecord, $stockBatch);
        $this->syncSkuIdIfNeeded($costRecord, $stockBatch);
    }

    /**
     * 同步批次号
     */
    private function syncBatchNo(CostRecord $costRecord, StockBatch $stockBatch): void
    {
        $costRecord->setBatchNo($stockBatch->getBatchNo());
    }

    /**
     * 同步单位成本
     */
    private function syncUnitCost(CostRecord $costRecord, StockBatch $stockBatch): void
    {
        $costRecord->setUnitCost($stockBatch->getUnitCost());
    }

    /**
     * 如果需要，同步 SKU ID
     */
    private function syncSkuIdIfNeeded(CostRecord $costRecord, StockBatch $stockBatch): void
    {
        $currentSkuId = $costRecord->getSkuId();
        $stockSku = $stockBatch->getSku();

        // 如果当前 SKU ID 为空且 StockBatch 有 SKU，则同步
        if ('' === $currentSkuId && null !== $stockSku) {
            $costRecord->setSkuId($stockSku->getId());
        }
    }

    /**
     * 计算总成本
     */
    public function calculateTotalCost(CostRecord $costRecord): float
    {
        return $costRecord->getUnitCost() * $costRecord->getQuantity();
    }

    /**
     * 格式化成本记录的字符串表示
     */
    public function formatCostRecordString(CostRecord $costRecord): string
    {
        $skuId = $costRecord->getSkuId();

        return sprintf(
            'CostRecord(sku=%s, qty=%d, unitCost=%.2f, total=%.2f)',
            '' !== $skuId ? $skuId : 'N/A',
            $costRecord->getQuantity(),
            $costRecord->getUnitCost(),
            $costRecord->getTotalCost()
        );
    }
}
