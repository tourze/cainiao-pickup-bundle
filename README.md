# CainiaoPickupBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/packagist/php-v/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![Latest Version](https://img.shields.io/packagist/v/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![License](https://img.shields.io/packagist/l/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/cainiao-pickup-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/cainiao-pickup-bundle)
[![Code Coverage](https://img.shields.io/badge/coverage-85%25-brightgreen?style=flat-square)](https://github.com/tourze/php-monorepo/actions)

A Symfony bundle for integrating with Cainiao pickup service, providing pickup order management, logistics tracking, and comprehensive order lifecycle management.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Dependencies](#dependencies)
- [Quick Start](#quick-start)
- [Console Commands](#console-commands)
- [Configuration](#configuration)
- [Advanced Usage](#advanced-usage)
- [Exception Handling](#exception-handling)
- [Security](#security)
- [Contributing](#contributing)
- [Testing](#testing)
- [License](#license)
- [Support](#support)
- [Credits](#credits)

## Features

- 🚚 Complete pickup order management (create, modify, cancel)
- 📦 Support for multiple item types (documents, packages, etc.)
- 📍 Detailed address management for senders and receivers
- 🔄 Real-time order status tracking (20+ status types)
- 📊 Logistics detail querying and tracking
- 🏭 Multi-configuration support for different warehouses/providers
- 🔒 Secure API communication with signature verification
- 🎯 Built-in exception handling for various API scenarios
- 🛠️ Symfony console commands for batch operations
- 📝 Comprehensive entity relationships with Doctrine ORM

## Installation

```bash
composer require tourze/cainiao-pickup-bundle
```

After installation, register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    CainiaoPickupBundle\CainiaoPickupBundle::class => ['all' => true],
];
```

## Dependencies

This bundle requires the following:

### PHP Requirements
- PHP 8.1 or higher
- SPL, date, json, random extensions

### Symfony Requirements
- Symfony 7.3 or higher
- Doctrine ORM 3.0+
- Doctrine DBAL 4.0+
- Symfony HttpClient component

### Internal Dependencies
- `tourze/doctrine-indexed-bundle`
- `tourze/doctrine-snowflake-bundle`
- `tourze/doctrine-timestamp-bundle`
- `tourze/doctrine-track-bundle`
- `tourze/doctrine-user-bundle`
- `tourze/enum-extra`

All dependencies are automatically managed by Composer.

## Quick Start

### 1. Configure Cainiao API Credentials

First, create a Cainiao configuration in your database:

```php
use CainiaoPickupBundle\Entity\CainiaoConfig;

$config = new CainiaoConfig();
$config->setName('Main Warehouse')
    ->setAppKey('your_app_key')
    ->setAppSecret('your_app_secret')
    ->setAccessCode('your_access_code')
    ->setProviderId('your_provider_id')
    ->setApiGateway('https://gateway.cainiao.com/gateway/api');

$entityManager->persist($config);
$entityManager->flush();
```

### 2. Create a Pickup Order

```php
use CainiaoPickupBundle\Service\PickupService;
use CainiaoPickupBundle\Enum\ItemTypeEnum;

$pickupService = $container->get(PickupService::class);

$orderData = [
    // Sender information
    'senderName' => 'John Doe',
    'senderPhone' => '13800138000',
    'senderFullAddress' => 'Building A, 123 Street, Beijing',
    'senderProvince' => 'Beijing',
    'senderCity' => 'Beijing',
    'senderArea' => 'Chaoyang District',
    
    // Receiver information
    'receiverName' => 'Jane Smith',
    'receiverPhone' => '13900139000',
    'receiverFullAddress' => 'Building B, 456 Avenue, Shanghai',
    'receiverProvince' => 'Shanghai',
    'receiverCity' => 'Shanghai',
    'receiverArea' => 'Pudong District',
    
    // Item details
    'itemType' => ItemTypeEnum::PACKAGE->value,
    'weight' => 2.5, // in kg
    'itemQuantity' => 1,
    'itemValue' => 100.00,
    'remark' => 'Fragile items',
    
    // Pickup time window (optional)
    'expectPickupTimeStart' => '2024-01-20 14:00:00',
    'expectPickupTimeEnd' => '2024-01-20 18:00:00',
];

$order = $pickupService->createPickupOrder($orderData);

echo "Order created: " . $order->getOrderCode();
echo "Tracking number: " . $order->getMailNo();
```

### 3. Track Order Status

```php
// Get order by code
$order = $pickupService->getOrderDetail('PK20240120123456');

// Check current status
echo "Current status: " . $order->getStatus()->getLabel();

// Get logistics details
$cainiaoHttpClient = $container->get(CainiaoHttpClient::class);
$logistics = $cainiaoHttpClient->queryLogisticsDetail($order);

foreach ($logistics['logisticsDetails'] as $detail) {
    echo $detail['time'] . ' - ' . $detail['desc'] . PHP_EOL;
}
```

### 4. Cancel an Order

```php
try {
    $cancelledOrder = $pickupService->cancelPickupOrder($order, 'Customer request');
    echo "Order cancelled successfully";
} catch (OrderCannotBeCancelledException $e) {
    echo "Cannot cancel order: " . $e->getMessage();
}
```

## Console Commands

### Sync Pickup Orders

Synchronize pickup order details from Cainiao API:

```bash
# Sync all unfinished orders
bin/console cainiao:pickup:sync-orders

# Sync a specific order by order code
bin/console cainiao:pickup:sync-orders --order-code="PK20240120123456"
```

This command will:
- Update order status and details from Cainiao API
- Sync all unfinished orders if no order code is specified
- Handle errors gracefully for individual orders in batch operations

## Sync Logistics Details

Synchronize logistics tracking information for pickup orders:

```bash
# Sync logistics for all orders with warehouse confirmed status
bin/console cainiao:pickup:sync-logistics

# Sync logistics for a specific order
bin/console cainiao:pickup:sync-logistics --order-code="PK20240120123456"
```

This command will:
- Query and update logistics tracking details from Cainiao API
- Replace existing logistics details with fresh data
- Include courier information when available
- Process all warehouse-confirmed orders if no order code is specified

## Configuration

### Entity Relationships

The bundle provides the following main entities:

- **CainiaoConfig**: API configuration for different warehouses/providers
- **PickupOrder**: Main order entity with full lifecycle tracking
- **AddressInfo**: Embedded value object for sender/receiver addresses
- **LogisticsDetail**: Tracking information for orders

### Order Status Types

The bundle supports 20+ order statuses, including:

- `CREATE` (0): Order created
- `WAREHOUSE_ACCEPT` (100): Warehouse accepted
- `ACCEPT` (400): Package picked up
- `TRANSPORT` (500): In transit
- `DELIVERING` (600): Out for delivery
- `SIGN` (1000): Delivered
- `CANCELLED`: Order cancelled

### Item Types

Supported item types:

- `DOC`: Documents
- `SPX`: Small packages
- `PACKAGE`: Standard packages
- `OTHER`: Other items

## Advanced Usage

### Pre-query Service Availability

```php
$availability = $cainiaoHttpClient->preQueryPickupService($order);

if ($availability['isFull']) {
    echo "Service is full for the requested time";
} else {
    echo "Available time slots:";
    foreach ($availability['availableTimeSlots'] as $slot) {
        echo $slot['startTime'] . ' - ' . $slot['endTime'] . PHP_EOL;
    }
}
```

### Modify an Existing Order

```php
$modificationData = [
    'weight' => 3.0, // Update weight
    'remark' => 'Updated: Handle with extra care',
    'expectPickupTimeStart' => '2024-01-21 09:00:00',
    'expectPickupTimeEnd' => '2024-01-21 12:00:00',
];

try {
    $modifiedOrder = $pickupService->modifyPickupOrder($order, $modificationData);
    echo "Order modified successfully";
} catch (OrderModificationFailedException $e) {
    echo "Cannot modify order: " . $e->getMessage();
}
```

### Query Orders by Status

```php
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;

$orderRepository = $container->get(PickupOrderRepository::class);

// Get all orders in transit
$ordersInTransit = $orderRepository->findByStatus(OrderStatusEnum::TRANSPORT);

// Get all delivered orders
$deliveredOrders = $orderRepository->findByStatus(OrderStatusEnum::SIGN);

// Get all unfinished orders
$unfinishedOrders = $orderRepository->findUnfinishedOrders();
```

## Exception Handling

The bundle provides specific exceptions for different scenarios:

- `ConfigurationException`: When Cainiao API configuration is missing or invalid
- `CainiaoApiException`: General API communication errors
- `InvalidResponseException`: When API returns unexpected response format
- `OrderNotFoundException`: When requested order doesn't exist
- `OrderCannotBeCancelledException`: When order status doesn't allow cancellation
- `OrderCancellationFailedException`: When cancellation request fails
- `OrderModificationFailedException`: When modification request fails

## Security

### API Security

This bundle implements several security measures:

- **Signature Verification**: All API requests to Cainiao are signed using HMAC-SHA256
- **HTTPS Communication**: All API calls are made over HTTPS
- **Access Token Management**: Secure storage and handling of API credentials
- **Input Validation**: All user input is validated using Symfony's validator component

### Data Protection

- **Sensitive Data**: API keys and secrets are stored securely in the database
- **Logging**: Sensitive information is excluded from logs by default
- **Data Encryption**: Consider encrypting sensitive configuration data at rest

### Best Practices

1. **Credential Management**:
    - Store API credentials in environment variables or secure configuration
    - Rotate API keys regularly
    - Use different credentials for different environments

2. **Network Security**:
    - Use HTTPS for all API communications
    - Implement rate limiting to prevent API abuse
    - Monitor API usage for unusual patterns

3. **Database Security**:
    - Use proper database permissions
    - Encrypt sensitive data columns if required
    - Regular security audits of stored data

If you discover a security vulnerability, please send an e-mail to security@tourze.com instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on submitting patches and the contribution workflow.

## Testing

```bash
# Run all tests
./vendor/bin/phpunit packages/cainiao-pickup-bundle/tests

# Run specific test suite
./vendor/bin/phpunit packages/cainiao-pickup-bundle/tests/Service
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

For bugs and feature requests, please use the [issue tracker](https://github.com/tourze/cainiao-pickup-bundle/issues).

## Credits

- [Tourze Team](https://github.com/tourze)
- [All Contributors](../../contributors)