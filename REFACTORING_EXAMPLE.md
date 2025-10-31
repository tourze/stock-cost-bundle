# CostAllocation 重构示例

## 重构概述

原本 338 行的 `CostAllocation` 实体类已被重构为多个职责单一的类，遵循贫血模型和策略模式。

## 重构后的架构

### 1. 实体层 (Entity)
- **CostAllocation.php**: 纯数据实体，仅包含基本的 getter/setter 和向后兼容方法

### 2. 验证层 (Validator)
- **CostAllocationValidator.php**: 负责所有数据验证逻辑

### 3. 计算层 (Service/Calculator)
- **AllocationStrategyInterface.php**: 分摊策略接口
- **RatioAllocationStrategy.php**: 比例分摊策略
- **QuantityAllocationStrategy.php**: 数量分摊策略
- **ValueAllocationStrategy.php**: 价值分摊策略
- **ActivityAllocationStrategy.php**: 活动基础分摊策略
- **CostAllocationCalculator.php**: 分摊计算器，协调各种策略

## 使用示例

### 旧方式（向后兼容）
```php
$allocation = new CostAllocation();
$allocation->setTotalAmount(1000.00);
$allocation->setAllocationMethod(AllocationMethod::RATIO);
$allocation->setTargets([
    ['sku_id' => 'SKU-001', 'ratio' => 0.3],
    ['sku_id' => 'SKU-002', 'ratio' => 0.7],
]);

$allocations = $allocation->calculateAllocations(); // 仍然可用，但已标记为 @deprecated
```

### 新方式（推荐）
```php
// 使用验证器
$validator = new CostAllocationValidator();
$validator->validateTotalAmount(1000.00);

// 使用计算器
$calculator = new CostAllocationCalculator(
    $validator,
    new RatioAllocationStrategy($validator),
    new QuantityAllocationStrategy($validator),
    new ValueAllocationStrategy($validator),
    new ActivityAllocationStrategy($validator)
);

$allocation = new CostAllocation();
$allocation->setTotalAmount(1000.00);
$allocation->setAllocationMethod(AllocationMethod::RATIO);
$allocation->setTargets([
    ['sku_id' => 'SKU-001', 'ratio' => 0.3],
    ['sku_id' => 'SKU-002', 'ratio' => 0.7],
]);

$allocations = $calculator->calculate($allocation);
```

### 直接参数方式
```php
$calculator = new CostAllocationCalculator(/* 策略注入 */);

$allocations = $calculator->calculateByParams(
    1000.00,
    AllocationMethod::RATIO,
    [
        ['sku_id' => 'SKU-001', 'ratio' => 0.3],
        ['sku_id' => 'SKU-002', 'ratio' => 0.7],
    ]
);
```

### 自定义策略
```php
class CustomAllocationStrategy implements AllocationStrategyInterface
{
    public function calculate(float $totalAmount, array $targets): array
    {
        // 自定义分摊逻辑
        return [];
    }

    public function getName(): string
    {
        return 'custom';
    }

    public function validateTargets(array $targets): bool
    {
        // 自定义验证逻辑
        return true;
    }
}

$calculator->registerStrategy('custom', new CustomAllocationStrategy());
```

## 重构优势

1. **单一职责原则**: 每个类只负责一个方面的功能
2. **开闭原则**: 可以轻松添加新的分摊策略而无需修改现有代码
3. **依赖倒置**: 计算器依赖于抽象接口而非具体实现
4. **更好的测试性**: 每个组件可以独立测试
5. **向后兼容**: 现有代码无需修改即可继续工作
6. **代码复用**: 验证逻辑和计算逻辑可以在其他地方复用

## 配置建议

在 Symfony 服务容器中配置：

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Tourze\StockCostBundle\Validator\CostAllocationValidator: ~

    Tourze\StockCostBundle\Service\Calculator\RatioAllocationStrategy: ~
    Tourze\StockCostBundle\Service\Calculator\QuantityAllocationStrategy: ~
    Tourze\StockCostBundle\Service\Calculator\ValueAllocationStrategy: ~
    Tourze\StockCostBundle\Service\Calculator\ActivityAllocationStrategy: ~

    Tourze\StockCostBundle\Service\Calculator\CostAllocationCalculator: ~
```