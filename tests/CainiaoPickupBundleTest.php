<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests;

use CainiaoPickupBundle\CainiaoPickupBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(CainiaoPickupBundle::class)]
#[RunTestsInSeparateProcesses]
final class CainiaoPickupBundleTest extends AbstractBundleTestCase
{
}
