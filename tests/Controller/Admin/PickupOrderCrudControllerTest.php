<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Controller\Admin;

use CainiaoPickupBundle\Controller\Admin\PickupOrderCrudController;
use CainiaoPickupBundle\Entity\PickupOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PickupOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
class PickupOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * 获取被测试的控制器服务
     */
    /**
     * @return AbstractCrudController<PickupOrder>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(PickupOrderCrudController::class);
    }

    /**
     * 提供索引页面表头信息
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Order Code' => ['取件单号'];
        yield 'Config' => ['菜鸟配置'];
        yield 'Sender Info' => ['发件人信息'];
        yield 'Receiver Info' => ['收件人信息'];
        yield 'Item Type' => ['物品类型'];
        yield 'Weight' => ['物品重量(kg)'];
        yield 'Status' => ['订单状态'];
        yield 'Tracking Number' => ['运单号'];
        yield 'Created At' => ['创建时间'];
        yield 'Updated At' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'Order Code' => ['orderCode'];
        yield 'Config' => ['config'];
        yield 'Sender Info' => ['senderInfo'];
        yield 'Receiver Info' => ['receiverInfo'];
        yield 'Item Type' => ['itemType'];
        yield 'Weight' => ['weight'];
        yield 'Status' => ['status'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'Order Code' => ['orderCode'];
        yield 'Config' => ['config'];
        yield 'Sender Info' => ['senderInfo'];
        yield 'Receiver Info' => ['receiverInfo'];
        yield 'Item Type' => ['itemType'];
        yield 'Weight' => ['weight'];
        yield 'Status' => ['status'];
        yield 'External User ID' => ['externalUserId'];
        yield 'External User Mobile' => ['externalUserMobile'];
    }

    /**
     * 测试表单验证错误
     * 提交空表单并验证必填字段的错误信息
     */
    #[Test]
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));

        // 获取表单并提交空数据
        $form = $crawler->selectButton('Create')->form();
        $client->submit($form);

        // 验证返回422状态码（表单验证失败）
        $this->assertResponseStatusCodeSame(422);

        // 获取响应的爬虫对象以检查错误信息
        $crawler = $client->getCrawler();

        // 验证必填字段的验证错误信息（支持中英文）
        $errorText = $crawler->filter('.invalid-feedback')->text();
        $hasValidationError = str_contains($errorText, 'should not be blank')
                             || str_contains($errorText, '不能为空');
        $this->assertTrue($hasValidationError, '应该包含验证错误信息');
    }
}
