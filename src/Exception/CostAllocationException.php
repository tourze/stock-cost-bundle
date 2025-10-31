<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

/**
 * 成本分摊异常
 *
 * 成本分摊操作中的错误异常
 */
class CostAllocationException extends CostCalculationException
{
    public static function invalidTotalAmount(float $amount): self
    {
        return new self(sprintf('Total amount must be positive, got %.2f', $amount));
    }

    public static function unsupportedAllocationMethod(string $method): self
    {
        return new self(sprintf('Unsupported allocation method: %s', $method));
    }

    public static function emptyTargets(): self
    {
        return new self('Allocation targets cannot be empty');
    }

    public static function invalidRatio(float $ratio): self
    {
        return new self(sprintf('Ratio must be between 0 and 1, got %.4f', $ratio));
    }

    public static function invalidQuantity(int $quantity): self
    {
        return new self(sprintf('Quantity must be positive, got %d', $quantity));
    }
}
