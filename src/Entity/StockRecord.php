<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\StockCostBundle\Repository\StockRecordRepository;

/**
 * 库存记录实体
 *
 * 记录库存的历史变化信息，用于成本计算
 */
#[ORM\Entity(repositoryClass: StockRecordRepository::class)]
#[ORM\Table(name: 'stock_records', options: ['comment' => '库存记录表，记录库存历史变化信息用于成本计算'])]
#[ORM\Index(name: 'stock_records_idx_sku_date', columns: ['sku', 'record_date'])]
class StockRecord implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'SKU不能为空')]
    #[Assert\Length(max: 100, maxMessage: 'SKU长度不能超过{{ limit }}个字符')]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '商品SKU编码'])]
    private string $sku = '';

    #[Assert\NotNull(message: '记录日期不能为空')]
    #[ORM\Column(name: 'record_date', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '记录日期'])]
    private ?\DateTimeImmutable $recordDate = null;

    #[Assert\PositiveOrZero(message: '原始库存数量不能为负数')]
    #[ORM\Column(name: 'original_quantity', type: Types::INTEGER, options: ['comment' => '原始库存数量'])]
    private int $originalQuantity = 0;

    #[Assert\PositiveOrZero(message: '当前库存数量不能为负数')]
    #[ORM\Column(name: 'current_quantity', type: Types::INTEGER, options: ['comment' => '当前库存数量'])]
    private int $currentQuantity = 0;

    #[Assert\PositiveOrZero(message: '单位成本不能为负数')]
    #[ORM\Column(name: 'unit_cost', type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '单位成本'])]
    private float $unitCost = 0.00;

    #[Assert\PositiveOrZero(message: '总成本不能为负数')]
    #[ORM\Column(name: 'total_cost', type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '总成本'])]
    private float $totalCost = 0.00;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getRecordDate(): ?\DateTimeImmutable
    {
        return $this->recordDate;
    }

    public function setRecordDate(\DateTimeImmutable $recordDate): void
    {
        $this->recordDate = $recordDate;
    }

    public function getOriginalQuantity(): int
    {
        return $this->originalQuantity;
    }

    public function setOriginalQuantity(int $originalQuantity): void
    {
        $this->originalQuantity = $originalQuantity;
    }

    public function getCurrentQuantity(): int
    {
        return $this->currentQuantity;
    }

    public function setCurrentQuantity(int $currentQuantity): void
    {
        $this->currentQuantity = $currentQuantity;
        $this->totalCost = $this->currentQuantity * $this->unitCost;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUnitCost(): float
    {
        return $this->unitCost;
    }

    public function setUnitCost(float $unitCost): void
    {
        $this->unitCost = $unitCost;
        $this->totalCost = $this->currentQuantity * $unitCost;
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isAvailable(): bool
    {
        return $this->currentQuantity > 0;
    }

    public function reduceQuantity(int $amount): void
    {
        $newQuantity = max(0, $this->currentQuantity - $amount);
        $this->setCurrentQuantity($newQuantity);
    }

    public function __toString(): string
    {
        return sprintf(
            'StockRecord[SKU: %s, Date: %s, Current: %d, Cost: %.2f]',
            $this->sku,
            $this->recordDate?->format('Y-m-d') ?? 'N/A',
            $this->currentQuantity,
            $this->unitCost
        );
    }
}
