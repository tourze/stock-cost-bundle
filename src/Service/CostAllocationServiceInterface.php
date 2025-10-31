<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostRecord;

/**
 * 成本分摊服务接口
 *
 * 基于需求FR2.2 - 成本分层管理和成本分摊功能
 */
interface CostAllocationServiceInterface
{
    /**
     * 创建成本分摊规则
     *
     * @param string $name 分摊规则名称
     * @param string $type 成本类型 (direct, indirect, manufacturing)
     * @param array<string, mixed> $criteria 分摊标准
     * @param array<int, array<string, mixed>> $targets 分摊目标
     *
     * @return CostAllocation 创建的分摊规则
     */
    public function createAllocationRule(
        string $name,
        string $type,
        array $criteria,
        array $targets,
    ): CostAllocation;

    /**
     * 执行成本分摊
     *
     * @param float $totalCost 要分摊的总成本
     * @param CostAllocation $rule 分摊规则
     * @param \DateTimeImmutable $effectiveDate 生效日期
     *
     * @return CostRecord[] 生成的成本记录
     */
    public function allocateCost(
        float $totalCost,
        CostAllocation $rule,
        \DateTimeImmutable $effectiveDate,
    ): array;

    /**
     * 获取SKU的分摊成本
     *
     * @param string $skuId SKU标识
     * @param \DateTimeImmutable $date 查询日期
     *
     * @return float 分摊成本总额
     */
    public function getAllocatedCost(string $skuId, \DateTimeImmutable $date): float;
}
