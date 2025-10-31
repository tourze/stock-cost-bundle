<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

/**
 * 标准成本服务
 *
 * 内存存储的标准成本配置服务实现
 */
class StandardCostService implements StandardCostServiceInterface
{
    /**
     * @var array<string, float> SKU到标准成本的映射
     */
    private array $standardCosts = [];

    public function getStandardCost(string $sku): ?float
    {
        return $this->standardCosts[$sku] ?? null;
    }

    public function setStandardCost(string $sku, float $cost, ?\DateTimeImmutable $effectiveDate = null): void
    {
        // 简单实现，忽略生效日期参数，直接设置当前成本
        // 在实际生产环境中，可以扩展为支持历史版本管理
        $this->standardCosts[$sku] = $cost;
    }

    public function hasStandardCost(string $sku): bool
    {
        return array_key_exists($sku, $this->standardCosts);
    }

    /**
     * @param array<string> $skus
     * @return array<string, float>
     */
    public function getBatchStandardCosts(array $skus): array
    {
        $result = [];

        foreach ($skus as $sku) {
            if ($this->hasStandardCost($sku)) {
                $result[$sku] = $this->standardCosts[$sku];
            }
        }

        return $result;
    }

    public function removeStandardCost(string $sku): void
    {
        unset($this->standardCosts[$sku]);
    }

    /**
     * @return array<string>
     */
    public function getAllConfiguredSkus(): array
    {
        return array_keys($this->standardCosts);
    }
}
