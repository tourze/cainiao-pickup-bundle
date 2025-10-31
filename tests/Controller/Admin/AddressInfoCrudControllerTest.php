<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Controller\Admin;

use CainiaoPickupBundle\Controller\Admin\AddressInfoCrudController;
use CainiaoPickupBundle\Entity\AddressInfo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AddressInfoCrudController::class)]
#[RunTestsInSeparateProcesses]
class AddressInfoCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /**
     * 获取被测试的控制器服务
     */
    /**
     * @return AbstractCrudController<AddressInfo>
     */
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(AddressInfoCrudController::class);
    }

    /**
     * 提供索引页面表头信息
     *
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Name' => ['联系人姓名'];
        yield 'Mobile' => ['联系电话'];
        yield 'Address' => ['完整地址'];
        yield 'City' => ['城市'];
        yield 'CreateTime' => ['创建时间'];
        yield 'UpdateTime' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 提供真实的字段名，基于 testNewPageFieldsConfiguration 中的期望字段
        yield 'Name' => ['name'];
        yield 'Mobile' => ['mobile'];
        yield 'Address' => ['fullAddressDetail'];
    }

    /**
     * 返回编辑页面显示的字段名称
     */
    public static function provideEditPageFields(): iterable
    {
        // 提供真实的字段名，基于 testEditPageFieldsConfiguration 中的期望字段
        yield 'Name' => ['name'];
        yield 'Mobile' => ['mobile'];
        yield 'Address' => ['fullAddressDetail'];
    }

    /**
     * 手动测试NEW页面字段配置，绕过框架的Client设置问题
     */
    public function testNewPageFieldsConfiguration(): void
    {
        $controller = new AddressInfoCrudController();
        $fields = $controller->configureFields('new');

        $fieldNames = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                continue;
            }
            $dto = $field->getAsDto();
            if ($dto->isDisplayedOn('new')) {
                $fieldNames[] = $dto->getProperty();
            }
        }

        // 验证关键字段存在
        $expectedFields = ['name', 'mobile', 'fullAddressDetail', 'cityName', 'areaName', 'address'];
        foreach ($expectedFields as $expectedField) {
            $this->assertContains($expectedField, $fieldNames,
                sprintf('NEW页面应该包含字段 %s', $expectedField));
        }

        $this->assertGreaterThan(0, count($fieldNames), 'NEW页面应该至少配置一个字段');
    }

    /**
     * 手动测试EDIT页面字段配置，绕过框架的Client设置问题
     */
    public function testEditPageFieldsConfiguration(): void
    {
        $controller = new AddressInfoCrudController();
        $fields = $controller->configureFields('edit');

        $fieldNames = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                continue;
            }
            $dto = $field->getAsDto();
            if ($dto->isDisplayedOn('edit')) {
                $fieldNames[] = $dto->getProperty();
            }
        }

        // 验证关键字段存在
        $expectedFields = ['name', 'mobile', 'fullAddressDetail', 'provinceName', 'cityName', 'areaName', 'address'];
        foreach ($expectedFields as $expectedField) {
            $this->assertContains($expectedField, $fieldNames,
                sprintf('EDIT页面应该包含字段 %s', $expectedField));
        }

        $this->assertGreaterThan(0, count($fieldNames), 'EDIT页面应该至少配置一个字段');
    }

    /**
     * 测试必填字段验证错误
     */
    public function testValidationErrors(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'password123');
        $this->loginAsAdmin($client, 'admin@test.com', 'password123');

        // 测试空表单提交
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $form = $crawler->selectButton('Create')->form();
        $crawler = $client->submit($form, []);

        // 使用标准方法验证422状态码
        $this->assertSame(422, $client->getResponse()->getStatusCode());

        // 验证错误信息存在于invalid-feedback元素中
        $invalidFeedbackElements = $crawler->filter('.invalid-feedback');
        if ($invalidFeedbackElements->count() > 0) {
            $errorText = $invalidFeedbackElements->text();
            // 根据实际情况，错误信息是中文的，检查关键字段的错误
            $this->assertTrue(
                str_contains($errorText, 'should not be blank')
                || str_contains($errorText, '不能为空'),
                'Invalid feedback should contain validation error message'
            );
        } else {
            // 如果没有.invalid-feedback元素，检查响应内容
            $content = (string) $client->getResponse()->getContent();
            $this->assertStringContainsString('联系人姓名不能为空', $content);
            $this->assertStringContainsString('联系人电话不能为空', $content);
            $this->assertStringContainsString('完整地址不能为空', $content);
        }

        // 测试无效的手机号格式
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $form = $crawler->selectButton('Create')->form();

        $form['AddressInfo[name]'] = '测试用户';
        $form['AddressInfo[mobile]'] = '123456'; // 无效手机号
        $form['AddressInfo[fullAddressDetail]'] = '测试地址';

        $crawler = $client->submit($form);
        $this->assertSame(422, $client->getResponse()->getStatusCode());

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('请输入有效的手机号码', $content);
    }
}
