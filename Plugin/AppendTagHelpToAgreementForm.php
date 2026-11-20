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

namespace MageBooster\Contract\Plugin;

use MageBooster\Contract\Model\TagList;
use Magento\CheckoutAgreements\Block\Adminhtml\Agreement\Edit;
use Magento\Framework\Escaper;

/**
 * Adds the list of usable {{TAG}} placeholders under the Terms and Conditions edit form.
 */
class AppendTagHelpToAgreementForm
{
    public function __construct(
        private TagList $tagList,
        private Escaper $escaper
    ) {
    }

    public function afterToHtml(Edit $subject, string $html): string
    {
        $groups = '';
        foreach ($this->tagList->getGroups() as $group => $tags) {
            $rows = '';
            foreach ($tags as $tag => $description) {
                $rows .= sprintf(
                    '<tr><td style="padding:3px 16px 3px 0;white-space:nowrap"><code>{{%s}}</code></td><td style="padding:3px 0">%s</td></tr>',
                    $this->escaper->escapeHtml($tag),
                    $this->escaper->escapeHtml($description)
                );
            }
            $groups .= sprintf(
                '<h4 style="margin:18px 0 6px">%s</h4><table>%s</table>',
                $this->escaper->escapeHtml($group),
                $rows
            );
        }

        return $html . sprintf(
            '<section class="admin__page-section" id="mb-contract-tags">'
            . '<div class="admin__page-section-title"><span class="title">%s</span></div>'
            . '<p>%s</p>%s</section>',
            $this->escaper->escapeHtml(__('Tags you can use in the text (MageBooster Contract)')),
            $this->escaper->escapeHtml(__(
                'At checkout the tags are filled with what the customer has entered; when the order is placed, with the '
                . 'order data. The filled text is saved with the order as HTML and PDF; editing this text later does not '
                . 'change the records of past orders. Have the text reviewed by your lawyer.'
            )),
            $groups
        );
    }
}
