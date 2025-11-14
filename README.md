# Tourze Stock Cost Bundle

[English](README.md) | [中文](README.zh-CN.md)

A professional inventory cost management Symfony Bundle that provides comprehensive cost calculation, accounting, analysis, and management capabilities.

## ✨ Features

### 🧮 Cost Calculation Strategies
- **FIFO (First-In, First-Out)**: Earliest inventory is used first
- **LIFO (Last-In, First-Out)**: Most recent inventory is used first
- **Weighted Average**: Calculates average cost weighted by inventory quantity
- **Standard Cost**: Uses predefined standard cost for accounting

### 📊 Core Features
- **Cost Record Management**: Records detailed cost information for each SKU and batch
- **Inventory Cost Tracking**: Real-time tracking of inventory changes' impact on costs
- **Cost Period Management**: Supports cost accounting and carryforward by period
- **Cost Allocation**: Supports multiple cost allocation methods and strategies
- **Variance Analysis**: Analyzes differences between standard and actual costs
- **Cost Reporting**: Generates detailed cost analysis reports

### 🎯 Management Interface
- EasyAdmin-integrated backend management interface
- Intuitive cost record and inventory management
- Real-time cost calculation and validation
- Complete data consistency checks

## 📦 Installation

Install with Composer:

```bash
composer require tourze/stock-cost-bundle
```

## 🚀 Quick Start

### 1. Register Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\StockCostBundle\StockCostBundle::class => ['all' => true],
];
```

### 2. Create Database Tables

```bash
php bin/console doctrine:schema:update --force
```

### 3. Load Test Data (Optional)

```bash
php bin/console doctrine:fixtures:load --group=stock-cost
```

### 4. Basic Usage

```php
use Tourze\StockCostBundle\Service\CostService;
use Tourze\StockCostBundle\Enum\CostStrategy;

// Calculate inventory cost
$costService = $container->get(CostService::class);
$result = $costService->calculateCost('SKU123', 100, CostStrategy::FIFO);

// Get cost records
$costRecords = $costService->getCostRecords('SKU123');

// Generate cost report
$report = $costService->generateCostReport('SKU123', new \DateTime('-30 days'), new \DateTime());
```

## 🏗️ Architecture

### Core Entities

#### CostRecord
```php
// Records cost information for each SKU
- skuId: SKU identifier
- batchNo: Batch number
- unitCost: Unit cost
- quantity: Quantity
- totalCost: Total cost
- costStrategy: Cost strategy
- costType: Cost type
```

#### StockRecord
```php
// Records historical inventory changes
- sku: Product SKU
- recordDate: Record date
- originalQuantity: Original quantity
- currentQuantity: Current quantity
- changeType: Change type
- changeQuantity: Change quantity
```

#### CostPeriod
```php
// Cost accounting period management
- periodCode: Period code
- startDate: Start date
- endDate: End date
- status: Period status
- description: Period description
```

#### CostAllocation
```php
// Cost allocation records
- sourceSku: Source SKU
- targetSku: Target SKU
- allocationMethod: Allocation method
- allocationRatio: Allocation ratio
- allocatedAmount: Allocated amount
```

### Cost Calculation Strategies

#### FIFO (First-In, First-Out)
```php
// Earliest inventory is used first, suitable for perishable goods
$calculator = new FifoCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### LIFO (Last-In, First-Out)
```php
// Most recent inventory is used first, suitable for inflationary environments
$calculator = new LifoCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### Weighted Average
```php
// Calculates weighted average cost by quantity, suitable for bulk commodities
$calculator = new WeightedAverageCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity);
```

#### Standard Cost
```php
// Uses predefined standard cost, suitable for standardized production
$calculator = new StandardCostCalculator();
$result = $calculator->calculate($stockRecords, $quantity, $standardCost);
```

## ⚙️ Configuration

### Basic Configuration

```yaml
# config/packages/stock_cost.yaml
stock_cost:
    # Default cost calculation strategy
    default_strategy: FIFO

    # Cost precision settings
    cost_precision: 2

    # Enable cost variance analysis
    enable_variance_analysis: true

    # Variance threshold (triggers warning if exceeded)
    variance_threshold: 0.1

    # Auto allocation strategy
    auto_allocation:
        enabled: true
        method: RATIO
        default_ratio: 0.5
```

### Advanced Configuration

```yaml
stock_cost:
    # Calculator-specific settings
    calculators:
        fifo:
            enabled: true
            batch_tracking: true
        lifo:
            enabled: true
            batch_tracking: true
        weighted_average:
            enabled: true
            precision: 4
        standard_cost:
            enabled: true
            auto_update: false

    # Event settings
    events:
        cost_updated:
            enabled: true
            async: false
        variance_exceeded:
            enabled: true
            threshold: 0.15
            notification_channels: ['email', 'slack']

    # Report settings
    reports:
        cost_analysis:
            enabled: true
            cache_ttl: 3600
        variance_report:
            enabled: true
            schedule: '0 8 * * 1'  # Every Monday at 8 AM
```

## 📊 Usage Examples

### Cost Calculation Examples

```php
use Tourze\StockCostBundle\Service\CostService;

// Get service
$costService = $container->get(CostService::class);

// FIFO calculation
$result = $costService->calculateCost('SKU001', 50, CostStrategy::FIFO);
echo "FIFO Cost: {$result->getTotalCost()}, Quantity: {$result->getQuantity()}";

// Weighted average calculation
$result = $costService->calculateCost('SKU001', 50, CostStrategy::WEIGHTED_AVERAGE);
echo "Weighted Average Cost: {$result->getUnitCost()}";

// Batch cost calculation
$skus = ['SKU001', 'SKU002', 'SKU003'];
$results = $costService->batchCalculateCost($skus, 100, CostStrategy::FIFO);
foreach ($results as $sku => $result) {
    echo "{$sku}: {$result->getTotalCost()}\n";
}
```

### Cost Record Management

```php
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Enum\CostStrategy;

// Create cost record
$costRecord = new CostRecord();
$costRecord->setSkuId('SKU001')
    ->setBatchNo('BATCH001')
    ->setUnitCost(10.50)
    ->setQuantity(100)
    ->setTotalCost(1050.00)
    ->setCostStrategy(CostStrategy::FIFO)
    ->setCostType(CostType::PURCHASE);

// Save record
$entityManager->persist($costRecord);
$entityManager->flush();

// Query cost records
$records = $costRecordRepository->findBySku('SKU001');
foreach ($records as $record) {
    echo "Batch: {$record->getBatchNo()}, Cost: {$record->getUnitCost()}\n";
}
```

### Cost Period Management

```php
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;

// Create cost period
$period = new CostPeriod();
$period->setPeriodCode('2024Q1')
    ->setStartDate(new DateTime('2024-01-01'))
    ->setEndDate(new DateTime('2024-03-31'))
    ->setStatus(CostPeriodStatus::OPEN)
    ->setDescription('2024 Q1 cost period');

// Period closing
$costPeriodService = $container->get(CostPeriodService::class);
$costPeriodService->closePeriod($period);
```

### Cost Variance Analysis

```php
use Tourze\StockCostBundle\Service\CostVarianceAnalysisService;

/** @var CostVarianceAnalysisService $varianceService */
$varianceService = $container->get(CostVarianceAnalysisService::class);

// Analyze cost variance
$analysis = $varianceService->analyzeVariance('SKU001', new DateTime('-30 days'), new DateTime());

echo "Standard Cost: {$analysis->getStandardCost()}\n";
echo "Actual Cost: {$analysis->getActualCost()}\n";
echo "Variance Amount: {$analysis->getVarianceAmount()}\n";
echo "Variance Rate: {$analysis->getVarianceRate()}%\n";
```

## 🔧 Extension Development

### Custom Cost Calculation Strategy

```php
use Tourze\StockCostBundle\Service\Calculator\CostStrategyCalculatorInterface;

class CustomCostCalculator implements CostStrategyCalculatorInterface
{
    public function calculate(array $stockRecords, int $quantity, ?array $options = []): CostCalculationResult
    {
        // Implement custom cost calculation logic
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

### Register Custom Strategy

```php
use Tourze\StockCostBundle\Service\CostCalculatorRegistry;

// Register as service
$container->register('app.custom_cost_calculator', CustomCostCalculator::class)
    ->addTag('stock_cost.calculator', ['strategy' => 'CUSTOM']);
```

### Custom Allocation Strategy

```php
use Tourze\StockCostBundle\Service\Calculator\AllocationStrategyInterface;

class CustomAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(array $costs, array $targets): array
    {
        // Implement custom allocation logic
        $totalCost = array_sum($costs);
        $allocated = [];

        foreach ($targets as $target => $ratio) {
            $allocated[$target] = $totalCost * $ratio;
        }

        return $allocated;
    }

    public function getMethod(): string
    {
        return 'CUSTOM_RATIO';
    }
}
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:integration

# Generate test coverage report
composer test:coverage
```

### Test Coverage

Current test coverage:
- Unit Test Coverage: 85%+
- Integration Test Coverage: 75%+
- Overall Coverage: 80%+

## 🔧 Troubleshooting

### Common Issues

#### 1. Incorrect Cost Calculation Results
```php
// Check if inventory records are complete
$records = $stockRecordRepository->findBy(['sku' => 'SKU001']);
if (empty($records)) {
    throw new \RuntimeException('No inventory records found');
}

// Check if cost records are complete
$costRecords = $costRecordRepository->findBy(['skuId' => 'SKU001']);
if (empty($costRecords)) {
    throw new \RuntimeException('No cost records found');
}
```

#### 2. Cost Period Closing Failure
```php
// Check period status
$period = $costPeriodRepository->findOneBy(['periodCode' => '2024Q1']);
if ($period->getStatus() !== CostPeriodStatus::OPEN) {
    throw new \RuntimeException('Period is already closed and cannot be carried forward');
}
```

#### 3. Cost Allocation Ratio Validation
```php
// Check allocation ratio calculation
$ratioService = $container->get(AllocationRatioService::class);
$totalRatio = $ratioService->getTotalRatio($allocations);
if (abs($totalRatio - 1.0) > 0.001) {
    throw new \RuntimeException('Allocation ratio must sum to 1.0');
}
```

### Debugging Tools

#### Enable Detailed Logging
```yaml
# config/packages/dev/monolog.yaml
monolog:
    handlers:
        stock_cost:
            type: stream
            path: "%kernel.logs_dir%/stock_cost.log"
            level: debug
            channels: ['stock_cost']
```

#### Debug Commands
```bash
# Check cost service configuration
php bin/console debug:container cost_service
php bin/console doctrine:schema:validate
php bin/console doctrine:mapping:info
```

## 🤝 Contributing

We welcome Issues and Pull Requests!

### Development Environment Setup

```bash
# Clone repository
git clone https://github.com/tourze/php-monorepo.git
cd packages/stock-cost-bundle

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer lint
composer phpstan
```

### Code Standards

- Follow PSR-12 coding standards
- Use PHPStan for static analysis, requiring level 9+
- Test coverage not below 80%
- Commit messages follow Conventional Commits specification

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 🔗 Related Links

- [Main Project Documentation](https://github.com/tourze/php-monorepo)
- [Stock Manage Bundle](../stock-manage-bundle/)
- [Easy Admin Enum Field Bundle](../easy-admin-enum-field-bundle/)
- [Doctrine Indexed Bundle](../doctrine-indexed-bundle/)

## 📈 Version History

### v1.0.0 (2024-11-11)
- Initial release
- Implemented four cost calculation strategies
- Provided EasyAdmin management interface
- Complete test coverage

### v1.1.0 (Planned)
- Add cost forecasting functionality
- Support for more currency types
- Enhanced reporting features
- Performance optimizations

---

**Maintainer**: [Tourze Team](mailto:team@tourze.com)

If you find this project useful, please give us a ⭐️!