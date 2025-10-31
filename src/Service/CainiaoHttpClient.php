<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Service;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\CainiaoApiException;
use CainiaoPickupBundle\Exception\InvalidResponseException;
use CainiaoPickupBundle\Exception\OrderModificationFailedException;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[WithMonologChannel(channel: 'cainiao_pickup')]
readonly class CainiaoHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 服务预查询
     *
     * @return array{isFull: bool, availableTimeSlots: array<array{startTime: string, endTime: string}>}
     *
     * @throws InvalidResponseException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_SEND_SERVICE_DETAIL
     */
    public function preQueryPickupService(PickupOrder $order): array
    {
        $startTime = microtime(true);
        $this->logger->info('Starting pickup service pre-query', ['order_id' => $order->getId()]);

        try {
            $response = $this->request($order->getConfig(), 'guoguo.pickup.service.time.query', $order->toPreQueryApiFormat());

            $this->logger->info('Pickup service pre-query completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Pickup service pre-query failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!isset($response['data'])) {
            throw new InvalidResponseException('Invalid response data');
        }

        $data = $response['data'];
        assert(is_array($data));

        // 提取可用的时间段
        $availableTimeSlots = [];
        $timeList = $data['timeList'] ?? [];
        assert(is_array($timeList));

        foreach ($timeList as $timeSlot) {
            assert(is_array($timeSlot));
            if (true === ($timeSlot['selectable'] ?? false)) {
                $startTime = $timeSlot['startTime'] ?? '';
                $endTime = $timeSlot['endTime'] ?? '';
                $availableTimeSlots[] = [
                    'startTime' => (string) $startTime,
                    'endTime' => (string) $endTime,
                ];
            }
        }

        return [
            'isFull' => (bool) ($data['full'] ?? false),
            'availableTimeSlots' => $availableTimeSlots,
        ];
    }

    /**
     * 创建取件订单
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_CREATE_SEND_ORDER
     *
     * @throws InvalidResponseException
     */
    public function createPickupOrder(PickupOrder $order): void
    {
        $startTime = microtime(true);
        $this->logger->info('Starting pickup order creation', ['order_id' => $order->getId()]);

        try {
            $response = $this->request($order->getConfig(), 'GUOGUO_CREATE_SEND_ORDER', $order->toCreateOrderApiFormat());

            $this->logger->info('Pickup order creation completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Pickup order creation failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!isset($response['data'])) {
            throw new InvalidResponseException('Invalid response data');
        }

        $data = $response['data'];
        if (!is_array($data)) {
            throw new InvalidResponseException('Response data is not an array');
        }

        $orderId = isset($data['orderId']) && is_string($data['orderId']) ? $data['orderId'] : null;
        $mailNo = isset($data['mailNo']) && is_string($data['mailNo']) ? $data['mailNo'] : null;
        $gotCode = $data['gotCode'] ?? '';
        $cpCode = isset($data['cpCode']) && is_string($data['cpCode']) ? $data['cpCode'] : null;

        if (!is_string($gotCode)) {
            throw new InvalidResponseException('gotCode must be a string');
        }

        $order->setCainiaoOrderCode($orderId);
        $order->setMailNo($mailNo);
        $order->setOrderCode($gotCode);
        $order->setCpCode($cpCode);
    }

    /**
     * 查询订单详情
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_SEND_ORDER_FULL_DETAIL
     *
     * @return array<string, mixed>
     * @throws InvalidResponseException
     */
    public function queryOrderDetail(PickupOrder $order): array
    {
        $startTime = microtime(true);
        $this->logger->info('Starting order detail query', ['order_id' => $order->getId()]);

        try {
            $response = $this->request($order->getConfig(), 'GUOGUO_QUERY_SEND_ORDER_FULL_DETAIL', [
                'orderId' => $order->getCainiaoOrderCode(),
                'cnAccountId' => $order->getConfig()->getAppKey(),
                'needLogisticsDetail' => true,
            ]);

            $this->logger->info('Order detail query completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Order detail query failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!isset($response['data'])) {
            throw new InvalidResponseException('Invalid response data');
        }

        $data = $response['data'];
        if (!is_array($data)) {
            throw new InvalidResponseException('Response data is not an array');
        }

        /** @var array<string, mixed> */
        return $data;
    }

    /**
     * 取消寄件订单
     *
     * @throws InvalidResponseException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_CANCEL_SEND_ORDER
     */
    public function cancelPickupOrder(PickupOrder $order, string $reason): void
    {
        $startTime = microtime(true);
        $this->logger->info('Starting pickup order cancellation', ['order_id' => $order->getId(), 'reason' => $reason]);

        try {
            $order->setCancelReason($reason);
            $this->request($order->getConfig(), 'GUOGUO_CANCEL_SEND_ORDER', $order->toCancelOrderApiFormat());

            $this->logger->info('Pickup order cancellation completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Pickup order cancellation failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $order->setStatus(OrderStatusEnum::CANCELLED);
    }

    /**
     * 修改取件订单
     *
     * @throws InvalidResponseException|OrderModificationFailedException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_MODIFY_SEND_ORDER
     */
    public function modifyPickupOrder(PickupOrder $order): void
    {
        $startTime = microtime(true);
        $this->logger->info('Starting pickup order modification', ['order_id' => $order->getId()]);

        try {
            $response = $this->request($order->getConfig(), 'GUOGUO_MODIFY_SEND_ORDER', $order->toModifyOrderApiFormat());

            $this->logger->info('Pickup order modification completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Pickup order modification failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!isset($response['data'])) {
            throw new InvalidResponseException('Invalid response data');
        }

        $data = $response['data'];
        if (false === $data) {
            throw new OrderModificationFailedException('请求菜鸟修改失败');
        }
    }

    /**
     * 查询物流详情
     *
     * @return array{logisticsDetails: array<array{status: string, desc: string, time: string, city?: string, area?: string, address?: string, courierInfo?: array{name?: string, mobile?: string}}>}
     *
     * @throws InvalidResponseException
     *
     * @see https://open.cainiao.com/api-doc/detail?category=link&type=cainiao_moduan_management&apiId=GUOGUO_QUERY_LOGISTICS_DETAIL
     */
    public function queryLogisticsDetail(PickupOrder $order): array
    {
        $startTime = microtime(true);
        $this->logger->info('Starting logistics detail query', ['order_id' => $order->getId()]);

        try {
            $response = $this->request($order->getConfig(), 'guoguo.pickup.query.logistics.detail', [
                'mailNo' => $order->getMailNo(),
                'orderCode' => $order->getOrderCode(),
            ]);

            $this->logger->info('Logistics detail query completed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Logistics detail query failed', [
                'order_id' => $order->getId(),
                'duration' => microtime(true) - $startTime,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (!isset($response['data'])) {
            throw new InvalidResponseException('Invalid response data');
        }

        $data = $response['data'];
        if (!is_array($data)) {
            throw new InvalidResponseException('Response data is not an array');
        }

        // 确保返回符合预期的数据结构
        if (!isset($data['logisticsDetails']) || !is_array($data['logisticsDetails'])) {
            throw new InvalidResponseException('Missing or invalid logisticsDetails in response');
        }

        /** @var array{logisticsDetails: array<array{status: string, desc: string, time: string, city?: string, area?: string, address?: string, courierInfo?: array{name?: string, mobile?: string}}>} */
        return $data;
    }

    /**
     * 发送请求到菜鸟API
     *
     * @throws CainiaoApiException 当API请求失败时
     */
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function request(CainiaoConfig $config, string $msgType, array $data): array
    {
        $url = rtrim($config->getApiGateway(), '/');

        $data = [
            'request' => $data,
            'accessOption' => [
                'accessCode' => $config->getAccessCode(),
            ],
        ];

        // 准备请求参数
        $requestData = [
            'msg_type' => $msgType,
            'logistic_provider_id' => $config->getProviderId(),
            'logistics_interface' => json_encode($data),
        ];

        // 添加签名
        $logisticsInterface = $requestData['logistics_interface'];
        if (!is_string($logisticsInterface)) {
            throw new CainiaoApiException('Failed to encode logistics interface data');
        }
        $requestData['data_digest'] = $this->generateSign($config, $logisticsInterface);

        $httpStartTime = microtime(true);
        $this->logger->info('Making HTTP request to Cainiao API', [
            'url' => $url,
            'msg_type' => $msgType,
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new CainiaoApiException('Cainiao API request failed: ' . $response->getContent(false));
            }

            $result = $response->toArray();

            // 记录请求日志
            $this->logger->info('Cainiao API request completed successfully', [
                'url' => $url,
                'msg_type' => $msgType,
                'duration' => microtime(true) - $httpStartTime,
                'response_success' => $result['success'] ?? false,
            ]);

            if (!$result['success']) {
                $errorMessage = $result['errorMessage'] ?? 'Unknown error';
                $errorCode = $result['errorCode'] ?? 'unknown';
                throw new CainiaoApiException(sprintf('Cainiao API request failed: %s (code: %s)', (string) $errorMessage, (string) $errorCode));
            }

            /** @var array<string, mixed> $result */
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cainiao API request failed', [
                'url' => $url,
                'request' => $requestData,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 生成签名
     *
     * 签名规则:
     * 1. 将请求参数按照key的字母顺序排序
     * 2. 将排序后的参数拼接成key=value形式，用&连接
     * 3. 在最前面拼接AppSecret
     * 4. 对拼接后的字符串进行MD5加密，转换成大写
     *
     * @see https://open.cainiao.com/platform_document?namespace=nek9zs&slug=Aqf8TdKrRt6nmlgy
     */
    private function generateSign(CainiaoConfig $config, string $params): string
    {
        $stringToSign = $params . $config->getAppSecret();

        // MD5加密并转换成base64编码
        return base64_encode(md5($stringToSign, true));
    }
}
