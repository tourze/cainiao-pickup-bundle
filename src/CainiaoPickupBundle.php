<?php

namespace CainiaoPickupBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '菜鸟驿站模块')]
class CainiaoPickupBundle extends Bundle
{
}
