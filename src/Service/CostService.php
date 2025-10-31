<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\InvalidCostStrategyException;
use Tourze\StockCostBundle\Model\CostCalculationResult;

/**
 * 成本计算核心服务
 *
 * 实现成本计算的核心逻辑，协调不同的策略计算器
 */
class CostService implements CostServiceInterface
{
    private CostStrategy $defaultStrategy = CostStrategy::FIFO;

    /**
     * @param array<CostStrategyCalculatorInterface> $calculators
     */
    public function __construct(
        private array $calculators,
    ) {
    }

    public function calculateCost(string $sku, int $quantity, ?CostStrategy $strategy = null): CostCalculationResult
    {
        $strategy ??= $this->defaultStrategy;

        $calculator = $this->findCalculatorForStrategy($strategy);
        if (null === $calculator) {
            throw InvalidCostStrategyException::forStrategy($strategy->value);
        }

        return $calculator->calculate($sku, $quantity);
    }

    public function batchCalculateCost(array $items, ?CostStrategy $strategy = null): array
    {
        $results = [];

        foreach ($items as $item) {
            $sku = $item['sku'];
            $quantity = $item['quantity'];

            $results[] = $this->calculateCost($sku, $quantity, $strategy);
        }

        return $results;
    }

    public function getDefaultStrategy(): CostStrategy
    {
        return $this->defaultStrategy;
    }

    public function setDefaultStrategy(CostStrategy $strategy): void
    {
        $this->defaultStrategy = $strategy;
    }

    private function findCalculatorForStrategy(CostStrategy $strategy): ?CostStrategyCalculatorInterface
    {
        foreach ($this->calculators as $calculator) {
            if ($calculator->supports($strategy)) {
                return $calculator;
            }
        }

        return null;
    }
}
