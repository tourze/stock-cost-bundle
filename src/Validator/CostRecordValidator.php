<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Validator;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;

/**
 * 成本记录验证器
 *
 * 负责处理 CostRecord 实体的复杂验证逻辑
 */
class CostRecordValidator
{
    /**
     * 验证 SKU ID
     */
    public static function validateSkuId(string $skuId): void
    {
        if ('' === $skuId) {
            throw InvalidCostDataException::forEmptySkuId();
        }
    }

    /**
     * 验证并转换单位成本
     */
    public static function validateAndConvertUnitCost(float|string|null $unitCost): ?float
    {
        if (null === $unitCost || '' === $unitCost) {
            return null;
        }

        $cost = is_string($unitCost) ? (float) $unitCost : $unitCost;

        if ($cost < 0) {
            throw InvalidCostDataException::forNegativeUnitCost($cost);
        }

        return $cost;
    }

    /**
     * 验证并转换数量
     */
    public static function validateAndConvertQuantity(int|string|null $quantity): ?int
    {
        if (null === $quantity || '' === $quantity) {
            return null;
        }

        $qty = is_string($quantity) ? (int) $quantity : $quantity;

        if ($qty <= 0) {
            throw InvalidCostDataException::forNegativeQuantity($qty);
        }

        return $qty;
    }

    /**
     * 验证并转换总成本
     */
    public static function validateAndConvertTotalCost(float|string|null $totalCost): ?float
    {
        if (null === $totalCost || '' === $totalCost) {
            return null;
        }

        $cost = is_string($totalCost) ? (float) $totalCost : $totalCost;

        if ($cost < 0) {
            throw InvalidCostDataException::forNegativeTotalCost($cost);
        }

        return $cost;
    }

    /**
     * 验证成本策略
     */
    public static function validateCostStrategy(CostStrategy $strategy): void
    {
        // 这里可以添加更多的策略验证逻辑
        if (!in_array($strategy, CostStrategy::cases(), true)) {
            throw InvalidCostDataException::forInvalidCostStrategy($strategy);
        }
    }

    /**
     * 验证成本类型
     */
    public static function validateCostType(CostType $type): void
    {
        // 这里可以添加更多的类型验证逻辑
        if (!in_array($type, CostType::cases(), true)) {
            throw InvalidCostDataException::forInvalidCostType($type);
        }
    }

    /**
     * 验证并转换元数据
     *
     * @param array<string, mixed>|string|null $metadata
     * @return array<string, mixed>|null
     */
    public static function validateAndConvertMetadata(array|string|null $metadata): ?array
    {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : null;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return null;
    }

    /**
     * 验证成本数据的一致性
     */
    public static function validateCostConsistency(float $unitCost, int $quantity, float $totalCost): void
    {
        $calculatedTotal = $unitCost * $quantity;
        $difference = abs($calculatedTotal - $totalCost);
        $tolerance = 0.01; // 允许的误差范围

        if ($difference > $tolerance) {
            throw InvalidCostDataException::forInconsistentCost($unitCost, $quantity, $totalCost, $calculatedTotal);
        }
    }

    /**
     * 验证批次号
     */
    public static function validateBatchNo(?string $batchNo): void
    {
        if (null !== $batchNo && '' === $batchNo) {
            throw InvalidCostDataException::forEmptyBatchNo();
        }
    }

    /**
     * 验证操作员
     */
    public static function validateOperator(?string $operator): void
    {
        if (null !== $operator && '' === $operator) {
            throw InvalidCostDataException::forEmptyOperator();
        }
    }
}
