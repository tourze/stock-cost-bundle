<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockManageBundle\Service\BatchQueryServiceInterface;

/**
 * 数据一致性校验器
 *
 * 用于检查CostRecord与StockBatch之间的数据一致性
 *
 * @deprecated 使用专门的验证器类：CostRecordConsistencyValidator, StockBatchConsistencyValidator, ConsistencyFixer
 */
class DataConsistencyValidator
{
    private readonly StockBatchConsistencyValidator $batchValidator;

    private readonly CostRecordConsistencyValidator $costRecordValidator;

    private readonly ConsistencyFixer $fixer;

    public function __construct(
        private readonly CostRecordRepository $costRecordRepository,
        private readonly BatchQueryServiceInterface $batchQueryService,
    ) {
        $this->batchValidator = new StockBatchConsistencyValidator();
        $this->costRecordValidator = new CostRecordConsistencyValidator();
        $this->fixer = new ConsistencyFixer($costRecordRepository);
    }

    /**
     * 校验单个成本记录的一致性
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateCostRecordConsistency(CostRecord $costRecord): array
    {
        return $this->batchValidator->validateCostRecordWithBatch($costRecord);
    }

    /**
     * 校验系统整体的数据一致性
     *
     * @return array{isValid: bool, totalRecords: int, inconsistentRecords: int, errors: array<string>}
     */
    public function validateSystemConsistency(): array
    {
        $costRecords = $this->costRecordRepository->findAll();
        $stockBatches = $this->batchQueryService->getAllBatches();

        // 创建StockBatch的索引以便快速查找
        $stockBatchIndex = [];
        foreach ($stockBatches as $batch) {
            $stockBatchIndex[$batch->getBatchNo()] = $batch;
        }

        $totalRecords = count($costRecords);
        $inconsistentRecords = 0;
        $allErrors = [];

        foreach ($costRecords as $costRecord) {
            // 如果CostRecord没有关联的StockBatch，尝试从索引中查找
            if (null === $costRecord->getStockBatch() && isset($stockBatchIndex[$costRecord->getBatchNo()])) {
                $costRecord->setStockBatch($stockBatchIndex[$costRecord->getBatchNo()]);
            }

            $validation = $this->validateCostRecordConsistency($costRecord);
            if (!$validation['isValid']) {
                ++$inconsistentRecords;
                $recordErrors = array_map(
                    fn ($error) => "Record {$costRecord->getSkuId()}#{$costRecord->getBatchNo()}: {$error}",
                    $validation['errors']
                );
                $allErrors = array_merge($allErrors, $recordErrors);
            }
        }

        return [
            'isValid' => 0 === $inconsistentRecords,
            'totalRecords' => $totalRecords,
            'inconsistentRecords' => $inconsistentRecords,
            'errors' => $allErrors,
        ];
    }

    /**
     * 校验成本总计的一致性
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateCostTotalConsistency(CostRecord $costRecord): array
    {
        return $this->costRecordValidator->validateCostTotalConsistency($costRecord);
    }

    /**
     * 校验数量约束
     *
     * @return array{isValid: bool, errors: array<string>}
     */
    public function validateQuantityConstraints(CostRecord $costRecord): array
    {
        return $this->costRecordValidator->validateQuantityConstraints($costRecord);
    }

    /**
     * 修复数据不一致问题
     *
     * @return array{fixed: int, errors: array<string>}
     */
    public function fixInconsistencies(): array
    {
        $validation = $this->validateSystemConsistency();
        if ($validation['isValid']) {
            return [
                'fixed' => 0,
                'errors' => [],
            ];
        }

        return $this->fixer->fixInconsistentRecords();
    }
}
