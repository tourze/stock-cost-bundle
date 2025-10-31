<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Repository\CostPeriodRepository;

/**
 * 成本期间实体
 *
 * 管理成本核算期间的基本信息，包括期间范围、状态等
 */
#[ORM\Entity(repositoryClass: CostPeriodRepository::class)]
#[ORM\Table(name: 'cost_periods', options: ['comment' => '成本核算期间表'])]
#[ORM\Index(columns: ['period_start', 'period_end'], name: 'cost_periods_idx_cost_periods_range')]
class CostPeriod implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '期间开始日期'])]
    #[Assert\NotBlank]
    #[Assert\Date]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '期间结束日期'])]
    #[Assert\NotBlank]
    #[Assert\Date]
    #[Assert\GreaterThan(propertyPath: 'periodStart')]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CostStrategy::class, options: ['comment' => '默认成本计算策略'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [CostStrategy::class, 'cases'])]
    private ?CostStrategy $defaultStrategy = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CostPeriodStatus::class, options: ['comment' => '期间状态'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [CostPeriodStatus::class, 'cases'])]
    private ?CostPeriodStatus $status = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '删除时间'])]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $deleteTime = null;

    public function __construct()
    {
        $this->status = CostPeriodStatus::OPEN;
        $this->defaultStrategy = CostStrategy::FIFO;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodStart(): ?\DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?\DateTimeImmutable $periodStart): void
    {
        $this->periodStart = $periodStart;
    }

    public function getPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?\DateTimeImmutable $periodEnd): void
    {
        $this->periodEnd = $periodEnd;
    }

    public function getDefaultStrategy(): ?CostStrategy
    {
        return $this->defaultStrategy;
    }

    public function setDefaultStrategy(?CostStrategy $defaultStrategy): void
    {
        $this->defaultStrategy = $defaultStrategy;
    }

    public function getStatus(): ?CostPeriodStatus
    {
        return $this->status;
    }

    public function setStatus(?CostPeriodStatus $status): void
    {
        $this->status = $status;
    }

    public function getDeleteTime(): ?\DateTimeImmutable
    {
        return $this->deleteTime;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleteTime;
    }

    public function softDelete(): void
    {
        $this->deleteTime = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            'CostPeriod[%s]: %s to %s (%s)',
            $this->id ?? 'new',
            $this->periodStart?->format('Y-m-d') ?? 'N/A',
            $this->periodEnd?->format('Y-m-d') ?? 'N/A',
            null !== $this->status ? $this->status->value : 'N/A'
        );
    }
}
