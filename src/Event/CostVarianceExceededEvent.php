<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 成本差异超额事件
 *
 * 当实际成本与标准成本的差异超过设定阈值时触发
 */
class CostVarianceExceededEvent extends Event
{
    public function __construct(
        private readonly string $skuId,
        private readonly float $actualCost,
        private readonly float $standardCost,
        private readonly float $absoluteVariance,
        private readonly float $relativeVariance,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function getSkuId(): string
    {
        return $this->skuId;
    }

    public function getActualCost(): float
    {
        return $this->actualCost;
    }

    public function getStandardCost(): float
    {
        return $this->standardCost;
    }

    public function getAbsoluteVariance(): float
    {
        return $this->absoluteVariance;
    }

    public function getRelativeVariance(): float
    {
        return $this->relativeVariance;
    }

    public function getVariancePercentage(): float
    {
        return $this->relativeVariance * 100;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function isUnfavorableVariance(): bool
    {
        return $this->actualCost > $this->standardCost;
    }

    public function isFavorableVariance(): bool
    {
        return $this->actualCost < $this->standardCost;
    }

    /**
     * 转换为数组格式
     *
     * @return array{sku_id: string, actual_cost: float, standard_cost: float, absolute_variance: float, relative_variance: float, variance_percentage: float, is_unfavorable: bool, occurred_at: \DateTimeImmutable}
     */
    public function toArray(): array
    {
        return [
            'sku_id' => $this->skuId,
            'actual_cost' => $this->actualCost,
            'standard_cost' => $this->standardCost,
            'absolute_variance' => $this->absoluteVariance,
            'relative_variance' => $this->relativeVariance,
            'variance_percentage' => $this->getVariancePercentage(),
            'is_unfavorable' => $this->isUnfavorableVariance(),
            'occurred_at' => $this->occurredAt,
        ];
    }
}
