<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockManageBundle\Entity\StockBatch;

/**
 * 库存批次一致性验证器
 *
 * 专门负责验证成本记录与库存批次之间的一致性
 */
class StockBatchConsistencyValidator
{
    private readonly CostRecordConsistencyValidator $costRecordValidator;

    public function __construct()
    {
        $this->costRecordValidator = new CostRecordConsistencyValidator();
    }

    /**
     * 校验单个成本记录与关联库存批次的一致性
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateCostRecordWithBatch(CostRecord $costRecord): array
    {
        $errors = [];

        // 检查与关联的StockBatch是否一致
        $stockBatch = $costRecord->getStockBatch();
        if (null !== $stockBatch) {
            $batchErrors = $this->validateBatchConsistency($costRecord, $stockBatch);
            $errors = array_merge($errors, $batchErrors);
        }

        // 成本计算一致性检查
        $totalConsistency = $this->costRecordValidator->validateCostTotalConsistency($costRecord);
        if (!$totalConsistency['isValid']) {
            $errors = array_merge($errors, $totalConsistency['errors']);
        }

        // 数量约束检查
        $quantityConsistency = $this->costRecordValidator->validateQuantityConstraints($costRecord);
        if (!$quantityConsistency['isValid']) {
            $errors = array_merge($errors, $quantityConsistency['errors']);
        }

        return [
            'isValid' => [] === $errors,
            'errors' => $errors,
        ];
    }

    /**
     * 验证批次一致性规则
     *
     * @param CostRecord $costRecord 成本记录
     * @param StockBatch $stockBatch 库存批次
     * @return array<string> 错误列表
     */
    private function validateBatchConsistency(CostRecord $costRecord, StockBatch $stockBatch): array
    {
        $errors = [];

        // SKU一致性检查
        $stockBatchSkuId = $stockBatch->getSku()?->getId();
        if ($costRecord->getSkuId() !== $stockBatchSkuId) {
            $errors[] = 'SKU mismatch between CostRecord and StockBatch';
        }

        // 批次号一致性检查
        if ($costRecord->getBatchNo() !== $stockBatch->getBatchNo()) {
            $errors[] = 'Batch number mismatch between CostRecord and StockBatch';
        }

        // 单位成本一致性检查（允许小的浮点精度差异）
        $costDiff = abs($costRecord->getUnitCost() - $stockBatch->getUnitCost());
        if ($costDiff > 0.001) {
            $errors[] = 'Unit cost mismatch between CostRecord and StockBatch';
        }

        // 数量约束检查
        if ($costRecord->getQuantity() > $stockBatch->getQuantity()) {
            $errors[] = 'CostRecord quantity exceeds StockBatch available quantity';
        }

        return $errors;
    }
}
