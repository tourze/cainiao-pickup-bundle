<?php

namespace CainiaoPickupBundle\Tests\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CainiaoHttpClientTest extends TestCase
{
    private HttpClientInterface|MockObject $httpClient;
    private LoggerInterface|MockObject $logger;
    private CainiaoHttpClient $cainiaoHttpClient;
    private PickupOrder $order;
    private CainiaoConfig $config;
    private ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        
        $this->cainiaoHttpClient = new CainiaoHttpClient(
            $this->httpClient,
            $this->logger
        );

        // 准备测试数据
        $this->config = new CainiaoConfig();
        $this->config->setName('测试配置')
            ->setAppKey('app_key')
            ->setAppSecret('app_secret')
            ->setProviderId('cainiao_provider')
            ->setAccessCode('access_code')
            ->setApiGateway('https://api.example.com/cainiao')
            ->setValid(true);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人')
            ->setMobile('13800138000')
            ->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人')
            ->setMobile('13900139000')
            ->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456')
            ->setSenderInfo($senderInfo)
            ->setReceiverInfo($receiverInfo)
            ->setItemType(ItemTypeEnum::DOCUMENT)
            ->setWeight(1.5)
            ->setConfig($this->config);
    }

    public function testPreQueryPickupService_withValidResponse(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'full' => false,
                'timeList' => [
                    [
                        'startTime' => '2023-08-01 09:00:00',
                        'endTime' => '2023-08-01 12:00:00',
                        'selectable' => true,
                    ],
                    [
                        'startTime' => '2023-08-01 14:00:00',
                        'endTime' => '2023-08-01 18:00:00',
                        'selectable' => true,
                    ],
                    [
                        'startTime' => '2023-08-01 18:00:00',
                        'endTime' => '2023-08-01 20:00:00',
                        'selectable' => false,
                    ],
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试
        $result = $this->cainiaoHttpClient->preQueryPickupService($this->order);

        // 断言
        $this->assertFalse($result['isFull']);
        $this->assertCount(2, $result['availableTimeSlots']);
        $this->assertEquals('2023-08-01 09:00:00', $result['availableTimeSlots'][0]['startTime']);
        $this->assertEquals('2023-08-01 12:00:00', $result['availableTimeSlots'][0]['endTime']);
        $this->assertEquals('2023-08-01 14:00:00', $result['availableTimeSlots'][1]['startTime']);
        $this->assertEquals('2023-08-01 18:00:00', $result['availableTimeSlots'][1]['endTime']);
    }

    public function testPreQueryPickupService_withInvalidResponse(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            // 缺少 data 字段
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 断言异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response data');

        // 执行测试
        $this->cainiaoHttpClient->preQueryPickupService($this->order);
    }

    public function testCreatePickupOrder_updatesOrderCorrectly(): void
    {
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'orderId' => 'CN123456',
                'mailNo' => 'SF123456789',
                'gotCode' => 'GOT123456',
                'cpCode' => 'SF',
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试
        $this->cainiaoHttpClient->createPickupOrder($this->order);

        // 断言
        $this->assertEquals('CN123456', $this->order->getCainiaoOrderCode());
        $this->assertEquals('SF123456789', $this->order->getMailNo());
        $this->assertEquals('GOT123456', $this->order->getOrderCode());
        $this->assertEquals('SF', $this->order->getCpCode());
    }

    public function testQueryOrderDetail_returnsCorrectData(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');
        
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'logisticsOrderCode' => 'CN123456',
                'mailNo' => 'SF123456789',
                'cpCode' => 'SF',
                'cpName' => '顺丰速运',
                'status' => '100',
                'courierInfo' => [
                    'name' => '张三',
                    'mobile' => '13777777777',
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试
        $result = $this->cainiaoHttpClient->queryOrderDetail($this->order);

        // 断言
        $this->assertIsArray($result);
        $this->assertEquals('CN123456', $result['logisticsOrderCode']);
        $this->assertEquals('SF123456789', $result['mailNo']);
        $this->assertEquals('SF', $result['cpCode']);
        $this->assertEquals('顺丰速运', $result['cpName']);
    }

    public function testCancelPickupOrder_updatesOrderStatusCorrectly(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');
        $this->order->setStatus(OrderStatusEnum::CREATE);
        
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => true,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试
        $this->cainiaoHttpClient->cancelPickupOrder($this->order, '用户取消');

        // 断言
        $this->assertEquals(OrderStatusEnum::CANCELLED, $this->order->getStatus());
        $this->assertEquals('用户取消', $this->order->getCancelReason());
    }

    public function testModifyPickupOrder_withSuccessResponse(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');
        
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => true,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试 - 不应抛出异常
        $this->cainiaoHttpClient->modifyPickupOrder($this->order);
        $this->addToAssertionCount(1); // 如果执行到这里，测试通过
    }

    public function testModifyPickupOrder_withFailureResponse(): void
    {
        // 准备测试数据
        $this->order->setCainiaoOrderCode('CN123456');
        
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => false,
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 断言异常
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('请求菜鸟修改失败');

        // 执行测试
        $this->cainiaoHttpClient->modifyPickupOrder($this->order);
    }

    public function testQueryLogisticsDetail_returnsCorrectData(): void
    {
        // 准备测试数据
        $this->order->setMailNo('SF123456789');
        $this->order->setOrderCode('TEST123456');
        
        // 模拟 HTTP 响应
        $responseData = [
            'success' => true,
            'data' => [
                'logisticsDetails' => [
                    [
                        'status' => '100',
                        'desc' => '已接单',
                        'time' => '2023-08-01 10:00:00',
                        'city' => '北京市',
                        'area' => '朝阳区',
                        'address' => '三里屯',
                        'courierInfo' => [
                            'name' => '张三',
                            'mobile' => '13777777777',
                        ],
                    ],
                ],
            ],
        ];

        // 配置模拟行为
        $this->response->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $this->response->method('toArray')->willReturn($responseData);
        
        $this->httpClient->method('request')
            ->willReturn($this->response);

        // 执行测试
        $result = $this->cainiaoHttpClient->queryLogisticsDetail($this->order);

        // 断言
        $this->assertIsArray($result);
        $this->assertArrayHasKey('logisticsDetails', $result);
        $this->assertCount(1, $result['logisticsDetails']);
        $this->assertEquals('100', $result['logisticsDetails'][0]['status']);
        $this->assertEquals('已接单', $result['logisticsDetails'][0]['desc']);
    }
} 