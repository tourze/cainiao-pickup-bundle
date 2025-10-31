# CainiaoPickupBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP 版本](https://img.shields.io/packagist/php-v/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![最新版本](https://img.shields.io/packagist/v/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![许可证](https://img.shields.io/packagist/l/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![总下载量](https://img.shields.io/packagist/dt/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![构建状态](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![代码覆盖率](https://img.shields.io/badge/coverage-85%25-brightgreen?style=flat-square)](https://github.com/tourze/php-monorepo/actions)

一个用于集成菜鸟驿站上门取件服务的 Symfony bundle，提供取件订单管理、物流跟踪和全面的订单生命周期管理功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [依赖关系](#依赖关系)
- [快速开始](#快速开始)
- [控制台命令](#控制台命令)
- [配置说明](#配置说明)
- [高级用法](#高级用法)
- [异常处理](#异常处理)
- [安全性](#安全性)
- [贡献](#贡献)
- [测试](#测试)
- [许可证](#许可证)
- [支持](#支持)
- [致谢](#致谢)

## 功能特性

- 🚚 完整的取件订单管理（创建、修改、取消）
- 📦 支持多种物品类型（文件、包裹等）
- 📍 详细的寄件人和收件人地址管理
- 🔄 实时订单状态跟踪（20+ 种状态类型）
- 📊 物流详情查询和跟踪
- 🏭 支持多配置管理不同的仓库/服务商
- 🔒 安全的 API 通信和签名验证
- 🎯 内置各种 API 场景的异常处理
- 🛠️ Symfony 控制台命令支持批量操作
- 📝 基于 Doctrine ORM 的完整实体关系

## 安装

```bash
composer require tourze/cainiao-pickup-bundle
```

安装后，在 `config/bundles.php` 中注册 bundle：

```php
return [
    // ...
    CainiaoPickupBundle\CainiaoPickupBundle::class => ['all' => true],
];
```

## 依赖关系

此包需要以下依赖：

### PHP 要求
- PHP 8.1 或更高版本
- SPL、date、json、random 扩展

### Symfony 要求
- Symfony 7.3 或更高版本
- Doctrine ORM 3.0+
- Doctrine DBAL 4.0+
- Symfony HttpClient 组件

### 内部依赖
- `tourze/doctrine-indexed-bundle`
- `tourze/doctrine-snowflake-bundle`
- `tourze/doctrine-timestamp-bundle`
- `tourze/doctrine-track-bundle`
- `tourze/doctrine-user-bundle`
- `tourze/enum-extra`

所有依赖都由 Composer 自动管理。

## 快速开始

### 1. 配置菜鸟 API 凭证

首先，在数据库中创建菜鸟配置：

```php
use CainiaoPickupBundle\Entity\CainiaoConfig;

$config = new CainiaoConfig();
$config->setName('主仓库')
    ->setAppKey('your_app_key')
    ->setAppSecret('your_app_secret')
    ->setAccessCode('your_access_code')
    ->setProviderId('your_provider_id')
    ->setApiGateway('https://gateway.cainiao.com/gateway/api');

$entityManager->persist($config);
$entityManager->flush();
```

### 2. 创建取件订单

```php
use CainiaoPickupBundle\Service\PickupService;
use CainiaoPickupBundle\Enum\ItemTypeEnum;

$pickupService = $container->get(PickupService::class);

$orderData = [
    // 寄件人信息
    'senderName' => '张三',
    'senderPhone' => '13800138000',
    'senderFullAddress' => '北京市朝阳区某街道123号A座',
    'senderProvince' => '北京市',
    'senderCity' => '北京市',
    'senderArea' => '朝阳区',
    
    // 收件人信息
    'receiverName' => '李四',
    'receiverPhone' => '13900139000',
    'receiverFullAddress' => '上海市浦东新区某大道456号B座',
    'receiverProvince' => '上海市',
    'receiverCity' => '上海市',
    'receiverArea' => '浦东新区',
    
    // 物品详情
    'itemType' => ItemTypeEnum::PACKAGE->value,
    'weight' => 2.5, // 单位：公斤
    'itemQuantity' => 1,
    'itemValue' => 100.00,
    'remark' => '易碎物品',
    
    // 预约取件时间窗口（可选）
    'expectPickupTimeStart' => '2024-01-20 14:00:00',
    'expectPickupTimeEnd' => '2024-01-20 18:00:00',
];

$order = $pickupService->createPickupOrder($orderData);

echo "订单创建成功: " . $order->getOrderCode();
echo "运单号: " . $order->getMailNo();
```

### 3. 跟踪订单状态

```php
// 根据订单号获取订单
$order = $pickupService->getOrderDetail('PK20240120123456');

// 检查当前状态
echo "当前状态: " . $order->getStatus()->getLabel();

// 获取物流详情
$cainiaoHttpClient = $container->get(CainiaoHttpClient::class);
$logistics = $cainiaoHttpClient->queryLogisticsDetail($order);

foreach ($logistics['logisticsDetails'] as $detail) {
    echo $detail['time'] . ' - ' . $detail['desc'] . PHP_EOL;
}
```

### 4. 取消订单

```php
try {
    $cancelledOrder = $pickupService->cancelPickupOrder($order, '客户要求取消');
    echo "订单取消成功";
} catch (OrderCannotBeCancelledException $e) {
    echo "无法取消订单: " . $e->getMessage();
}
```

## 控制台命令

### 同步取件订单

从菜鸟 API 同步取件订单详情：

```bash
# 同步所有未完成的订单
bin/console cainiao:pickup:sync-orders

# 根据订单号同步指定订单
bin/console cainiao:pickup:sync-orders --order-code="PK20240120123456"
```

该命令将：
- 从菜鸟 API 更新订单状态和详情
- 如果未指定订单号，则同步所有未完成的订单
- 在批量操作中优雅地处理单个订单的错误

## 同步物流详情

同步取件订单的物流跟踪信息：

```bash
# 同步所有仓库已确认状态的订单物流信息
bin/console cainiao:pickup:sync-logistics

# 同步指定订单的物流信息
bin/console cainiao:pickup:sync-logistics --order-code="PK20240120123456"
```

该命令将：
- 从菜鸟 API 查询并更新物流跟踪详情
- 使用最新数据替换现有的物流详情
- 包含可用的快递员信息
- 如果未指定订单号，则处理所有仓库已确认的订单

## 配置说明

### 实体关系

本 bundle 提供以下主要实体：

- **CainiaoConfig**: 不同仓库/服务商的 API 配置
- **PickupOrder**: 主订单实体，具有完整的生命周期跟踪
- **AddressInfo**: 寄件人/收件人地址信息的嵌入式值对象
- **LogisticsDetail**: 订单的物流跟踪信息

### 订单状态类型

本 bundle 支持 20+ 种订单状态，包括：

- `CREATE` (0): 已下单
- `WAREHOUSE_ACCEPT` (100): 仓库已接单
- `ACCEPT` (400): 已揽件
- `TRANSPORT` (500): 运输中
- `DELIVERING` (600): 派送中
- `SIGN` (1000): 已签收
- `CANCELLED`: 已取消

### 物品类型

支持的物品类型：

- `DOC`: 文件
- `SPX`: 小包裹
- `PACKAGE`: 标准包裹
- `OTHER`: 其他物品

## 高级用法

### 预查询服务可用性

```php
$availability = $cainiaoHttpClient->preQueryPickupService($order);

if ($availability['isFull']) {
    echo "请求的时间段服务已满";
} else {
    echo "可用时间段:";
    foreach ($availability['availableTimeSlots'] as $slot) {
        echo $slot['startTime'] . ' - ' . $slot['endTime'] . PHP_EOL;
    }
}
```

### 修改现有订单

```php
$modificationData = [
    'weight' => 3.0, // 更新重量
    'remark' => '更新：请格外小心处理',
    'expectPickupTimeStart' => '2024-01-21 09:00:00',
    'expectPickupTimeEnd' => '2024-01-21 12:00:00',
];

try {
    $modifiedOrder = $pickupService->modifyPickupOrder($order, $modificationData);
    echo "订单修改成功";
} catch (OrderModificationFailedException $e) {
    echo "无法修改订单: " . $e->getMessage();
}
```

### 按状态查询订单

```php
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;

$orderRepository = $container->get(PickupOrderRepository::class);

// 获取所有运输中的订单
$ordersInTransit = $orderRepository->findByStatus(OrderStatusEnum::TRANSPORT);

// 获取所有已签收的订单
$deliveredOrders = $orderRepository->findByStatus(OrderStatusEnum::SIGN);

// 获取所有未完成的订单
$unfinishedOrders = $orderRepository->findUnfinishedOrders();
```

## 异常处理

本 bundle 为不同场景提供了特定的异常：

- `ConfigurationException`: 当菜鸟 API 配置缺失或无效时
- `CainiaoApiException`: 通用 API 通信错误
- `InvalidResponseException`: 当 API 返回意外的响应格式时
- `OrderNotFoundException`: 当请求的订单不存在时
- `OrderCannotBeCancelledException`: 当订单状态不允许取消时
- `OrderCancellationFailedException`: 当取消订单请求失败时
- `OrderModificationFailedException`: 当修改订单请求失败时

## 安全性

### API 安全

此包实现了多种安全措施：

- **签名验证**: 所有发送到菜鸟的 API 请求都使用 HMAC-SHA256 进行签名
- **HTTPS 通信**: 所有 API 调用都通过 HTTPS 进行
- **访问令牌管理**: 安全存储和处理 API 凭证
- **输入验证**: 所有用户输入都使用 Symfony 的验证组件进行验证

### 数据保护

- **敏感数据**: API 密钥和密码在数据库中安全存储
- **日志记录**: 默认情况下，敏感信息不会记录在日志中
- **数据加密**: 考虑对静态的敏感配置数据进行加密

### 最佳实践

1. **凭证管理**:
    - 将 API 凭证存储在环境变量或安全配置中
    - 定期轮换 API 密钥
    - 为不同环境使用不同的凭证

2. **网络安全**:
    - 对所有 API 通信使用 HTTPS
    - 实施速率限制以防止 API 滥用
    - 监控 API 使用情况是否有异常模式

3. **数据库安全**:
    - 使用适当的数据库权限
    - 如有需要，加密敏感数据列
    - 定期对存储数据进行安全审计

如果您发现安全漏洞，请发送电子邮件至 security@tourze.com，而不是使用问题跟踪器。

## 贡献

有关提交补丁和贡献工作流程的详细信息，请参阅 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/cainiao-pickup-bundle/tests

# 运行特定测试套件
./vendor/bin/phpunit packages/cainiao-pickup-bundle/tests/Service
```

## 许可证

MIT 许可证 (MIT)。详细信息请参阅[许可证文件](LICENSE)。

## 支持

对于错误和功能请求，请使用 [issue tracker](https://github.com/tourze/cainiao-pickup-bundle/issues)。

## 致谢

- [Tourze 团队](https://github.com/tourze)
- [所有贡献者](../../contributors)