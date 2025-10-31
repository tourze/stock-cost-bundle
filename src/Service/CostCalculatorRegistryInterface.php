<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * 成本计算器注册表接口
 *
 * 提供成本计算器的注册、查找和管理功能
 */
interface CostCalculatorRegistryInterface
{
    /**
     * 注册成本计算器
     *
     * @param CostStrategyCalculatorInterface $calculator 计算器实例
     */
    public function registerCalculator(CostStrategyCalculatorInterface $calculator): void;

    /**
     * 获取指定策略的计算器
     *
     * @param CostStrategy $strategy 成本策略
     *
     * @return CostStrategyCalculatorInterface 计算器实例
     *
     * @throws \RuntimeException 当没有找到对应的计算器时
     */
    public function getCalculatorForStrategy(CostStrategy $strategy): CostStrategyCalculatorInterface;

    /**
     * 检查是否有指定策略的计算器
     *
     * @param CostStrategy $strategy 成本策略
     *
     * @return bool 是否存在对应的计算器
     */
    public function hasCalculatorForStrategy(CostStrategy $strategy): bool;

    /**
     * 获取所有注册的计算器
     *
     * @return array<CostStrategyCalculatorInterface> 所有计算器实例
     */
    public function getAllCalculators(): array;

    /**
     * 获取所有支持的策略
     *
     * @return array<CostStrategy> 支持的策略列表
     */
    public function getSupportedStrategies(): array;

    /**
     * 注销指定策略的计算器
     *
     * @param CostStrategy $strategy 要注销的策略
     */
    public function unregisterCalculator(CostStrategy $strategy): void;

    /**
     * 清除所有注册的计算器
     */
    public function clearAllCalculators(): void;
}
