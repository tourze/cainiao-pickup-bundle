<?php

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\AddressInfo;
use PHPUnit\Framework\TestCase;

class AddressInfoTest extends TestCase
{
    private AddressInfo $addressInfo;

    protected function setUp(): void
    {
        $this->addressInfo = new AddressInfo();
    }

    public function testGetterSetter_basicProperties(): void
    {
        $this->addressInfo->setName('测试名称');
        $this->addressInfo->setMobile('13800138000');
        $this->addressInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');
        
        $this->assertSame('测试名称', $this->addressInfo->getName());
        $this->assertSame('13800138000', $this->addressInfo->getMobile());
        $this->assertSame('北京市朝阳区三里屯街道10号', $this->addressInfo->getFullAddressDetail());
    }

    public function testGetterSetter_optionalProperties(): void
    {
        $this->addressInfo->setProvinceName('北京市');
        $this->addressInfo->setCityName('北京市');
        $this->addressInfo->setAreaName('朝阳区');
        $this->addressInfo->setAddress('三里屯街道10号');
        
        $this->assertSame('北京市', $this->addressInfo->getProvinceName());
        $this->assertSame('北京市', $this->addressInfo->getCityName());
        $this->assertSame('朝阳区', $this->addressInfo->getAreaName());
        $this->assertSame('三里屯街道10号', $this->addressInfo->getAddress());
    }

    public function testFluentInterface_returnsObjectInstance(): void
    {
        $result = $this->addressInfo->setName('测试名称');
        $this->assertSame($this->addressInfo, $result);
        
        $result = $this->addressInfo->setMobile('13800138000');
        $this->assertSame($this->addressInfo, $result);
        
        $result = $this->addressInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');
        $this->assertSame($this->addressInfo, $result);
    }

    public function testChainedCalls_setsAllProperties(): void
    {
        $this->addressInfo->setName('测试名称')
            ->setMobile('13800138000')
            ->setFullAddressDetail('北京市朝阳区三里屯街道10号')
            ->setProvinceName('北京市')
            ->setCityName('北京市')
            ->setAreaName('朝阳区')
            ->setAddress('三里屯街道10号');
        
        $this->assertSame('测试名称', $this->addressInfo->getName());
        $this->assertSame('13800138000', $this->addressInfo->getMobile());
        $this->assertSame('北京市朝阳区三里屯街道10号', $this->addressInfo->getFullAddressDetail());
        $this->assertSame('北京市', $this->addressInfo->getProvinceName());
        $this->assertSame('北京市', $this->addressInfo->getCityName());
        $this->assertSame('朝阳区', $this->addressInfo->getAreaName());
        $this->assertSame('三里屯街道10号', $this->addressInfo->getAddress());
    }
} 