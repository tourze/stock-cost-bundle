<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\CostAllocationException;
use Tourze\StockCostBundle\Repository\CostAllocationRepository;
use Tourze\StockCostBundle\Service\Calculator\ActivityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\CostAllocationCalculator;
use Tourze\StockCostBundle\Service\Calculator\QuantityAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\RatioAllocationStrategy;
use Tourze\StockCostBundle\Service\Calculator\ValueAllocationStrategy;
use Tourze\StockCostBundle\Validator\CostAllocationValidator;

class CostAllocationServiceImpl implements CostAllocationServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CostAllocationRepository $repository,
        private readonly CostAllocationCalculator $calculator,
    ) {
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<int, array<string, mixed>> $targets
     */
    public function createAllocationRule(
        string $name,
        string $type,
        array $criteria,
        array $targets,
    ): CostAllocation {
        $allocation = new CostAllocation();
        $allocation->setAllocationName($name);
        $allocation->setSourceType(CostType::from(strtoupper($type)));
        $allocation->setTotalAmount(1.0); // 临时设置为1.0，实际分摊时会重新设置
        $allocation->setAllocationMethod(AllocationMethod::RATIO); // 默认比例分摊
        $allocation->setTargets($targets);

        $this->entityManager->persist($allocation);
        $this->entityManager->flush();

        return $allocation;
    }

    /**
     * @return CostRecord[]
     */
    public function allocateCost(
        float $totalCost,
        CostAllocation $rule,
        \DateTimeImmutable $effectiveDate,
    ): array {
        if ($totalCost <= 0) {
            throw CostAllocationException::invalidTotalAmount($totalCost);
        }

        // 设置分摊规则的总金额
        $rule->setTotalAmount($totalCost);

        // 计算各目标的分摊金额
        $allocations = $this->calculator->calculate($rule);

        if ([] === $allocations) {
            return [];
        }

        $records = [];
        foreach ($allocations as $skuId => $allocatedAmount) {
            $record = $this->createCostRecord($skuId, $allocatedAmount, $rule, $effectiveDate);
            $records[] = $record;
            $this->entityManager->persist($record);
        }

        $this->entityManager->flush();

        return $records;
    }

    public function getAllocatedCost(string $skuId, \DateTimeImmutable $date): float
    {
        $cost = $this->repository->findAllocatedCostForSku($skuId, $date);

        return $cost ?? 0.0;
    }

    private function createCostRecord(
        string $skuId,
        float $allocatedAmount,
        CostAllocation $rule,
        \DateTimeImmutable $effectiveDate,
    ): CostRecord {
        $record = new CostRecord();
        $record->setSkuId($skuId);
        $record->setQuantity(1); // 分摊成本的数量为1
        $record->setUnitCost($allocatedAmount);
        $record->setTotalCost($allocatedAmount);
        $record->setCostStrategy(CostStrategy::STANDARD_COST); // 分摊成本使用标准成本策略
        $record->setCostType($rule->getSourceType());
        $record->setPeriod($rule->getPeriod());
        $record->setOperator('system');

        // 设置元数据
        $metadata = [
            'allocation_id' => $rule->getId(),
            'allocation_name' => $rule->getAllocationName(),
            'allocation_method' => $rule->getAllocationMethod(),
            'effective_date' => $effectiveDate->format('Y-m-d'),
        ];
        $record->setMetadata($metadata);

        return $record;
    }
}
