<?php
/**
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
namespace Magento\Sales\Service\V1\Action;

use Magento\Sales\Model\Order\AddressConverter;
use Magento\Sales\Service\V1\Data\OrderAddress;

/**
 * Class OrderAddressUpdate
 */
class OrderAddressUpdate
{
    /**
     * @var AddressConverter
     */
    protected $addressConverter;

    /**
     * @param AddressConverter $addressConverter
     */
    public function __construct(
        AddressConverter $addressConverter
    ) {
        $this->addressConverter = $addressConverter;
    }

    /**
     * Invoke order address update service
     *
     * @param \Magento\Sales\Service\V1\Data\OrderAddress $orderAddress
     * @return bool
     */
    public function invoke(OrderAddress $orderAddress)
    {
        $orderAddressModel = $this->addressConverter->getModel($orderAddress);
        $orderAddressModel->save();
        return true;
    }
}
