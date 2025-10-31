<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostRecord;

/**
 * 成本记录服务接口
 */
interface CostRecordServiceInterface
{
    /**
     * 获取指定SKU的成本记录
     *
     * @return array<CostRecord>
     */
    public function getCostRecordsForSku(string $skuId): array;

    /**
     * 创建成本记录
     */
    public function createCostRecord(CostRecord $costRecord): CostRecord;

    /**
     * 获取SKU的总成本
     */
    public function getTotalCostForSku(string $skuId, ?\DateTimeImmutable $asOfDate = null): float;

    /**
     * 获取SKU的平均成本
     */
    public function getAverageCostForSku(string $skuId): ?float;

    /**
     * 获取指定日期前最近的成本记录
     */
    public function getLatestCostRecord(string $skuId, \DateTimeImmutable $beforeDate): ?CostRecord;

    /**
     * 更新成本记录
     */
    public function updateCostRecord(CostRecord $costRecord): CostRecord;

    /**
     * 删除成本记录
     */
    public function deleteCostRecord(CostRecord $costRecord): void;

    /**
     * 获取指定时间段内的成本记录
     *
     * @return CostRecord[]
     */
    public function getCostRecordsByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;
}
