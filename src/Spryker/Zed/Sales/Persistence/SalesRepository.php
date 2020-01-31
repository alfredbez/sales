<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Sales\Persistence;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\FilterTransfer;
use Generated\Shared\Transfer\OrderListRequestTransfer;
use Generated\Shared\Transfer\OrderListTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\Propel\PropelFilterCriteria;

/**
 * @method \Spryker\Zed\Sales\Persistence\SalesPersistenceFactory getFactory()
 */
class SalesRepository extends AbstractRepository implements SalesRepositoryInterface
{
    protected const ID_SALES_ORDER = 'id_sales_order';

    /**
     * @param string $customerReference
     * @param string $orderReference
     *
     * @return int|null
     */
    public function findCustomerOrderIdByOrderReference(string $customerReference, string $orderReference): ?int
    {
        $idSalesOrder = $this->getFactory()
            ->createSalesOrderQuery()
            ->filterByCustomerReference($customerReference)
            ->filterByOrderReference($orderReference)
            ->select([static::ID_SALES_ORDER])
            ->findOne();

        return $idSalesOrder;
    }

    /**
     * @param int $idOrderAddress
     *
     * @return \Generated\Shared\Transfer\AddressTransfer|null
     */
    public function findOrderAddressByIdOrderAddress(int $idOrderAddress): ?AddressTransfer
    {
        $addressEntity = $this->getFactory()
            ->createSalesOrderAddressQuery()
            ->leftJoinWithCountry()
            ->filterByIdSalesOrderAddress($idOrderAddress)
            ->findOne();

        if ($addressEntity === null) {
            return null;
        }

        return $this->hydrateAddressTransferFromEntity($this->createOrderAddressTransfer(), $addressEntity);
    }

    /**
     * @param \Generated\Shared\Transfer\AddressTransfer $addressTransfer
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderAddress $addressEntity
     *
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function hydrateAddressTransferFromEntity(
        AddressTransfer $addressTransfer,
        SpySalesOrderAddress $addressEntity
    ): AddressTransfer {
        $addressTransfer->fromArray($addressEntity->toArray(), true);
        $addressTransfer->setIso2Code($addressEntity->getCountry()->getIso2Code());

        return $addressTransfer;
    }

    /**
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function createOrderAddressTransfer(): AddressTransfer
    {
        return new AddressTransfer();
    }

    /**
     * @param \Generated\Shared\Transfer\OrderListRequestTransfer $orderListRequestTransfer
     *
     * @return \Generated\Shared\Transfer\OrderListTransfer
     */
    public function getCustomerOrderListByCustomerReference(OrderListRequestTransfer $orderListRequestTransfer): OrderListTransfer
    {
        $orderListQuery = $this->getFactory()
            ->createSalesOrderQuery()
            ->filterByCustomerReference($orderListRequestTransfer->getCustomerReference());

        $ordersCount = $orderListQuery->count();
        if (!$ordersCount) {
            return new OrderListTransfer();
        }

        $orderListQuery = $this->applyFilterToQuery($orderListQuery, $orderListRequestTransfer->getFilter());

        $orderListTransfer = $this->getFactory()
            ->createSalesOrderMapper()
            ->mapSalesOrderEntitiesToOrderListTransfer($orderListQuery->find()->getArrayCopy(), new OrderListTransfer());

        $paginationTransfer = $this->getPaginationTransfer($orderListRequestTransfer, $orderListQuery, $ordersCount);
        $orderListTransfer->setPagination($paginationTransfer);

        return $orderListTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\OrderListRequestTransfer $orderListRequestTransfer
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderQuery $orderListQuery
     * @param int $ordersCount
     *
     * @return \Generated\Shared\Transfer\PaginationTransfer
     */
    protected function getPaginationTransfer(
        OrderListRequestTransfer $orderListRequestTransfer,
        SpySalesOrderQuery $orderListQuery,
        int $ordersCount
    ): PaginationTransfer {
        $paginationTransfer = $orderListRequestTransfer->getPagination();
        if (!$paginationTransfer) {
            return (new PaginationTransfer())->setNbResults($ordersCount);
        }

        $paginationTransfer
            ->requirePage()
            ->requireMaxPerPage();

        $page = $paginationTransfer->getPage();

        $maxPerPage = $paginationTransfer
            ->getMaxPerPage();

        $paginationModel = $orderListQuery->paginate($page, $maxPerPage);
        $paginationTransfer->setNbResults($paginationModel->getNbResults());
        $paginationTransfer->setFirstIndex($paginationModel->getFirstIndex());
        $paginationTransfer->setLastIndex($paginationModel->getLastIndex());
        $paginationTransfer->setFirstPage($paginationModel->getFirstPage());
        $paginationTransfer->setLastPage($paginationModel->getLastPage());
        $paginationTransfer->setNextPage($paginationModel->getNextPage());
        $paginationTransfer->setPreviousPage($paginationModel->getPreviousPage());

        return $paginationTransfer;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderQuery $orderListQuery
     * @param \Generated\Shared\Transfer\FilterTransfer|null $filterTransfer
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderQuery
     */
    protected function applyFilterToQuery(SpySalesOrderQuery $orderListQuery, ?FilterTransfer $filterTransfer)
    {
        if ($filterTransfer) {
            $orderListQuery->mergeWith(
                (new PropelFilterCriteria($filterTransfer))->toCriteria()
            );
        }

        return $orderListQuery;
    }
}
