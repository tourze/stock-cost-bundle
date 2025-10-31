<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * 数量分摊策略
 *
 * 根据商品数量进行成本分摊
 */
class QuantityAllocationStrategy implements AllocationStrategyInterface
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

        // 计算总数量
        $quantities = array_column($targets, 'quantity');
        $totalQuantity = array_sum(array_map(function ($value): float {
            $validated = $this->validator->validateNumericValue($value);

            return $validated ?? 0.0;
        }, $quantities));

        if ($totalQuantity <= 0.0) {
            return [];
        }

        $allocations = [];

        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['quantity'])) {
                continue;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $quantity = $this->validator->validateNumericValue($target['quantity']);

            if (null === $skuId || null === $quantity) {
                continue;
            }

            try {
                $this->validator->validateQuantity($quantity);
                $ratio = $totalQuantity > 0.0 ? $quantity / $totalQuantity : 0.0;
                $allocations[$skuId] = $totalAmount * $ratio;
            } catch (\Exception) {
                // 跳过无效的数量值
                continue;
            }
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'quantity';
    }

    public function validateTargets(array $targets): bool
    {
        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['quantity'])) {
                return false;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $quantity = $this->validator->validateNumericValue($target['quantity']);

            if (null === $skuId || null === $quantity) {
                return false;
            }

            try {
                $this->validator->validateQuantity($quantity);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }
}
