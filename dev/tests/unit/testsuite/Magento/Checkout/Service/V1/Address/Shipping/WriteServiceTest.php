<?php
/**
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Checkout\Service\V1\Address\Shipping;

class WriteServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WriteService
     */
    protected $service;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteLoaderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $converterMock;

    /**
     * @var \Magento\TestFramework\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->quoteLoaderMock = $this->getMock('\Magento\Checkout\Service\V1\QuoteLoader', [], [], '', false);
        $this->storeManagerMock = $this->getMock('\Magento\Store\Model\StoreManagerInterface');
        $this->addressFactoryMock = $this->getMock(
            '\Magento\Sales\Model\Quote\AddressFactory', ['create', '__wakeup'], [], '', false
        );

        $this->objectManager = new \Magento\TestFramework\Helper\ObjectManager($this);
        $this->quoteAddressMock = $this->getMock(
            '\Magento\Sales\Model\Quote\Address',
            ['getCustomerId', 'load', 'getData', 'setData', 'setStreet', 'setRegionId', 'setRegion', '__wakeup'],
            [],
            '',
            false
        );
        $this->addressFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->quoteAddressMock));

        $this->validatorMock = $this->getMock(
            '\Magento\Checkout\Service\V1\Address\Validator', [], [], '', false
        );

        $this->converterMock = $this->getMock(
            '\Magento\Checkout\Service\V1\Address\Converter', [], [], '', false
        );

        $this->service = $this->objectManager->getObject(
            '\Magento\Checkout\Service\V1\Address\Shipping\WriteService',
            [
                'quoteLoader' => $this->quoteLoaderMock,
                'storeManager' => $this->storeManagerMock,
                'quoteAddressFactory' => $this->addressFactoryMock,
                'addressValidator' => $this->validatorMock,
                'addressConverter' => $this->converterMock,
            ]
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expected ExceptionMessage error345
     */
    public function testSetAddressValidationFailed()
    {
        $storeId = 554;
        $storeMock = $this->getMock('\Magento\Store\Model\Store', [], [], '', false);
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $quoteMock = $this->getMock('\Magento\Sales\Model\Quote', [], [], '', false);
        $this->quoteLoaderMock->expects($this->once())
            ->method('load')
            ->with('cart654', $storeId)
            ->will($this->returnValue($quoteMock));

        $this->validatorMock->expects($this->once())->method('validate')
            ->will($this->throwException(new \Magento\Framework\Exception\NoSuchEntityException('error345')));

        $this->service->setAddress('cart654', null);
    }

    public function testSetAddress()
    {
        $storeId = 323;
        $storeMock = $this->getMock('\Magento\Store\Model\Store', [], [], '', false);
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $quoteMock = $this->getMock('\Magento\Sales\Model\Quote', [], [], '', false);
        $this->quoteLoaderMock->expects($this->once())
            ->method('load')
            ->with('cart867', $storeId)
            ->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->once())->method('isVirtual')->will($this->returnValue(false));

        /** @var \Magento\Checkout\Service\V1\Data\Cart\AddressBuilder $addressDataBuilder */
        $addressDataBuilder = $this->objectManager->getObject('Magento\Checkout\Service\V1\Data\Cart\AddressBuilder');

        /** @var \Magento\Checkout\Service\V1\Data\Cart\Address $addressData */
        $addressData = $addressDataBuilder->setId(356)->create();

        $this->validatorMock->expects($this->once())->method('validate')
            ->with($addressData)
            ->will($this->returnValue(true));

        $this->converterMock->expects($this->once())->method('convertDataObjectToModel')
            ->with($addressData, $this->quoteAddressMock)
            ->will($this->returnValue($this->quoteAddressMock));

        $quoteMock->expects($this->once())->method('setShippingAddress')->with($this->quoteAddressMock);
        $quoteMock->expects($this->once())->method('setDataChanges')->with(true);
        $quoteMock->expects($this->once())->method('save');

        $addressId = 1;
        $shippingAddressMock = $this->getMock('\Magento\Sales\Model\Quote\Address', [], [], '', false);
        $shippingAddressMock->expects($this->once())->method('getId')->will($this->returnValue($addressId));
        $quoteMock->expects($this->once())->method('getShippingAddress')
            ->will($this->returnValue($shippingAddressMock));

        $this->assertEquals($addressId, $this->service->setAddress('cart867', $addressData));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Cart contains virtual product(s) only. Shipping address is not applicable
     */
    public function testSetAddressForVirtualProduct()
    {
        $storeId = 323;
        $storeMock = $this->getMock('\Magento\Store\Model\Store', [], [], '', false);
        $storeMock->expects($this->once())->method('getId')->will($this->returnValue($storeId));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $quoteMock = $this->getMock('\Magento\Sales\Model\Quote', [], [], '', false);
        $this->quoteLoaderMock->expects($this->once())
            ->method('load')
            ->with('cart867', $storeId)
            ->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->once())->method('isVirtual')->will($this->returnValue(true));

        /** @var \Magento\Checkout\Service\V1\Data\Cart\AddressBuilder $addressDataBuilder */
        $addressDataBuilder = $this->objectManager->getObject('Magento\Checkout\Service\V1\Data\Cart\AddressBuilder');

        /** @var \Magento\Checkout\Service\V1\Data\Cart\Address $addressData */
        $addressData = $addressDataBuilder->setId(356)->create();

        $this->validatorMock->expects($this->never())->method('validate');

        $quoteMock->expects($this->never())->method('setShippingAddress');
        $quoteMock->expects($this->never())->method('save');

        $this->service->setAddress('cart867', $addressData);
    }
}
