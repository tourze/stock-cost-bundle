<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * 成本期间管理服务接口
 *
 * 定义会计期间的创建、关闭、冻结等状态管理操作
 */
interface CostPeriodServiceInterface
{
    /**
     * 创建新会计期间
     *
     * @param \DateTimeImmutable $periodStart 期间开始日期
     * @param \DateTimeImmutable $periodEnd 期间结束日期
     * @param CostStrategy|null $defaultStrategy 默认成本策略，不提供时使用系统默认
     *
     * @return CostPeriod 创建的期间实体
     */
    public function createPeriod(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        ?CostStrategy $defaultStrategy = null,
    ): CostPeriod;

    /**
     * 关闭会计期间
     *
     * 只有处于OPEN状态的期间才能关闭
     *
     * @param CostPeriod $period 要关闭的期间
     *
     * @return CostPeriod 关闭后的期间实体
     */
    public function closePeriod(CostPeriod $period): CostPeriod;

    /**
     * 冻结会计期间
     *
     * 冻结后的期间不能进行任何修改
     *
     * @param CostPeriod $period 要冻结的期间
     *
     * @return CostPeriod 冻结后的期间实体
     */
    public function freezePeriod(CostPeriod $period): CostPeriod;

    /**
     * 解冻会计期间
     *
     * 只有冻结状态的期间才能解冻
     *
     * @param CostPeriod $period 要解冻的期间
     *
     * @return CostPeriod 解冻后的期间实体
     */
    public function unfreezePeriod(CostPeriod $period): CostPeriod;

    /**
     * 获取当前期间
     *
     * 返回当前日期所在的期间，如果没有则返回null
     *
     * @return CostPeriod|null 当前期间，未找到时返回null
     */
    public function getCurrentPeriod(): ?CostPeriod;

    /**
     * 根据日期查找期间
     *
     * @param \DateTimeImmutable $date 要查找的日期
     *
     * @return CostPeriod|null 包含该日期的期间，未找到时返回null
     */
    public function findPeriodByDate(\DateTimeImmutable $date): ?CostPeriod;

    /**
     * 检查是否可以关闭期间
     *
     * 验证期间是否满足关闭条件（如无未完成的成本计算等）
     *
     * @param CostPeriod $period 要检查的期间
     *
     * @return bool 是否可以关闭
     */
    public function canClosePeriod(CostPeriod $period): bool;
}
