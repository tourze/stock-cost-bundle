# Tourze Stock Cost Bundle

[English](README.md) | [中文](README.zh-CN.md)

专业的库存成本管理 Symfony Bundle，提供完整的成本计算、核算、分析和管理功能。

## ✨ 特性

### 🧮 成本计算策略
- **FIFO（先进先出）**: 最先入库的库存最先出库
- **LIFO（后进先出）**: 最后入库的库存最先出库
- **加权平均法**: 按库存数量加权计算平均成本
- **标准成本法**: 使用预设的标准成本进行核算

### 📊 核心功能
- **成本记录管理**: 记录每个SKU和批次的详细成本信息
- **库存成本跟踪**: 实时跟踪库存变化对成本的影响
- **成本期间管理**: 支持按期间进行成本核算和结转
- **成本分配**: 支持多种成本分配方法和策略
- **差异分析**: 标准成本与实际成本的差异分析
- **成本报告**: 生成详细的成本分析报告

### 🎯 管理界面
- EasyAdmin 集成的后台管理界面
- 直观的成本记录和库存管理
- 实时成本计算和验证
- 完整的数据一致性检查

## 📦 安装

使用 Composer 安装：

```bash
composer require tourze/stock-cost-bundle
```

## 🚀 快速开始

### 1. 注册 Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\StockCostBundle\StockCostBundle::class => ['all' => true],
];
```

### 2. 创建数据库表

```bash
php bin/console doctrine:schema:update --force
```

### 3. 加载测试数据（可选）

```bash
php bin/console doctrine:fixtures:load --group=stock-cost
```

### 4. 基本使用

```php
use Tourze\StockCostBundle\Service\CostService;
use Tourze\StockCostBundle\Enum\CostStrategy;

// 计算库存成本
$costService = $container->get(CostService::class);
$result = $costService->calculateCost('SKU123', 100, CostStrategy::FIFO);

// 获取成本记录
$costRecords = $costService->getCostRecords('SKU123');

// 生成成本报告
$report = $costService->generateCostReport('SKU123', new \DateTime('-30 days'), new \DateTime());
```

## 🏗️ 架构设计

### 核心实体

#### CostRecord（成本记录）
```php
// 记录每个SKU的成本信息
- skuId: SKU标识
- batchNo: 批次号
- unitCost: 单位成本
- quantity: 数量
- totalCost: 总成本
- costStrategy: 成本策略
- costType: 成本类型
```

#### StockRecord（库存记录）
```php
// 记录库存的历史变化
- sku: 商品SKU
- recordDate: 记录日期
- originalQuantity: 原始数量
- currentQuantity: 当前数量
- changeType: 变化类型
- changeQuantity: 变化数量
```

#### CostPeriod（成本期间）
```php
// 成本核算期间管理
- periodCode: 期间编码
- startDate: 开始日期
- endDate: 结束日期
- status: 期间状态
- description: 期间描述
```

#### CostAllocation（成本分配）
```php
// 成本分配记录
- sourceSku: 来源SKU
- targetSku: 目标SKU
- allocationMethod: 分配方法
- allocationRatio: 分配比例
- allocatedAmount: 已分配金额
```

### 成本计算策略

#### FIFO（先进先出）
```php
// 最先入库的库存最先出库，适用于保质期敏感的商品
$calculator = new FifoCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### LIFO（后进先出）
```php
// 最后入库的库存最先出库，适用于通胀环境
$calculator = new LifoCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### Weighted Average（加权平均）
```php
// 按数量加权计算平均成本，适用于大宗商品
$calculator = new WeightedAverageCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### Standard Cost（标准成本）
```php
// 使用预设标准成本，适用于标准化生产
$calculator = new StandardCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity, $standardCost);
```

## ⚙️ 配置

### 基础配置

```yaml
# config/packages/stock_cost.yaml
stock_cost:
    # 默认成本计算策略
    default_strategy: FIFO

    # 成本精度设置
    cost_precision: 2

    # 启用成本差异分析
    enable_variance_analysis: true

    # 差异阈值（超过此值触发警告）
    variance_threshold: 0.1

    # 自动分配策略
    auto_allocation:
        enabled: true
        method: RATIO
        default_ratio: 0.5
```

## 📊 使用示例

### 成本计算示例

```php
use Tourze\StockCostBundle\Service\CostService;

// 获取服务
$costService = $container->get(CostService::class);

// FIFO 计算
$result = $costService->calculateCost('SKU001', 50, CostStrategy::FIFO);
echo "FIFO 成本: {$result->getTotalCost()}, 数量: {$result->getQuantity()}";

// 加权平均计算
$result = $costService->calculateCost('SKU001', 50, CostStrategy::WEIGHTED_AVERAGE);
echo "加权平均成本: {$result->getUnitCost()}";

// 批量成本计算
$skus = ['SKU001', 'SKU002', 'SKU003'];
$results = $costService->batchCalculateCost($skus, 100, CostStrategy::FIFO);
foreach ($results as $sku => $result) {
    echo "{$sku}: {$result->getTotalCost()}\n";
}
```

### 成本记录管理

```php
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Enum\CostStrategy;

// 创建成本记录
$costRecord = new CostRecord();
$costRecord->setSkuId('SKU001')
    ->setBatchNo('BATCH001')
    ->setUnitCost(10.50)
    ->setQuantity(100)
    ->setTotalCost(1050.00)
    ->setCostStrategy(CostStrategy::FIFO)
    ->setCostType(CostType::PURCHASE);

// 保存记录
$entityManager->persist($costRecord);
$entityManager->flush();
```

## 🔧 扩展开发

### 自定义成本计算策略

```php
use Tourze\StockCostBundle\Service\Calculator\CostStrategyCalculatorInterface;

class CustomCostCalculator implements CostStrategyCalculatorInterface
{
    public function calculate(array $stockRecords, int $quantity, ?array $options = []): CostCalculationResult
    {
        // 实现自定义成本计算逻辑
        $totalCost = 0;
        $remainingQuantity = $quantity;

        foreach ($stockRecords as $record) {
            if ($remainingQuantity <= 0) break;

            $usedQuantity = min($remainingQuantity, $record->getQuantity());
            $totalCost += $usedQuantity * $record->getUnitCost();
            $remainingQuantity -= $usedQuantity;
        }

        return new CostCalculationResult($totalCost, $quantity - $remainingQuantity);
    }

    public function getStrategy(): string
    {
        return 'CUSTOM';
    }
}
```

## 🧪 测试

### 运行测试

```bash
# 运行所有测试
composer test

# 运行特定测试套件
composer test:unit
composer test:integration

# 生成测试覆盖率报告
composer test:coverage
```

### 测试覆盖率

当前测试覆盖率：
- 单元测试覆盖率: 85%+
- 集成测试覆盖率: 75%+
- 综合覆盖率: 80%+

## 🔧 故障排除

### 常见问题

#### 1. 成本计算结果不正确
```php
// 检查库存记录是否完整
$records = $stockRecordRepository->findBy(['sku' => 'SKU001']);
if (empty($records)) {
    throw new \RuntimeException('没有找到库存记录');
}

// 检查成本记录是否完整
$costRecords = $costRecordRepository->findBy(['skuId' => 'SKU001']);
if (empty($costRecords)) {
    throw new \RuntimeException('没有找到成本记录');
}
```

#### 2. 成本期间结转失败
```php
// 检查期间状态
$period = $costPeriodRepository->findOneBy(['periodCode' => '2024Q1']);
if ($period->getStatus() !== CostPeriodStatus::OPEN) {
    throw new \RuntimeException('期间已经关闭，无法结转');
}
```

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

### 开发环境设置

```bash
# 克隆仓库
git clone https://github.com/tourze/php-monorepo.git
cd packages/stock-cost-bundle

# 安装依赖
composer install

# 运行测试
composer test

# 检查代码质量
composer lint
composer phpstan
```

### 代码规范

- 遵循 PSR-12 代码规范
- 使用 PHPStan 进行静态分析，要求 level 9+
- 测试覆盖率不低于 80%
- 提交信息遵循 Conventional Commits 规范

## 📄 许可证

本项目采用 MIT 许可证。详情请见 [LICENSE](LICENSE) 文件。

## 🔗 相关链接

- [主项目文档](https://github.com/tourze/php-monorepo)
- [Stock Manage Bundle](../stock-manage-bundle/)
- [Easy Admin Enum Field Bundle](../easy-admin-enum-field-bundle/)
- [Doctrine Indexed Bundle](../doctrine-indexed-bundle/)

## 📈 版本历史

### v1.0.0 (2024-11-11)
- 初始版本发布
- 实现四种成本计算策略
- 提供 EasyAdmin 管理界面
- 完整的测试覆盖

### v1.1.0 (计划中)
- 增加成本预测功能
- 支持更多货币类型
- 增强报告功能
- 性能优化

---

**维护者**: [Tourze Team](mailto:team@tourze.com)

如果您觉得这个项目有用，请给我们一个 ⭐️！
