<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;
use Tourze\ProductServiceContracts\SKU;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\EventListener\StockChangeListener;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostServiceInterface;
use Tourze\StockManageBundle\Entity\StockBatch;
use Tourze\StockManageBundle\Event\StockAdjustedEvent;

/**
 * @internal
 */
#[CoversClass(StockChangeListener::class)]
#[RunTestsInSeparateProcesses]
class StockChangeListenerTest extends AbstractEventSubscriberTestCase
{
    private MockObject&CostServiceInterface $costService;

    private StockChangeListener $listener;

    protected function onSetUp(): void
    {
        $this->costService = $this->createMock(CostServiceInterface::class);

        // 将 Mock 的服务设置到容器中
        self::getContainer()->set(CostServiceInterface::class, $this->costService);

        // 从容器获取监听器，这样它会使用我们的 Mock 服务
        $listener = self::getContainer()->get(StockChangeListener::class);
        $this->assertInstanceOf(StockChangeListener::class, $listener);
        $this->listener = $listener;
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(StockChangeListener::class, $this->listener);
    }

    public function testOnStockAdjustedCallsCostService(): void
    {
        // 创建模拟的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU-001');

        // 创建模拟的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);
        $stockBatch->method('getQuantity')->willReturn(100);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 设置期望的成本计算结果
        $expectedResult = new CostCalculationResult(
            'SKU-001',
            100,
            15.50,
            1550.00,
            CostStrategy::FIFO
        );

        // 设置成本服务的期望调用
        $this->costService
            ->expects($this->once())
            ->method('calculateCost')
            ->with('SKU-001', 100)
            ->willReturn($expectedResult)
        ;

        // 调用监听器方法
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedWithNullSku(): void
    {
        // 创建没有SKU的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn(null);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 成本服务不应该被调用
        $this->costService
            ->expects($this->never())
            ->method('calculateCost')
        ;

        // 调用监听器方法（不应抛出异常）
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedWithEmptySkuId(): void
    {
        // 创建返回空字符串ID的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('');

        // 创建模拟的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 成本服务不应该被调用
        $this->costService
            ->expects($this->never())
            ->method('calculateCost')
        ;

        // 调用监听器方法（不应抛出异常）
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedWithZeroQuantity(): void
    {
        // 创建模拟的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU-001');

        // 创建数量为0的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);
        $stockBatch->method('getQuantity')->willReturn(0);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 数量为0时，成本服务不应该被调用
        $this->costService
            ->expects($this->never())
            ->method('calculateCost')
        ;

        // 调用监听器方法（不应抛出异常）
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedWithNegativeQuantity(): void
    {
        // 创建模拟的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU-001');

        // 创建负数量的StockBatch（可能是库存调减）
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);
        $stockBatch->method('getQuantity')->willReturn(-50);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 负数量时，成本服务不应该被调用
        $this->costService
            ->expects($this->never())
            ->method('calculateCost')
        ;

        // 调用监听器方法（不应抛出异常）
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedWithLargeQuantity(): void
    {
        // 创建模拟的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU-BULK');

        // 创建大数量的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);
        $stockBatch->method('getQuantity')->willReturn(10000);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 设置期望的成本计算结果
        $expectedResult = new CostCalculationResult(
            'SKU-BULK',
            10000,
            5.00,
            50000.00,
            CostStrategy::FIFO
        );

        // 成本服务应该被调用
        $this->costService
            ->expects($this->once())
            ->method('calculateCost')
            ->with('SKU-BULK', 10000)
            ->willReturn($expectedResult)
        ;

        // 调用监听器方法
        $this->listener->onStockAdjusted($event);
    }

    public function testOnStockAdjustedHandlesCostServiceException(): void
    {
        // 创建模拟的SKU
        $sku = $this->createMock(SKU::class);
        $sku->method('getId')->willReturn('SKU-ERROR');

        // 创建模拟的StockBatch
        $stockBatch = $this->createMock(StockBatch::class);
        $stockBatch->method('getSku')->willReturn($sku);
        $stockBatch->method('getQuantity')->willReturn(100);

        // 创建模拟的事件
        $event = $this->createMock(StockAdjustedEvent::class);
        $event->method('getStockBatch')->willReturn($stockBatch);

        // 成本服务抛出异常
        $this->costService
            ->expects($this->once())
            ->method('calculateCost')
            ->with('SKU-ERROR', 100)
            ->willThrowException(new \RuntimeException('成本计算失败'))
        ;

        // 监听器应该让异常向上传播
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('成本计算失败');

        $this->listener->onStockAdjusted($event);
    }

    public function testListenerHasCorrectEventAttribute(): void
    {
        // 使用反射检查监听器是否正确配置了事件属性
        $reflection = new \ReflectionClass(StockChangeListener::class);
        $attributes = $reflection->getAttributes();

        $this->assertNotEmpty($attributes, '监听器类应该有属性注解');

        // 检查是否有AsEventListener属性
        $hasAsEventListener = false;
        foreach ($attributes as $attribute) {
            if ('Symfony\Component\EventDispatcher\Attribute\AsEventListener' === $attribute->getName()) {
                $hasAsEventListener = true;
                break;
            }
        }

        $this->assertTrue($hasAsEventListener, '监听器应该有AsEventListener属性');
    }

    public function testMultipleEventCallsWithDifferentSkus(): void
    {
        $skus = ['SKU-001', 'SKU-002', 'SKU-003'];
        $quantities = [100, 200, 50];

        // 设置成本服务期望被调用3次
        $this->costService
            ->expects($this->exactly(3))
            ->method('calculateCost')
            ->willReturnCallback(function (string $skuId, int $quantity) {
                return new CostCalculationResult(
                    $skuId,
                    $quantity,
                    10.00,
                    $quantity * 10.00,
                    CostStrategy::FIFO
                );
            })
        ;

        foreach ($skus as $index => $skuId) {
            // 创建模拟的SKU
            $sku = $this->createMock(SKU::class);
            $sku->method('getId')->willReturn($skuId);

            // 创建模拟的StockBatch
            $stockBatch = $this->createMock(StockBatch::class);
            $stockBatch->method('getSku')->willReturn($sku);
            $stockBatch->method('getQuantity')->willReturn($quantities[$index]);

            // 创建模拟的事件
            $event = $this->createMock(StockAdjustedEvent::class);
            $event->method('getStockBatch')->willReturn($stockBatch);

            // 调用监听器方法
            $this->listener->onStockAdjusted($event);
        }
    }
}
