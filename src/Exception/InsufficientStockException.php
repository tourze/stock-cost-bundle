<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

/**
 * 库存不足异常
 *
 * 当库存数量不足以满足成本计算需求时抛出
 */
class InsufficientStockException extends CostCalculationException
{
    public function __construct(string $message = 'Insufficient stock for cost calculation', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forQuantity(string $sku, int $requested, int $available): self
    {
        $message = "Insufficient stock for {$sku}: requested {$requested}, available {$available}";

        return new self($message);
    }
}
