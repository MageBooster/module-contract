<?php
/*
 * Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.
 * Commercial licence, see LICENSE.txt. https://magebooster.com
 * Licensed per serial number for one production installation (one store domain); staging and
 * development copies of that store are allowed. Serial numbers and downloads are tied to the customer.
 * You may modify this code for your own licensed store; keep this notice.
 * Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing it publicly,
 * removing licence checks or notices, sharing serial numbers. Unauthorised use: the serial number is
 * revoked without refund, updates and support stop, damages and legal action may follow.
 * Estonian law applies; court: Harju County Court (Harju Maakohus).
 */
declare(strict_types=1);

namespace MageBooster\Contract\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * An agreement of one order, filled with the order data when the order was placed.
 *
 * @method int getOrderId()
 * @method string getIncrementId()
 * @method int getStoreId()
 * @method int getAgreementId()
 * @method string getAgreementName()
 * @method string getContent()
 * @method string getContentHash()
 * @method string|null getPdfPath()
 * @method string|null getCustomerIp()
 * @method bool getIsBackfilled()
 * @method string getCreatedAt()
 */
class Record extends AbstractModel
{
    /** @var string */
    protected $_eventPrefix = 'magebooster_contract_record';

    protected function _construct()
    {
        $this->_init(ResourceModel\Record::class);
    }
}
