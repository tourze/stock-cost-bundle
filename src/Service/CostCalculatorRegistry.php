<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\InvalidCostStrategyException;

/**
 * 成本计算器注册表
 *
 * 管理和查找成本计算器的中央注册表
 */
class CostCalculatorRegistry implements CostCalculatorRegistryInterface
{
    /**
     * @var array<string, CostStrategyCalculatorInterface> 策略到计算器的映射
     */
    private array $calculators = [];

    /**
     * @param array<CostStrategyCalculatorInterface> $calculators 初始计算器列表
     */
    public function __construct(array $calculators = [])
    {
        foreach ($calculators as $calculator) {
            $this->registerCalculator($calculator);
        }
    }

    public function registerCalculator(CostStrategyCalculatorInterface $calculator): void
    {
        $strategy = $calculator->getSupportedStrategy();
        $this->calculators[$strategy->value] = $calculator;
    }

    public function getCalculatorForStrategy(CostStrategy $strategy): CostStrategyCalculatorInterface
    {
        if (!$this->hasCalculatorForStrategy($strategy)) {
            throw InvalidCostStrategyException::forStrategy($strategy->value);
        }

        return $this->calculators[$strategy->value];
    }

    public function hasCalculatorForStrategy(CostStrategy $strategy): bool
    {
        return isset($this->calculators[$strategy->value]);
    }

    /**
     * @return array<CostStrategyCalculatorInterface>
     */
    public function getAllCalculators(): array
    {
        return array_values($this->calculators);
    }

    /**
     * @return array<CostStrategy>
     */
    public function getSupportedStrategies(): array
    {
        $strategies = [];
        foreach (array_keys($this->calculators) as $strategyValue) {
            $strategies[] = CostStrategy::from($strategyValue);
        }

        return $strategies;
    }

    public function unregisterCalculator(CostStrategy $strategy): void
    {
        unset($this->calculators[$strategy->value]);
    }

    public function clearAllCalculators(): void
    {
        $this->calculators = [];
    }
}
