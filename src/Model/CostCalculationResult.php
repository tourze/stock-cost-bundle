<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Model;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;

/**
 * 成本计算结果数据传输对象
 *
 * 封装成本计算的结果数据，包括SKU、数量、单价、总价、策略等信息
 */
readonly class CostCalculationResult implements \JsonSerializable
{
    public function __construct(
        private string $sku,
        private int $quantity,
        private float $unitCost,
        private float $totalCost,
        private CostStrategy $strategy,
        /** @var array<string, mixed> */
        private array $calculationDetails = [],
        private bool $isPartialCalculation = false,
    ) {
        $this->validateInput();
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitCost(): float
    {
        return $this->unitCost;
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function getStrategy(): CostStrategy
    {
        return $this->strategy;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalculationDetails(): array
    {
        return $this->calculationDetails;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unitCost' => $this->unitCost,
            'totalCost' => $this->totalCost,
            'strategy' => $this->strategy->value,
            'calculationDetails' => $this->calculationDetails,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['sku'], $data['quantity'], $data['unitCost'], $data['totalCost'], $data['strategy'])) {
            throw InvalidCostDataException::forMissingRequiredFields();
        }

        $calculationDetails = $data['calculationDetails'] ?? [];
        if (!is_array($calculationDetails)) {
            $calculationDetails = [];
        }

        // Ensure we have proper array<string, mixed> format
        $validatedDetails = [];
        foreach ($calculationDetails as $key => $value) {
            if (is_string($key)) {
                $validatedDetails[$key] = $value;
            }
        }
        $calculationDetails = $validatedDetails;

        $skuValue = $data['sku'];
        $quantityValue = $data['quantity'];
        $unitCostValue = $data['unitCost'];
        $totalCostValue = $data['totalCost'];
        $strategyValue = $data['strategy'];

        return new self(
            sku: is_string($skuValue) || is_numeric($skuValue) ? (string) $skuValue : '',
            quantity: is_numeric($quantityValue) ? (int) $quantityValue : 0,
            unitCost: is_numeric($unitCostValue) ? (float) $unitCostValue : 0.0,
            totalCost: is_numeric($totalCostValue) ? (float) $totalCostValue : 0.0,
            strategy: CostStrategy::from(is_string($strategyValue) ? $strategyValue : CostStrategy::FIFO->value),
            calculationDetails: $calculationDetails
        );
    }

    public function hasCalculationDetails(): bool
    {
        return [] !== $this->calculationDetails;
    }

    public function getFormattedUnitCost(int $precision = 2): string
    {
        return number_format($this->unitCost, $precision);
    }

    public function getFormattedTotalCost(int $precision = 2): string
    {
        return number_format($this->totalCost, $precision);
    }

    public function isPartialCalculation(): bool
    {
        return $this->isPartialCalculation;
    }

    private function validateInput(): void
    {
        if ('' === $this->sku) {
            throw InvalidCostDataException::forEmptySku();
        }

        if ($this->quantity <= 0) {
            throw InvalidCostDataException::forNegativeQuantity($this->quantity);
        }
    }
}
