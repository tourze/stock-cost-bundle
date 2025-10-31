<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Validator;

use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Exception\CostAllocationException;

/**
 * 成本分摊数据验证器
 *
 * 负责验证成本分摊相关的数据完整性和有效性
 */
class CostAllocationValidator
{
    /**
     * 验证成本分摊实体的有效性
     *
     * @throws CostAllocationException
     */
    public function validate(CostAllocation $allocation): void
    {
        $this->validateTotalAmount($allocation->getTotalAmount());
        $this->validateTargets($allocation->getTargets());
    }

    /**
     * 验证总金额
     *
     * @throws CostAllocationException
     */
    public function validateTotalAmount(float $amount): void
    {
        if ($amount < 0) {
            throw CostAllocationException::invalidTotalAmount($amount);
        }
    }

    /**
     * 验证分摊目标数据
     *
     * @param array<int, array<string, mixed>> $targets
     * @return array<int, array<string, mixed>> 验证后的有效目标数组
     * @throws CostAllocationException
     */
    public function validateTargets(array $targets): array
    {
        if (0 === count($targets)) {
            throw CostAllocationException::emptyTargets();
        }

        $validTargets = [];
        foreach ($targets as $index => $target) {
            if (is_array($target)) {
                $validTargets[$index] = $target;
            }
        }

        if (0 === count($validTargets)) {
            throw CostAllocationException::emptyTargets();
        }

        return $validTargets;
    }

    /**
     * 验证并过滤目标数组
     *
     * @param mixed $targets
     * @return array<int, array<string, mixed>>
     */
    public function validateTargetArray(mixed $targets): array
    {
        if (!is_array($targets)) {
            return [];
        }

        $validTargets = [];
        foreach ($targets as $index => $target) {
            if (is_array($target)) {
                $validTargets[$index] = $target;
            }
        }

        return $validTargets;
    }

    /**
     * 验证比例值的有效性
     *
     * @throws CostAllocationException
     */
    public function validateRatio(float $ratio): void
    {
        if ($ratio < 0.0 || $ratio > 1.0) {
            throw CostAllocationException::invalidRatio($ratio);
        }
    }

    /**
     * 验证数量值的有效性
     *
     * @throws CostAllocationException
     */
    public function validateQuantity(float $quantity): void
    {
        if ($quantity < 0) {
            throw CostAllocationException::invalidQuantity((int) $quantity);
        }
    }

    /**
     * 验证价值值的有效性
     *
     * @throws CostAllocationException
     */
    public function validateValue(float $value): void
    {
        if ($value < 0) {
            throw CostAllocationException::invalidQuantity((int) $value);
        }
    }

    /**
     * 验证活动单位值的有效性
     *
     * @throws CostAllocationException
     */
    public function validateActivityUnits(float $activityUnits): void
    {
        if ($activityUnits < 0) {
            throw CostAllocationException::invalidQuantity((int) $activityUnits);
        }
    }

    /**
     * 验证SKU ID的有效性
     */
    public function validateSkuId(mixed $skuId): ?string
    {
        if (is_string($skuId) && '' !== $skuId) {
            return $skuId;
        }

        if (is_numeric($skuId)) {
            return (string) $skuId;
        }

        return null;
    }

    /**
     * 验证数值的有效性
     */
    public function validateNumericValue(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
