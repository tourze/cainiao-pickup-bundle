<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\ConfigurationException;
use CainiaoPickupBundle\Exception\OrderCancellationFailedException;
use CainiaoPickupBundle\Exception\OrderCannotBeCancelledException;
use CainiaoPickupBundle\Exception\OrderModificationFailedException;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

#[WithMonologChannel(channel: 'cainiao_pickup')]
readonly class PickupService
{
    /**
     * @param PickupOrderRepository $pickupOrderRepository
     * @param CainiaoConfigRepository $cainiaoConfigRepository
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PickupOrderRepository $pickupOrderRepository,
        private LoggerInterface $logger,
        private CainiaoHttpClient $cainiaoHttpClient,
        private CainiaoConfigRepository $cainiaoConfigRepository,
    ) {
    }

    /**
     * 创建取件订单
     * @param array<string, mixed> $data
     */
    public function createPickupOrder(array $data): PickupOrder
    {
        $config = $this->getValidConfig();
        $senderInfo = $this->createAddressInfo($data, 'sender');
        $receiverInfo = $this->createAddressInfo($data, 'receiver');

        $order = $this->buildPickupOrder($data, $config, $senderInfo, $receiverInfo);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->cainiaoHttpClient->createPickupOrder($order);
        $this->entityManager->flush();

        $this->logOrderCreated($order);

        return $order;
    }

    private function getValidConfig(): CainiaoConfig
    {
        $config = $this->cainiaoConfigRepository->findValidConfig();
        if (null === $config) {
            throw new ConfigurationException('No valid Cainiao config found');
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createAddressInfo(array $data, string $type): AddressInfo
    {
        $addressInfo = new AddressInfo();
        $prefix = 'sender' === $type ? 'sender' : 'receiver';
        $fullAddress = 'sender' === $type ? 'senderFullAddress' : 'receiverFullAddress';

        $name = $data[$prefix . 'Name'] ?? '';
        $phone = $data[$prefix . 'Phone'] ?? '';
        $fullAddressDetail = $data[$fullAddress] ?? '';

        if (!is_string($name) || !is_string($phone) || !is_string($fullAddressDetail)) {
            throw new \InvalidArgumentException('Address fields must be strings');
        }

        $addressInfo->setName($name);
        $addressInfo->setMobile($phone);
        $addressInfo->setFullAddressDetail($fullAddressDetail);

        $this->setOptionalAddressFields($addressInfo, $data, $prefix);

        return $addressInfo;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOptionalAddressFields(AddressInfo $addressInfo, array $data, string $prefix): void
    {
        $this->setAddressField($addressInfo, $data, $prefix . 'City', 'setCityName');
        $this->setAddressField($addressInfo, $data, $prefix . 'Province', 'setProvinceName');
        $this->setAddressField($addressInfo, $data, $prefix . 'Area', 'setAreaName');
        $this->setAddressField($addressInfo, $data, $prefix . 'Address', 'setAddress');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setAddressField(AddressInfo $addressInfo, array $data, string $key, string $setter): void
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            return;
        }

        match ($setter) {
            'setCityName' => $addressInfo->setCityName($data[$key]),
            'setProvinceName' => $addressInfo->setProvinceName($data[$key]),
            'setAreaName' => $addressInfo->setAreaName($data[$key]),
            'setAddress' => $addressInfo->setAddress($data[$key]),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPickupOrder(array $data, CainiaoConfig $config, AddressInfo $senderInfo, AddressInfo $receiverInfo): PickupOrder
    {
        $order = new PickupOrder();
        $order->setOrderCode($this->generateOrderCode());
        $order->setSenderInfo($senderInfo);
        $order->setReceiverInfo($receiverInfo);
        $order->setItemType(ItemTypeEnum::from(is_int($data['itemType'] ?? 1) || is_string($data['itemType'] ?? 1) ? $data['itemType'] ?? 1 : 1));
        $order->setWeight(is_numeric($data['weight']) ? (float) $data['weight'] : 0.0);
        $order->setRemark(isset($data['remark']) && is_string($data['remark']) ? $data['remark'] : null);
        $order->setConfig($config);

        $this->setOrderOptionalFields($order, $data);

        return $order;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOrderOptionalFields(PickupOrder $order, array $data): void
    {
        $this->setPickupTimeFields($order, $data);
        $this->setOrderStringField($order, $data, 'itemQuantity', 'setItemQuantity');
        $this->setOrderNumericField($order, $data, 'itemValue', 'setItemValue');
        $this->setOrderStringField($order, $data, 'externalUserId', 'setExternalUserId');
        $this->setOrderStringField($order, $data, 'externalUserMobile', 'setExternalUserMobile');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setPickupTimeFields(PickupOrder $order, array $data): void
    {
        if (isset($data['expectPickupTimeStart'], $data['expectPickupTimeEnd']) && '' !== $data['expectPickupTimeStart'] && '' !== $data['expectPickupTimeEnd']) {
            $expectStart = $data['expectPickupTimeStart'];
            $expectEnd = $data['expectPickupTimeEnd'];
            if (is_string($expectStart) && is_string($expectEnd)) {
                $order->setExpectPickupTimeStart($expectStart);
                $order->setExpectPickupTimeEnd($expectEnd);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOrderStringField(PickupOrder $order, array $data, string $key, string $setter): void
    {
        if (!(isset($data[$key]) && '' !== $data[$key] && is_string($data[$key]))) {
            return;
        }

        match ($setter) {
            'setItemQuantity' => $order->setItemQuantity($data[$key]),
            'setExternalUserId' => $order->setExternalUserId($data[$key]),
            'setExternalUserMobile' => $order->setExternalUserMobile($data[$key]),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOrderNumericField(PickupOrder $order, array $data, string $key, string $setter): void
    {
        if (!isset($data[$key])) {
            return;
        }

        $value = $data[$key];
        if ('' === $value || !is_numeric($value)) {
            return;
        }

        match ($setter) {
            'setItemValue' => $order->setItemValue((float) $value),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    private function logOrderCreated(PickupOrder $order): void
    {
        $this->logger->info('Created pickup order', [
            'orderCode' => $order->getOrderCode(),
            'cainiaoOrderCode' => $order->getCainiaoOrderCode(),
            'mailNo' => $order->getMailNo(),
        ]);
    }

    /**
     * 更新订单状态
     */
    public function updateOrderStatus(PickupOrder $order, OrderStatusEnum $status): PickupOrder
    {
        $order->setStatus($status);
        $this->entityManager->flush();

        $this->logger->info('Updated pickup order status', [
            'orderCode' => $order->getOrderCode(),
            'status' => $status->value,
        ]);

        return $order;
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetail(string $orderCode): ?PickupOrder
    {
        return $this->pickupOrderRepository->findByOrderCode($orderCode);
    }

    /**
     * 获取指定状态的订单列表
     * @return PickupOrder[]
     */
    public function getOrdersByStatus(OrderStatusEnum $status): array
    {
        return $this->pickupOrderRepository->findByStatus($status);
    }

    /**
     * 取消取件订单
     *
     * @throws OrderCannotBeCancelledException  当订单状态不允许取消时
     * @throws OrderCancellationFailedException 当取消订单失败时
     */
    public function cancelPickupOrder(PickupOrder $order, string $reason): PickupOrder
    {
        if (!in_array($order->getStatus(), [OrderStatusEnum::CREATE, OrderStatusEnum::WAREHOUSE_ACCEPT], true)) {
            throw new OrderCannotBeCancelledException(sprintf('Order %s cannot be cancelled, current status: %s', $order->getOrderCode(), $order->getStatus()->value));
        }

        $this->cainiaoHttpClient->cancelPickupOrder($order, $reason);

        $order->setStatus(OrderStatusEnum::CANCELLED);
        $order->setCancelReason($reason);
        $order->setCancelTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->logger->info('Order cancelled successfully', [
            'orderCode' => $order->getOrderCode(),
            'reason' => $reason,
            'status' => OrderStatusEnum::CANCELLED->value,
        ]);

        return $order;
    }

    /**
     * 修改取件订单
     * @param array<string, mixed> $data
     * @throws OrderModificationFailedException 当订单状态不允许修改时
     */
    public function modifyPickupOrder(PickupOrder $order, array $data): PickupOrder
    {
        $this->validateOrderCanBeModified($order);

        $this->updateOrderFromData($order, $data);
        $this->cainiaoHttpClient->modifyPickupOrder($order);
        $this->entityManager->flush();

        $this->logOrderModified($order);

        return $order;
    }

    private function validateOrderCanBeModified(PickupOrder $order): void
    {
        if (!in_array($order->getStatus(), [OrderStatusEnum::CREATE, OrderStatusEnum::WAREHOUSE_ACCEPT], true)) {
            throw new OrderModificationFailedException(sprintf('Order %s cannot be modified, current status: %s', $order->getOrderCode(), $order->getStatus()->value));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateOrderFromData(PickupOrder $order, array $data): void
    {
        $this->updateSenderInfo($order, $data);
        $this->updateReceiverInfo($order, $data);
        $this->updateItemInfo($order, $data);
        $this->updatePickupTime($order, $data);
        $this->updateRemark($order, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateSenderInfo(PickupOrder $order, array $data): void
    {
        if (isset($data['senderName'], $data['senderPhone'], $data['senderAddress'])) {
            $senderName = $data['senderName'];
            $senderPhone = $data['senderPhone'];
            $senderAddress = $data['senderAddress'];

            if (is_string($senderName) && is_string($senderPhone) && is_string($senderAddress)) {
                $senderInfo = $order->getSenderInfo();
                $senderInfo->setName($senderName);
                $senderInfo->setMobile($senderPhone);
                $senderInfo->setFullAddressDetail($senderAddress);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateReceiverInfo(PickupOrder $order, array $data): void
    {
        if (isset($data['receiverName'], $data['receiverPhone'], $data['receiverAddress'])) {
            $receiverName = $data['receiverName'];
            $receiverPhone = $data['receiverPhone'];
            $receiverAddress = $data['receiverAddress'];

            if (is_string($receiverName) && is_string($receiverPhone) && is_string($receiverAddress)) {
                $receiverInfo = $order->getReceiverInfo();
                $receiverInfo->setName($receiverName);
                $receiverInfo->setMobile($receiverPhone);
                $receiverInfo->setFullAddressDetail($receiverAddress);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateItemInfo(PickupOrder $order, array $data): void
    {
        $this->updateItemType($order, $data);
        $this->updateOrderNumericField($order, $data, 'weight', 'setWeight');
        $this->updateOrderStringField($order, $data, 'itemQuantity', 'setItemQuantity');
        $this->updateOrderNumericField($order, $data, 'itemValue', 'setItemValue');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateItemType(PickupOrder $order, array $data): void
    {
        if (isset($data['itemType'])) {
            $itemType = $data['itemType'];
            if (is_int($itemType) || is_string($itemType)) {
                $order->setItemType(ItemTypeEnum::from($itemType));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateOrderStringField(PickupOrder $order, array $data, string $key, string $setter): void
    {
        if (!(isset($data[$key]) && is_string($data[$key]))) {
            return;
        }

        match ($setter) {
            'setItemQuantity' => $order->setItemQuantity($data[$key]),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateOrderNumericField(PickupOrder $order, array $data, string $key, string $setter): void
    {
        if (!(isset($data[$key]) && is_numeric($data[$key]))) {
            return;
        }

        match ($setter) {
            'setWeight' => $order->setWeight((float) $data[$key]),
            'setItemValue' => $order->setItemValue((float) $data[$key]),
            default => throw new \InvalidArgumentException("Unknown setter: {$setter}"),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updatePickupTime(PickupOrder $order, array $data): void
    {
        if (isset($data['expectPickupTimeStart'], $data['expectPickupTimeEnd'])) {
            $expectStart = $data['expectPickupTimeStart'];
            $expectEnd = $data['expectPickupTimeEnd'];
            if (is_string($expectStart) && is_string($expectEnd)) {
                $order->setExpectPickupTimeStart($expectStart);
                $order->setExpectPickupTimeEnd($expectEnd);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateRemark(PickupOrder $order, array $data): void
    {
        if (isset($data['remark'])) {
            $remark = $data['remark'];
            if (is_string($remark)) {
                $order->setRemark($remark);
            }
        }
    }

    private function logOrderModified(PickupOrder $order): void
    {
        $this->logger->info('Modified pickup order', [
            'orderCode' => $order->getOrderCode(),
            'cainiaoOrderCode' => $order->getCainiaoOrderCode(),
        ]);
    }

    /**
     * 生成订单号
     */
    private function generateOrderCode(): string
    {
        return 'PK' . date('YmdHis') . rand(1000, 9999);
    }
}
