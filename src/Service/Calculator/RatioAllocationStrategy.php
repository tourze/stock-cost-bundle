<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * 比例分摊策略
 *
 * 根据预设的比例进行成本分摊
 */
class RatioAllocationStrategy implements AllocationStrategyInterface
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

        $allocations = [];

        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['ratio'])) {
                continue;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $ratio = $this->validator->validateNumericValue($target['ratio']);

            if (null === $skuId || null === $ratio) {
                continue;
            }

            try {
                $this->validator->validateRatio($ratio);
                $allocations[$skuId] = $totalAmount * $ratio;
            } catch (\Exception) {
                // 跳过无效的比例值
                continue;
            }
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'ratio';
    }

    public function validateTargets(array $targets): bool
    {
        foreach ($targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['ratio'])) {
                return false;
            }

            $skuId = $this->validator->validateSkuId($target['sku_id']);
            $ratio = $this->validator->validateNumericValue($target['ratio']);

            if (null === $skuId || null === $ratio) {
                return false;
            }

            try {
                $this->validator->validateRatio($ratio);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }
}
