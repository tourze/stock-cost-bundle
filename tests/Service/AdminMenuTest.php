<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\StockCostBundle\Service\AdminMenu;

/**
 * AdminMenu服务测试
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testServiceIsCallable(): void
    {
        self::assertIsCallable($this->adminMenu);
    }

    public function testInvokeAddsMenuItems(): void
    {
        $mainItem = $this->createMock(ItemInterface::class);
        $stockCostMenu = $this->createMock(ItemInterface::class);
        $costPeriodItem = $this->createMock(ItemInterface::class);
        $stockRecordItem = $this->createMock(ItemInterface::class);
        $costRecordItem = $this->createMock(ItemInterface::class);
        $costAllocationItem = $this->createMock(ItemInterface::class);

        // 模拟LinkGenerator行为
        $this->linkGenerator->expects($this->exactly(4))
            ->method('getCurdListPage')
            ->willReturnMap([
                ['Tourze\StockCostBundle\Entity\CostPeriod', '/admin/costperiod/list'],
                ['Tourze\StockCostBundle\Entity\StockRecord', '/admin/stockrecord/list'],
                ['Tourze\StockCostBundle\Entity\CostRecord', '/admin/costrecord/list'],
                ['Tourze\StockCostBundle\Entity\CostAllocation', '/admin/costallocation/list'],
            ])
        ;

        // 第一次调用getChild返回null，第二次返回已创建的菜单项
        $mainItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('库存成本管理')
            ->willReturnOnConsecutiveCalls(null, $stockCostMenu)
        ;

        // 创建库存成本管理父菜单
        $mainItem->expects($this->once())
            ->method('addChild')
            ->with('库存成本管理')
            ->willReturn($stockCostMenu)
        ;

        // 添加四个子菜单
        $stockCostMenu->expects($this->exactly(4))
            ->method('addChild')
            ->with(self::logicalOr('成本期间', '库存记录', '成本记录', '成本分摊'))
            ->willReturnOnConsecutiveCalls($costPeriodItem, $stockRecordItem, $costRecordItem, $costAllocationItem)
        ;

        // 设置成本期间菜单的URI和图标
        $costPeriodItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/costperiod/list')
            ->willReturn($costPeriodItem)
        ;

        $costPeriodItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-calendar')
            ->willReturn($costPeriodItem)
        ;

        // 设置库存记录菜单的URI和图标
        $stockRecordItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/stockrecord/list')
            ->willReturn($stockRecordItem)
        ;

        $stockRecordItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-boxes')
            ->willReturn($stockRecordItem)
        ;

        // 设置成本记录菜单的URI和图标
        $costRecordItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/costrecord/list')
            ->willReturn($costRecordItem)
        ;

        $costRecordItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-receipt')
            ->willReturn($costRecordItem)
        ;

        // 设置成本分摊菜单的URI和图标
        $costAllocationItem->expects($this->once())
            ->method('setUri')
            ->with('/admin/costallocation/list')
            ->willReturn($costAllocationItem)
        ;

        $costAllocationItem->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-share-alt')
            ->willReturn($costAllocationItem)
        ;

        $this->adminMenu->__invoke($mainItem);
    }
}
