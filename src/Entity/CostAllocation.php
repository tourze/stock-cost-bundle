<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Repository\CostAllocationRepository;
use Tourze\StockCostBundle\Service\Calculator\ActivityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\CostAllocationCalculator;
use Tourze\StockCostBundle\Service\Calculator\QuantityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\RatioAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\ValueAllocationStrategy;
use Tourze\StockCostBundle\Validator\CostAllocationValidator;

#[ORM\Entity(repositoryClass: CostAllocationRepository::class)]
#[ORM\Table(name: 'cost_allocations', options: ['comment' => '成本分摊表'])]
class CostAllocation implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '分摊名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $allocationName = '';

    #[ORM\Column(type: Types::STRING, enumType: CostType::class, options: ['comment' => '源成本类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [CostType::class, 'cases'])]
    private CostType $sourceType;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '总金额'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private float $totalAmount = 0.0;

    #[ORM\Column(type: Types::STRING, enumType: AllocationMethod::class, options: ['comment' => '分摊方法'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AllocationMethod::class, 'cases'])]
    private AllocationMethod $allocationMethod;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '分摊日期'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $allocationDate;

    #[ORM\ManyToOne(targetEntity: CostPeriod::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CostPeriod $period = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '分摊目标配置'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'array')]
    private array $targets = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->allocationDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAllocationName(): string
    {
        return $this->allocationName;
    }

    public function setAllocationName(?string $allocationName): void
    {
        $this->allocationName = $allocationName ?? '';
    }

    public function getSourceType(): CostType
    {
        return $this->sourceType;
    }

    public function setSourceType(CostType $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?float $totalAmount): void
    {
        $totalAmount = $totalAmount ?? 0.0;
        if ($totalAmount < 0) {
            throw CostAllocationException::invalidTotalAmount($totalAmount);
        }
        $this->totalAmount = $totalAmount;
    }

    public function getAllocationMethod(): AllocationMethod
    {
        return $this->allocationMethod;
    }

    public function setAllocationMethod(AllocationMethod $allocationMethod): void
    {
        $this->allocationMethod = $allocationMethod;
    }

    public function getAllocationDate(): \DateTimeImmutable
    {
        return $this->allocationDate;
    }

    public function setAllocationDate(\DateTimeImmutable $allocationDate): void
    {
        $this->allocationDate = $allocationDate;
    }

    public function getPeriod(): ?CostPeriod
    {
        return $this->period;
    }

    public function setPeriod(?CostPeriod $period): void
    {
        $this->period = $period;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTargets(): array
    {
        return $this->targets;
    }

    /**
     * @param array<int, array<string, mixed>>|string $targets
     */
    public function setTargets(array|string $targets): void
    {
        if (is_string($targets)) {
            $decoded = json_decode($targets, true);
            if (is_array($decoded)) {
                /** @var array<int, array<string, mixed>> $decoded */
                $this->targets = $decoded;
            } else {
                $this->targets = [];
            }
        } else {
            $this->targets = $targets;
        }
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * 计算各目标的分摊金额（向后兼容方法）
     *
     * @deprecated 使用 CostAllocationCalculator 进行计算，此方法仅保持向后兼容
     * @return array<string, float> SKU ID => 分摊金额
     */
    public function calculateAllocations(): array
    {
        // 如果没有可用的计算器，则回退到原始的简单计算逻辑
        if (class_exists(CostAllocationCalculator::class)) {
            try {
                $validator = new CostAllocationValidator();
                $calculator = new CostAllocationCalculator(
                    $validator,
                    new RatioAllocationStrategy($validator),
                    new QuantityAllocationStrategy($validator),
                    new ValueAllocationStrategy($validator),
                    new ActivityAllocationStrategy($validator)
                );

                return $calculator->calculate($this);
            } catch (\Exception) {
                // 如果计算器不可用，回退到原始方法
            }
        }

        // 简单的回退实现（不包含完整的验证逻辑）
        return $this->calculateAllocationsFallback();
    }

    /**
     * 回退计算方法（简化版本，仅用于向后兼容）
     *
     * @return array<string, float>
     */
    private function calculateAllocationsFallback(): array
    {
        if (0 === count($this->targets)) {
            return [];
        }

        return match ($this->allocationMethod->value) {
            'ratio' => $this->calculateRatioFallback(),
            'quantity' => $this->calculateQuantityFallback(),
            'value' => $this->calculateValueFallback(),
            'activity' => $this->calculateActivityFallback(),
            default => [],
        };
    }

    /**
     * 比例分摊回退计算
     *
     * @return array<string, float>
     */
    private function calculateRatioFallback(): array
    {
        $allocations = [];
        foreach ($this->targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['ratio'])) {
                continue;
            }
            $skuId = (string) $target['sku_id'];
            $ratio = (float) $target['ratio'];
            if ($ratio >= 0.0 && $ratio <= 1.0) {
                $allocations[$skuId] = $this->totalAmount * $ratio;
            }
        }

        return $allocations;
    }

    /**
     * 数量分摊回退计算
     *
     * @return array<string, float>
     */
    private function calculateQuantityFallback(): array
    {
        $quantities = array_column($this->targets, 'quantity');
        $totalQuantity = array_sum(array_map(static fn ($value): float => is_numeric($value) ? (float) $value : 0.0, $quantities));

        if ($totalQuantity <= 0.0) {
            return [];
        }

        $allocations = [];
        foreach ($this->targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['quantity'])) {
                continue;
            }
            $skuId = (string) $target['sku_id'];
            $quantity = (float) $target['quantity'];
            if ($quantity >= 0.0) {
                $allocations[$skuId] = $this->totalAmount * ($quantity / $totalQuantity);
            }
        }

        return $allocations;
    }

    /**
     * 价值分摊回退计算
     *
     * @return array<string, float>
     */
    private function calculateValueFallback(): array
    {
        $values = array_column($this->targets, 'value');
        $totalValue = array_sum(array_map(static fn ($value): float => is_numeric($value) ? (float) $value : 0.0, $values));

        if ($totalValue <= 0.0) {
            return [];
        }

        $allocations = [];
        foreach ($this->targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['value'])) {
                continue;
            }
            $skuId = (string) $target['sku_id'];
            $value = (float) $target['value'];
            if ($value >= 0.0) {
                $allocations[$skuId] = $this->totalAmount * ($value / $totalValue);
            }
        }

        return $allocations;
    }

    /**
     * 活动基础分摊回退计算
     *
     * @return array<string, float>
     */
    private function calculateActivityFallback(): array
    {
        $activityUnits = array_column($this->targets, 'activity_units');
        $totalActivity = array_sum(array_map(static fn ($value): float => is_numeric($value) ? (float) $value : 0.0, $activityUnits));

        if ($totalActivity <= 0.0) {
            return [];
        }

        $allocations = [];
        foreach ($this->targets as $target) {
            if (!is_array($target) || !isset($target['sku_id'], $target['activity_units'])) {
                continue;
            }
            $skuId = (string) $target['sku_id'];
            $activityUnits = (float) $target['activity_units'];
            if ($activityUnits >= 0.0) {
                $allocations[$skuId] = $this->totalAmount * ($activityUnits / $totalActivity);
            }
        }

        return $allocations;
    }

    public function __toString(): string
    {
        return sprintf(
            'CostAllocation(%s, %.2f, %s)',
            $this->allocationName ?? 'Unnamed',
            $this->totalAmount ?? 0.0,
            $this->sourceType->value ?? 'N/A'
        );
    }
}
