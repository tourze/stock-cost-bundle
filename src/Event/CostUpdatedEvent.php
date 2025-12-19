<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 成本更新事件
 *
 * 当商品成本发生变更时触发此事件
 */
final class CostUpdatedEvent extends Event
{
    private \DateTimeImmutable $occurredAt;

    public function __construct(
        private string $sku,
        private int $quantity,
        private float $newUnitCost,
        private float $newTotalCost,
        private float $oldUnitCost,
        private float $oldTotalCost,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getNewUnitCost(): float
    {
        return $this->newUnitCost;
    }

    public function getNewTotalCost(): float
    {
        return $this->newTotalCost;
    }

    public function getOldUnitCost(): float
    {
        return $this->oldUnitCost;
    }

    public function getOldTotalCost(): float
    {
        return $this->oldTotalCost;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getUnitCostDifference(): float
    {
        return round($this->newUnitCost - $this->oldUnitCost, 2);
    }

    public function getTotalCostDifference(): float
    {
        return round($this->newTotalCost - $this->oldTotalCost, 2);
    }

    public function isCostIncreased(): bool
    {
        return $this->getTotalCostDifference() > 0;
    }

    public function isCostDecreased(): bool
    {
        return $this->getTotalCostDifference() < 0;
    }

    /**
     * 将事件转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'newUnitCost' => $this->newUnitCost,
            'newTotalCost' => $this->newTotalCost,
            'oldUnitCost' => $this->oldUnitCost,
            'oldTotalCost' => $this->oldTotalCost,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
