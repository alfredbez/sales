<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Sales\Module;

use Codeception\Module;
use Orm\Zed\Country\Persistence\SpyCountryQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcessQuery;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderAddressTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTableMap;
use Orm\Zed\Sales\Persistence\SpySalesDiscount;
use Orm\Zed\Sales\Persistence\SpySalesExpense;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Orm\Zed\Shipment\Persistence\SpyShipmentMethodQuery;
use Spryker\Shared\Shipment\ShipmentConstants;

class Sales extends Module
{

    /**
     * @return int
     */
    public function createOrder()
    {
        $salesOrderEntity = new SpySalesOrder();

        $this->addOrderDetails($salesOrderEntity);
        $this->addAddresses($salesOrderEntity);
        $this->addShipment($salesOrderEntity);

        $salesOrderEntity->save();

        $this->addExpenses($salesOrderEntity);

        return $salesOrderEntity->getIdSalesOrder();
    }

    /**
     * @return void
     */
    public function createOrderWithOneItem()
    {
        $i = $this;
        $idSalesOrder = $i->createOrder();
        $i->createSalesOrderItemForOrder($idSalesOrder);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function addExpenses(SpySalesOrder $salesOrderEntity)
    {
        $this->addShipmentExpense($salesOrderEntity);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function addOrderDetails(SpySalesOrder $salesOrderEntity)
    {
        $salesOrderEntity->setOrderReference(random_int(0, 9999999));
        $salesOrderEntity->setIsTest(true);
        $salesOrderEntity->setSalutation(SpySalesOrderTableMap::COL_SALUTATION_MR);
        $salesOrderEntity->setFirstName('FirstName');
        $salesOrderEntity->setLastName('LastName');
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function addAddresses(SpySalesOrder $salesOrderEntity)
    {
        $billingAddressEntity = $salesOrderEntity->getBillingAddress();
        if ($billingAddressEntity === null) {
            $billingAddressEntity = $this->createBillingAddress();
            $salesOrderEntity->setBillingAddress($billingAddressEntity);
        }

        $shippingAddressEntity = $salesOrderEntity->getShippingAddress();
        if ($shippingAddressEntity === null) {
            $salesOrderEntity->setShippingAddress($billingAddressEntity);
        }
    }

    /**
     * @return \Orm\Zed\Country\Persistence\SpyCountry
     */
    protected function getCountryEntity()
    {
        $countryQuery = new SpyCountryQuery();
        $countryQuery->filterByIso2Code('DE');
        $countryQuery->filterByIso3Code('DEU');
        $countryQuery->filterByName('Germany');
        $countryQuery->filterByPostalCodeMandatory(true);
        $countryQuery->filterByPostalCodeRegex('\d{5}');

        $countryEntity = $countryQuery->findOneOrCreate();
        $countryEntity->save();

        return $countryEntity;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function addShipment(SpySalesOrder $salesOrderEntity)
    {
        $shipmentMethodQuery = new SpyShipmentMethodQuery();
        $shipmentMethodEntity = $shipmentMethodQuery->filterByName('Standard')->findOne();
        $salesOrderEntity->setShipmentMethod($shipmentMethodEntity);
    }

    /**
     * @param int $idSalesOrder
     * @param array $salesOrderItem
     *
     * @return int
     */
    public function createSalesOrderItemForOrder($idSalesOrder, array $salesOrderItem = [])
    {
        $salesOrderQuery = new SpySalesOrderQuery();
        $salesOrderEntity = $salesOrderQuery->findOneByIdSalesOrder($idSalesOrder);

        $salesOrderItem = $this->createSalesOrderItem($salesOrderItem);
        $salesOrderItem->setFkSalesOrder($salesOrderEntity->getIdSalesOrder());
        $salesOrderItem->save();

        return $salesOrderItem->getIdSalesOrderItem();
    }

    /**
     * @param array $salesOrderItem
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItem
     */
    protected function createSalesOrderItem(array $salesOrderItem)
    {
        $salesOrderItemEntity = new SpySalesOrderItem();
        $salesOrderItemEntity->fromArray($salesOrderItem);
        if ($salesOrderItemEntity->getName() === null) {
            $salesOrderItemEntity->setName('name');
        }
        if ($salesOrderItemEntity->getSku() === null) {
            $salesOrderItemEntity->setSku('sku');
        }
        if ($salesOrderItemEntity->getGrossPrice() === null) {
            $salesOrderItemEntity->setGrossPrice(1000);
        }
        if ($salesOrderItemEntity->getTaxRate() === null) {
            $salesOrderItemEntity->setTaxRate(19);
        }
        if ($salesOrderItemEntity->getQuantity() === null) {
            $salesOrderItemEntity->setQuantity(1);
        }

        $omsOrderItemStateEntity = $this->getOrderItemState($salesOrderItem);
        $salesOrderItemEntity->setFkOmsOrderItemState($omsOrderItemStateEntity->getIdOmsOrderItemState());

        $omsOrderProcessEntity = $this->getOrderProcess($salesOrderItem);
        $salesOrderItemEntity->setFkOmsOrderProcess($omsOrderProcessEntity->getIdOmsOrderProcess());

        return $salesOrderItemEntity;
    }

    /**
     * @param int $idSalesOrderItem
     * @param array $discount
     *
     * @return void
     */
    public function createDiscountForSalesOrderItem($idSalesOrderItem, array $discount = [])
    {
        $salesOrderDiscountEntity = new SpySalesDiscount();
        $salesOrderDiscountEntity->fromArray($discount);
        $salesOrderDiscountEntity->setFkSalesOrderItem($idSalesOrderItem);
        if ($salesOrderDiscountEntity->getName() === null) {
            $salesOrderDiscountEntity->setName('discount name');
        }
        if ($salesOrderDiscountEntity->getDisplayName() === null) {
            $salesOrderDiscountEntity->setDisplayName('discount display name');
        }
        if ($salesOrderDiscountEntity->getAmount() === null) {
            $salesOrderDiscountEntity->setAmount(33);
        }

        $salesOrderDiscountEntity->save();
    }

    /**
     * @param array $salesOrderItem
     *
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState
     */
    protected function getOrderItemState(array $salesOrderItem)
    {
        $expectedState = (!empty($salesOrderItem['state'])) ? $salesOrderItem['state'] : 'new';
        $omsOrderItemStateQuery = new SpyOmsOrderItemStateQuery();
        $omsOrderItemStateEntity = $omsOrderItemStateQuery->filterByName($expectedState)->findOneOrCreate();
        $omsOrderItemStateEntity->save();

        return $omsOrderItemStateEntity;
    }

    /**
     * @param array $salesOrderItem
     *
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderProcess
     */
    protected function getOrderProcess(array $salesOrderItem)
    {
        $expectedProcess = (!empty($salesOrderItem['process'])) ? $salesOrderItem['process'] : 'Nopayment01';
        $omsOrderProcessQuery = new SpyOmsOrderProcessQuery();
        $omsOrderProcessEntity = $omsOrderProcessQuery->filterByName($expectedProcess)->findOneOrCreate();
        $omsOrderProcessEntity->save();

        return $omsOrderProcessEntity;
    }

    /**
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderAddress
     */
    protected function createBillingAddress()
    {
        $billingAddressEntity = new SpySalesOrderAddress();

        $countryEntity = $this->getCountryEntity();
        $billingAddressEntity->setCountry($countryEntity);

        $billingAddressEntity->setSalutation(SpySalesOrderAddressTableMap::COL_SALUTATION_MR);
        $billingAddressEntity->setFirstName('FirstName');
        $billingAddressEntity->setLastName('LastName');
        $billingAddressEntity->setAddress1('Address1');
        $billingAddressEntity->setAddress2('Address2');
        $billingAddressEntity->setCity('City');
        $billingAddressEntity->setZipCode('12345');
        $billingAddressEntity->save();

        return $billingAddressEntity;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function addShipmentExpense(SpySalesOrder $salesOrderEntity)
    {
        $shipmentEntity = $salesOrderEntity->getShipmentMethod();
        $shipmentExpense = new SpySalesExpense();
        $shipmentExpense->setFkSalesOrder($salesOrderEntity->getIdSalesOrder());
        $shipmentExpense->setName($shipmentEntity->getName());
        $shipmentExpense->setType(ShipmentConstants::SHIPMENT_EXPENSE_TYPE);
        $shipmentExpense->setGrossPrice($shipmentEntity->getDefaultPrice());

        $shipmentExpense->save();
    }

}