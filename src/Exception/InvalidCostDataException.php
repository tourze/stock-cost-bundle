<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

class InvalidCostDataException extends CostCalculationException
{
    public static function forEmptySku(): self
    {
        return new self('SKU cannot be empty');
    }

    public static function forEmptySkuId(): self
    {
        return new self('SKU ID cannot be empty');
    }

    public static function forNegativeQuantity(int $quantity): self
    {
        return new self("Quantity must be positive, got: {$quantity}");
    }

    public static function forNegativeUnitCost(float $cost): self
    {
        return new self("Unit cost cannot be negative, got: {$cost}");
    }

    public static function forNegativeTotalCost(float $cost): self
    {
        return new self("Total cost cannot be negative, got: {$cost}");
    }

    public static function forInvalidPeriod(): self
    {
        return new self('Period start must be before period end');
    }

    public static function forInvalidCostStrategy(mixed $strategy): self
    {
        return new self('Invalid cost strategy provided');
    }

    public static function forInvalidCostType(mixed $type): self
    {
        return new self('Invalid cost type provided');
    }

    public static function forInconsistentCost(float $unitCost, int $quantity, float $totalCost, float $calculatedTotal): self
    {
        return new self(sprintf(
            'Cost calculation inconsistent: unitCost(%f) * quantity(%d) = %f, but totalCost is %f',
            $unitCost,
            $quantity,
            $calculatedTotal,
            $totalCost
        ));
    }

    public static function forEmptyBatchNo(): self
    {
        return new self('Batch number cannot be empty when provided');
    }

    public static function forEmptyOperator(): self
    {
        return new self('Operator cannot be empty when provided');
    }

    public static function forNegativeCost(float $cost): self
    {
        return new self("Total cost cannot be negative, got: {$cost}");
    }

    public static function forMissingRequiredFields(): self
    {
        return new self('Missing required fields: sku, quantity, unitCost, totalCost, strategy');
    }
}
