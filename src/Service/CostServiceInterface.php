<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;

/**
 * 成本计算服务接口
 *
 * 定义成本计算的核心方法，支持单个和批量计算
 */
interface CostServiceInterface
{
    /**
     * 计算单个SKU的成本
     *
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     * @param CostStrategy|null $strategy 成本计算策略，不提供时使用默认策略
     *
     * @return CostCalculationResult 成本计算结果
     */
    public function calculateCost(string $sku, int $quantity, ?CostStrategy $strategy = null): CostCalculationResult;

    /**
     * 批量计算多个SKU的成本
     *
     * @param array<array{sku: string, quantity: int}> $items 商品清单
     * @param CostStrategy|null $strategy 成本计算策略，不提供时使用默认策略
     *
     * @return array<CostCalculationResult> 成本计算结果列表
     */
    public function batchCalculateCost(array $items, ?CostStrategy $strategy = null): array;

    /**
     * 获取默认成本计算策略
     *
     * @return CostStrategy 默认策略
     */
    public function getDefaultStrategy(): CostStrategy;

    /**
     * 设置默认成本计算策略
     *
     * @param CostStrategy $strategy 要设置的策略
     */
    public function setDefaultStrategy(CostStrategy $strategy): void;
}
