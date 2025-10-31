<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * 价值分摊策略
 *
 * 根据商品价值进行成本分摊
 */
class ValueAllocationStrategy implements AllocationStrategyInterface
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

        // 计算总价值
        $values = array_column($targets, 'value');
        $totalValue = array_sum(array_map(function ($value): float {
            $validated = $this->validator->validateNumericValue($value);

            return $validated ?? 0.0;
        }, $values));

        if ($totalValue <= 0.0) {
            return [];
        }

        $allocations = [];

        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['value'])) {
                continue;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $value = $this->validator->validateNumericValue($target['value']);

            if (null === $skuId || null === $value) {
                continue;
            }

            try {
                $this->validator->validateValue($value);
                $ratio = $totalValue > 0.0 ? $value / $totalValue : 0.0;
                $allocations[$skuId] = $totalAmount * $ratio;
            } catch (\Exception) {
                // 跳过无效的价值值
                continue;
            }
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'value';
    }

    public function validateTargets(array $targets): bool
    {
        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['value'])) {
                return false;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $value = $this->validator->validateNumericValue($target['value']);

            if (null === $skuId || null === $value) {
                return false;
            }

            try {
                $this->validator->validateValue($value);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }
}
