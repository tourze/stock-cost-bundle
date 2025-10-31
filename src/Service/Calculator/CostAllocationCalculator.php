<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Validator\CostAllocationValidator;

/**
 * 成本分摊计算器
 *
 * 负责根据不同的分摊方法计算成本分摊结果
 */
class CostAllocationCalculator
{
    /**
     * @var array<string, AllocationStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(
        private readonly CostAllocationValidator $validator,
        RatioAllocationStrategy $ratioStrategy,
        QuantityAllocationStrategy $quantityStrategy,
        ValueAllocationStrategy $valueStrategy,
        ActivityAllocationStrategy $activityStrategy,
    ) {
        $this->strategies = [
            'ratio' => $ratioStrategy,
            'quantity' => $quantityStrategy,
            'value' => $valueStrategy,
            'activity' => $activityStrategy,
        ];
    }

    /**
     * 计算成本分摊
     *
     * @param CostAllocation $allocation 成本分摊实体
     * @return array<string, float> SKU ID => 分摊金额
     * @throws CostAllocationException
     */
    public function calculate(CostAllocation $allocation): array
    {
        // 验证基础数据
        $this->validator->validate($allocation);

        $method = $allocation->getAllocationMethod();
        $strategy = $this->getStrategy($method);

        $targets = $allocation->getTargets();
        $totalAmount = $allocation->getTotalAmount();

        return $strategy->calculate($totalAmount, $targets);
    }

    /**
     * 计算成本分摊（直接参数方式）
     *
     * @param float $totalAmount 总金额
     * @param AllocationMethod $method 分摊方法
     * @param array<int, array<string, mixed>> $targets 分摊目标
     * @return array<string, float> SKU ID => 分摊金额
     * @throws CostAllocationException
     */
    public function calculateByParams(float $totalAmount, AllocationMethod $method, array $targets): array
    {
        // 验证基础数据
        $this->validator->validateTotalAmount($totalAmount);
        $targets = $this->validator->validateTargets($targets);

        $strategy = $this->getStrategy($method);

        return $strategy->calculate($totalAmount, $targets);
    }

    /**
     * 获取分摊策略
     *
     * @throws CostAllocationException
     */
    private function getStrategy(AllocationMethod $method): AllocationStrategyInterface
    {
        $strategyKey = $method->value;

        if (!isset($this->strategies[$strategyKey])) {
            throw CostAllocationException::unsupportedAllocationMethod($strategyKey);
        }

        return $this->strategies[$strategyKey];
    }

    /**
     * 注册新的分摊策略
     */
    public function registerStrategy(string $name, AllocationStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    /**
     * 获取所有可用策略
     *
     * @return array<string, AllocationStrategyInterface>
     */
    public function getAllStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * 验证分摊目标是否符合指定方法的要求
     * @param array<array<string, mixed>> $targets
     */
    public function validateTargetsForMethod(AllocationMethod $method, array $targets): bool
    {
        $strategy = $this->getStrategy($method);

        return $strategy->validateTargets($targets);
    }
}
