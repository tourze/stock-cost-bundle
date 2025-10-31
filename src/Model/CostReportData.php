<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Model;

use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;

/**
 * 成本报告数据传输对象
 *
 * 封装成本报告的数据，包括期间、总成本、分类成本等信息
 */
readonly class CostReportData implements \JsonSerializable
{
    public function __construct(
        private \DateTimeImmutable $periodStart,
        private \DateTimeImmutable $periodEnd,
        private float $totalCost,
        /** @var array<string, float> */
        private array $costByType = [],
        /** @var array<string, array{quantity: int, unitCost: float, totalCost: float}> */
        private array $skuDetails = [],
    ) {
        $this->validateInput();
    }

    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function getCostByType(CostType $type): float
    {
        return $this->costByType[$type->value] ?? 0.0;
    }

    /**
     * @return array<string, float>
     */
    public function getAllCostByType(): array
    {
        return $this->costByType;
    }

    /**
     * @return array<string, array{quantity: int, unitCost: float, totalCost: float}>
     */
    public function getSkuDetails(): array
    {
        return $this->skuDetails;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'periodStart' => $this->periodStart->format(\DateTimeInterface::ATOM),
            'periodEnd' => $this->periodEnd->format(\DateTimeInterface::ATOM),
            'totalCost' => $this->totalCost,
            'costByType' => $this->costByType,
            'skuDetails' => $this->skuDetails,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            periodStart: new \DateTimeImmutable($data['periodStart']),
            periodEnd: new \DateTimeImmutable($data['periodEnd']),
            totalCost: $data['totalCost'],
            costByType: $data['costByType'] ?? [],
            skuDetails: $data['skuDetails'] ?? []
        );
    }

    public function getPeriodDays(): int
    {
        $days = $this->periodEnd->diff($this->periodStart)->days;

        return false !== $days ? $days : 0;
    }

    public function getFormattedPeriod(): string
    {
        return sprintf(
            '%s - %s',
            $this->periodStart->format('Y-m-d'),
            $this->periodEnd->format('Y-m-d')
        );
    }

    public function getFormattedTotalCost(int $precision = 2): string
    {
        return number_format($this->totalCost, $precision);
    }

    public function getCostTypePercentage(CostType $type): float
    {
        if (0.0 === $this->totalCost) {
            return 0.0;
        }

        return round(($this->getCostByType($type) / $this->totalCost) * 100, 2);
    }

    private function validateInput(): void
    {
        if ($this->periodStart >= $this->periodEnd) {
            throw InvalidCostDataException::forInvalidPeriod();
        }

        if ($this->totalCost < 0) {
            throw InvalidCostDataException::forNegativeCost($this->totalCost);
        }
    }
}
