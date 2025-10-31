<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostRecord;

/**
 * 成本记录一致性验证器
 *
 * 专门负责验证成本记录的各种一致性规则
 */
class CostRecordConsistencyValidator
{
    /**
     * 校验成本总计的一致性
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateCostTotalConsistency(CostRecord $costRecord): array
    {
        $expectedTotal = $costRecord->getUnitCost() * $costRecord->getQuantity();
        $actualTotal = $costRecord->getTotalCost();

        // 允许小的浮点精度差异
        $diff = abs($expectedTotal - $actualTotal);
        if ($diff > 0.001) {
            return [
                'isValid' => false,
                'errors' => ['Total cost calculation mismatch: expected ' . $expectedTotal . ', got ' . $actualTotal],
            ];
        }

        return [
            'isValid' => true,
            'errors' => [],
        ];
    }

    /**
     * 校验数量约束
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateQuantityConstraints(CostRecord $costRecord): array
    {
        $errors = [];

        if ($costRecord->getQuantity() <= 0) {
            $errors[] = 'Quantity must be positive';
        }

        return [
            'isValid' => [] === $errors,
            'errors' => $errors,
        ];
    }

    /**
     * 重新计算总成本
     */
    public function recalculateTotalCost(CostRecord $costRecord): void
    {
        $costRecord->setTotalCost($costRecord->getUnitCost() * $costRecord->getQuantity());
    }
}
