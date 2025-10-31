<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;

/**
 * 成本计算策略接口
 *
 * 定义不同成本计算策略的通用接口，支持FIFO、LIFO、加权平均等
 */
interface CostStrategyCalculatorInterface
{
    /**
     * 计算指定SKU和数量的成本
     *
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     *
     * @return CostCalculationResult 计算结果
     */
    public function calculate(string $sku, int $quantity): CostCalculationResult;

    /**
     * 检查是否支持指定的计算策略
     *
     * @param CostStrategy $strategy 要检查的策略
     *
     * @return bool 是否支持该策略
     */
    public function supports(CostStrategy $strategy): bool;

    /**
     * 获取该计算器支持的策略
     *
     * @return CostStrategy 支持的策略
     */
    public function getSupportedStrategy(): CostStrategy;

    /**
     * 重新计算指定SKU的成本
     *
     * 当库存发生变动时，使用此方法重新计算成本
     *
     * @param array<string> $skus 要重新计算的SKU列表
     *
     * @return array<CostCalculationResult> 重新计算的结果
     */
    public function recalculate(array $skus): array;

    /**
     * 检查是否能够计算指定SKU和数量的成本
     *
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     *
     * @return bool 是否能够计算
     */
    public function canCalculate(string $sku, int $quantity): bool;
}
