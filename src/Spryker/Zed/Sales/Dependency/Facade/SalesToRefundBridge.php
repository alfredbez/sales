<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Sales\Dependency\Facade;

use Spryker\Zed\Refund\Business\RefundFacade;

class SalesToRefundBridge implements SalesToRefundInterface
{

    /**
     * @var \Spryker\Zed\Refund\Business\RefundFacade
     */
    protected $refundFacade;

    /**
     * @param \Spryker\Zed\Refund\Business\RefundFacade $refundFacade
     */
    public function __construct($refundFacade)
    {
        $this->refundFacade = $refundFacade;
    }

    /**
     * @param int $idSalesOrder
     *
     * @return \Generated\Shared\Transfer\RefundTransfer[]
     */
    public function getRefundsByIdSalesOrder($idSalesOrder)
    {
        return $this->refundFacade->getRefundsByIdSalesOrder($idSalesOrder);
    }

}