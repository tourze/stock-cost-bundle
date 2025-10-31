<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

/**
 * 标准成本服务接口
 *
 * 提供标准成本配置和管理功能
 */
interface StandardCostServiceInterface
{
    /**
     * 获取指定SKU的标准成本
     *
     * @param string $sku 商品SKU
     *
     * @return float|null 标准成本，如果未配置则返回null
     */
    public function getStandardCost(string $sku): ?float;

    /**
     * 设置指定SKU的标准成本
     *
     * @param string $sku 商品SKU
     * @param float $cost 标准成本
     * @param \DateTimeImmutable|null $effectiveDate 生效日期，默认立即生效
     */
    public function setStandardCost(string $sku, float $cost, ?\DateTimeImmutable $effectiveDate = null): void;

    /**
     * 检查指定SKU是否配置了标准成本
     *
     * @param string $sku 商品SKU
     *
     * @return bool 是否配置了标准成本
     */
    public function hasStandardCost(string $sku): bool;

    /**
     * 批量获取多个SKU的标准成本
     *
     * @param array<string> $skus SKU列表
     *
     * @return array<string, float> SKU到标准成本的映射，未配置的SKU不包含在结果中
     */
    public function getBatchStandardCosts(array $skus): array;

    /**
     * 删除指定SKU的标准成本配置
     *
     * @param string $sku 商品SKU
     */
    public function removeStandardCost(string $sku): void;

    /**
     * 获取所有配置了标准成本的SKU列表
     *
     * @return array<string> SKU列表
     */
    public function getAllConfiguredSkus(): array;
}
