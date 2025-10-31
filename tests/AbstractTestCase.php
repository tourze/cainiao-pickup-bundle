<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * 通用的测试基类，用于简单的单元测试
 */
#[CoversNothing]
abstract class AbstractTestCase extends TestCase
{
    // 这个类主要提供类型安全和未来扩展能力
}
