<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Exception;

/**
 * 无效成本策略异常
 *
 * 当提供的成本计算策略无效时抛出
 */
class InvalidCostStrategyException extends CostCalculationException
{
    public function __construct(string $message = 'Invalid cost calculation strategy', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forStrategy(string $strategy): self
    {
        $message = "Invalid cost calculation strategy: {$strategy}";

        return new self($message);
    }

    /**
     * @param string[] $allowedStrategies
     */
    public static function forStrategyWithAllowed(string $strategy, array $allowedStrategies): self
    {
        $allowed = implode(', ', $allowedStrategies);
        $message = "Invalid cost calculation strategy: {$strategy}. Allowed strategies: {$allowed}";

        return new self($message);
    }
}
