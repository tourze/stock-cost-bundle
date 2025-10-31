<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * 活动基础分摊策略
 *
 * 根据活动单位进行成本分摊
 */
class ActivityAllocationStrategy implements AllocationStrategyInterface
{
    public function __construct(
        private readonly CostAllocationValidator $validator,
    ) {
    }

    public function calculate(float $totalAmount, array $targets): array
    {
        if (0 === count($targets)) {
            return [];
        }

        // 计算总活动单位
        $activityUnits = array_column($targets, 'activity_units');
        $totalActivity = array_sum(array_map(function ($value): float {
            $validated = $this->validator->validateNumericValue($value);

            return $validated ?? 0.0;
        }, $activityUnits));

        if ($totalActivity <= 0.0) {
            return [];
        }

        $allocations = [];

        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['activity_units'])) {
                continue;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $activityUnits = $this->validator->validateNumericValue($target['activity_units']);

            if (null === $skuId || null === $activityUnits) {
                continue;
            }

            try {
                $this->validator->validateActivityUnits($activityUnits);
                $ratio = $totalActivity > 0.0 ? $activityUnits / $totalActivity : 0.0;
                $allocations[$skuId] = $totalAmount * $ratio;
            } catch (\Exception) {
                // 跳过无效的活动单位值
                continue;
            }
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'activity';
    }

    public function validateTargets(array $targets): bool
    {
        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['activity_units'])) {
                return false;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $activityUnits = $this->validator->validateNumericValue($target['activity_units']);

            if (null === $skuId || null === $activityUnits) {
                return false;
            }

            try {
                $this->validator->validateActivityUnits($activityUnits);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }
}
