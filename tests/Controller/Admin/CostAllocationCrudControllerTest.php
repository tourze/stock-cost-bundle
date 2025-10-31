<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\StockCostBundle\Controller\Admin\CostAllocationCrudController;
use Tourze\StockCostBundle\Entity\CostAllocation;

/**
 * @internal
 */
#[CoversClass(CostAllocationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CostAllocationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): CostAllocationCrudController
    {
        return self::getService(CostAllocationCrudController::class);
    }

    protected function onSetUp(): void
    {
        // 规避基类 bug：AbstractWebTestCase::createAuthenticatedClient() 未正确注册客户端
        static::createClient();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        // 基于控制器configureFields方法中在index页面显示的字段
        yield 'ID' => ['ID'];
        yield '分摊名称' => ['分摊名称'];
        yield '源成本类型' => ['源成本类型'];
        yield '总金额' => ['总金额'];
        yield '分摊方法' => ['分摊方法'];
        yield '分摊日期' => ['分摊日期'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 基于控制器configureFields方法中NEW页面显示的字段
        yield 'allocationName' => ['allocationName'];
        yield 'sourceType' => ['sourceType'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'allocationMethod' => ['allocationMethod'];
        yield 'allocationDate' => ['allocationDate'];
        yield 'period' => ['period'];
        yield 'targets' => ['targets'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 基于控制器configureFields方法中EDIT页面显示的字段
        yield 'allocationName' => ['allocationName'];
        yield 'sourceType' => ['sourceType'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'allocationMethod' => ['allocationMethod'];
        yield 'allocationDate' => ['allocationDate'];
        yield 'period' => ['period'];
        yield 'targets' => ['targets'];
    }

    public function testExtendsAbstractCrudController(): void
    {
        $this->assertInstanceOf(AbstractCrudController::class, new CostAllocationCrudController());
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(CostAllocation::class, CostAllocationCrudController::getEntityFqcn());
    }

    public function testControllerCanBeInstantiated(): void
    {
        $controller = new CostAllocationCrudController();
        $this->assertInstanceOf(CostAllocationCrudController::class, $controller);
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();

        // 获取表单并直接提交（不填写必填字段）
        $form = $crawler->filter('form[name="CostAllocation"]')->form();

        // 提交表单
        $client->submit($form);

        // 验证返回422状态码
        $this->assertResponseStatusCodeSame(422);

        // 验证响应内容包含必填字段错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringContainsString('This value should not be blank', $responseContent);
    }
}
