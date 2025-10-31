<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Validator\CostRecordValidator;
use Tourze\StockManageBundle\Entity\StockBatch;

#[ORM\Entity(repositoryClass: CostRecordRepository::class)]
#[ORM\Table(name: 'cost_records', options: ['comment' => '成本记录表'])]
class CostRecord implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => 'SKU标识'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $skuId = '';

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '批次号'])]
    #[Assert\Length(max: 100)]
    private ?string $batchNo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '单位成本'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private float $unitCost = 0.0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '数量'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    private int $quantity = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '总成本'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private float $totalCost = 0.0;

    #[ORM\Column(type: Types::STRING, enumType: CostStrategy::class, options: ['comment' => '成本策略'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [CostStrategy::class, 'cases'])]
    private CostStrategy $costStrategy = CostStrategy::WEIGHTED_AVERAGE;

    #[ORM\Column(type: Types::STRING, enumType: CostType::class, options: ['comment' => '成本类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [CostType::class, 'cases'])]
    private CostType $costType = CostType::DIRECT;

    #[ORM\ManyToOne(targetEntity: CostPeriod::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?CostPeriod $period = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '操作员'])]
    #[Assert\Length(max: 50)]
    private ?string $operator = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '记录时间'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: StockBatch::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?StockBatch $stockBatch = null;

    public function __construct()
    {
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkuId(): string
    {
        return $this->skuId;
    }

    public function setSkuId(string $skuId): void
    {
        CostRecordValidator::validateSkuId($skuId);
        $this->skuId = $skuId;
    }

    public function getBatchNo(): ?string
    {
        return $this->batchNo;
    }

    public function setBatchNo(?string $batchNo): void
    {
        CostRecordValidator::validateBatchNo($batchNo);
        $this->batchNo = $batchNo;
    }

    public function getUnitCost(): float
    {
        return $this->unitCost;
    }

    public function setUnitCost(float|string|null $unitCost): void
    {
        $validatedCost = CostRecordValidator::validateAndConvertUnitCost($unitCost);
        if (null !== $validatedCost) {
            $this->unitCost = $validatedCost;
        }
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int|string|null $quantity): void
    {
        $validatedQuantity = CostRecordValidator::validateAndConvertQuantity($quantity);
        if (null !== $validatedQuantity) {
            $this->quantity = $validatedQuantity;
        }
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function setTotalCost(float|string|null $totalCost): void
    {
        $validatedCost = CostRecordValidator::validateAndConvertTotalCost($totalCost);
        if (null !== $validatedCost) {
            $this->totalCost = $validatedCost;
        }
    }

    public function calculateTotalCost(): float
    {
        return $this->unitCost * $this->quantity;
    }

    public function getCostStrategy(): CostStrategy
    {
        return $this->costStrategy;
    }

    public function setCostStrategy(CostStrategy $costStrategy): void
    {
        CostRecordValidator::validateCostStrategy($costStrategy);
        $this->costStrategy = $costStrategy;
    }

    public function getCostType(): CostType
    {
        return $this->costType;
    }

    public function setCostType(CostType $costType): void
    {
        CostRecordValidator::validateCostType($costType);
        $this->costType = $costType;
    }

    public function getPeriod(): ?CostPeriod
    {
        return $this->period;
    }

    public function setPeriod(?CostPeriod $period): void
    {
        $this->period = $period;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(?string $operator): void
    {
        CostRecordValidator::validateOperator($operator);
        $this->operator = $operator;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|string|null $metadata
     */
    public function setMetadata(array|string|null $metadata): void
    {
        $this->metadata = CostRecordValidator::validateAndConvertMetadata($metadata);
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeImmutable $recordedAt): void
    {
        $this->recordedAt = $recordedAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getStockBatch(): ?StockBatch
    {
        return $this->stockBatch;
    }

    public function setStockBatch(?StockBatch $stockBatch): void
    {
        $this->stockBatch = $stockBatch;
    }

    /**
     * 从关联的 StockBatch 同步数据
     *
     * 注意：这个方法保持向后兼容性，但建议使用 CostRecordService::syncFromStockBatch()
     * 以获得更好的可测试性和职责分离
     */
    public function syncFromStockBatch(): void
    {
        if (null === $this->stockBatch) {
            return;
        }

        // 简化的同步逻辑，委托给具体的私有方法
        $this->syncBatchNoFromStock();
        $this->syncUnitCostFromStock();
        $this->syncSkuIdFromStockIfNeeded();
    }

    /**
     * 同步批次号
     */
    private function syncBatchNoFromStock(): void
    {
        if (null !== $this->stockBatch) {
            $this->batchNo = $this->stockBatch->getBatchNo();
        }
    }

    /**
     * 同步单位成本
     */
    private function syncUnitCostFromStock(): void
    {
        if (null !== $this->stockBatch) {
            $this->unitCost = $this->stockBatch->getUnitCost();
        }
    }

    /**
     * 如果需要，同步 SKU ID
     */
    private function syncSkuIdFromStockIfNeeded(): void
    {
        if (null === $this->stockBatch) {
            return;
        }

        $stockSku = $this->stockBatch->getSku();
        if (null !== $stockSku && '' === $this->skuId) {
            $this->skuId = $stockSku->getId();
        }
    }

    public function __toString(): string
    {
        return sprintf(
            'CostRecord(sku=%s, qty=%d, unitCost=%.2f, total=%.2f)',
            $this->skuId ?? 'N/A',
            $this->quantity,
            $this->unitCost,
            $this->totalCost
        );
    }
}
