<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockManageBundle\Entity\StockBatch;
use Tourze\StockManageBundle\Service\BatchQueryServiceInterface;

/**
 * 一致性修复工具
 *
 * 专门负责修复数据不一致问题
 */
class ConsistencyFixer
{
    private readonly CostRecordConsistencyValidator $costRecordValidator;

    public function __construct(
        private readonly CostRecordRepository $costRecordRepository,
    ) {
        $this->costRecordValidator = new CostRecordConsistencyValidator();
    }

    /**
     * 修复单个记录的不一致问题
     */
    public function fixSingleRecordInconsistency(CostRecord $costRecord): bool
    {
        $stockBatch = $costRecord->getStockBatch();
        if (null === $stockBatch) {
            return false;
        }

        $skuFixed = $this->fixSkuMismatch($costRecord, $stockBatch);
        $batchFixed = $this->fixBatchNoMismatch($costRecord, $stockBatch);
        $costFixed = $this->fixUnitCostMismatch($costRecord, $stockBatch);

        $fixed = $skuFixed || $batchFixed || $costFixed;

        if ($fixed) {
            $this->costRecordValidator->recalculateTotalCost($costRecord);
        }

        return $fixed;
    }

    /**
     * 修复SKU不匹配
     */
    private function fixSkuMismatch(CostRecord $costRecord, StockBatch $stockBatch): bool
    {
        $stockBatchSkuId = $stockBatch->getSku()?->getId();
        if (null !== $stockBatchSkuId && $costRecord->getSkuId() !== $stockBatchSkuId) {
            $costRecord->setSkuId($stockBatchSkuId);

            return true;
        }

        return false;
    }

    /**
     * 修复批次号不匹配
     */
    private function fixBatchNoMismatch(CostRecord $costRecord, StockBatch $stockBatch): bool
    {
        if ($costRecord->getBatchNo() !== $stockBatch->getBatchNo()) {
            $costRecord->setBatchNo($stockBatch->getBatchNo());

            return true;
        }

        return false;
    }

    /**
     * 修复单位成本不匹配
     */
    private function fixUnitCostMismatch(CostRecord $costRecord, StockBatch $stockBatch): bool
    {
        $costDiff = abs($costRecord->getUnitCost() - $stockBatch->getUnitCost());
        if ($costDiff > 0.001) {
            $costRecord->setUnitCost($stockBatch->getUnitCost());

            return true;
        }

        return false;
    }

    /**
     * 修复不一致的记录
     *
     * @return array{fixed: int, errors: array<string>}
     */
    public function fixInconsistentRecords(): array
    {
        $costRecords = $this->costRecordRepository->findAll();
        $fixedCount = 0;
        $errors = [];

        foreach ($costRecords as $costRecord) {
            if ($this->fixSingleRecordInconsistency($costRecord)) {
                ++$fixedCount;
            }
        }

        return [
            'fixed' => $fixedCount,
            'errors' => $errors,
        ];
    }
}
