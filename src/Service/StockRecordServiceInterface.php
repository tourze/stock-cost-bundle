<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * 库存记录服务接口
 *
 * 提供库存记录查询和管理功能
 */
interface StockRecordServiceInterface
{
    /**
     * 根据SKU获取库存记录
     *
     * @param string $sku 商品SKU
     *
     * @return array<StockRecord> 按时间排序的库存记录
     */
    public function getStockRecordsForSku(string $sku): array;

    /**
     * 获取当前可用库存数量
     *
     * @param string $sku 商品SKU
     */
    public function getCurrentStock(string $sku): int;

    /**
     * 创建库存记录
     *
     * @param string $sku 商品SKU
     * @param int $quantity 数量
     * @param float $unitCost 单位成本
     * @param \DateTimeImmutable $recordDate 记录日期
     */
    public function createStockRecord(
        string $sku,
        int $quantity,
        float $unitCost,
        \DateTimeImmutable $recordDate,
    ): StockRecord;

    /**
     * 更新库存记录数量
     *
     * @param StockRecord $record 库存记录
     * @param int $newQuantity 新数量
     */
    public function updateStockQuantity(StockRecord $record, int $newQuantity): StockRecord;
}
