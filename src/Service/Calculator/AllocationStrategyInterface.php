<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

/**
 * 成本分摊策略接口
 *
 * 定义不同分摊方法的计算接口
 */
interface AllocationStrategyInterface
{
    /**
     * 计算分摊金额
     *
     * @param float $totalAmount 总金额
     * @param array<int, array<string, mixed>> $targets 分摊目标数组
     * @return array<string, float> SKU ID => 分摊金额
     */
    public function calculate(float $totalAmount, array $targets): array;

    /**
     * 获取策略名称
     */
    public function getName(): string;

    /**
     * 验证目标数据是否符合策略要求
     *
     * @param array<int, array<string, mixed>> $targets
     * @return bool
     */
    public function validateTargets(array $targets): bool;
}
