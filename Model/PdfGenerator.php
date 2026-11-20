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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Escaper;
use Magento\Framework\Filesystem;

/**
 * Builds the stand-alone HTML document of a filled agreement and, when dompdf is installed, an A4 PDF from it.
 */
class PdfGenerator
{
    private const TMP_DIR = 'magebooster_contract/dompdf';

    public function __construct(
        private Filesystem $filesystem,
        private Escaper $escaper
    ) {
    }

    /** True when the dompdf library (composer dependency) can be loaded. */
    public function isAvailable(): bool
    {
        return class_exists(\Dompdf\Dompdf::class) && class_exists(\Dompdf\Options::class);
    }

    /**
     * @param array<string, string> $footer label => value (document details printed under the text)
     */
    public function render(string $title, string $contentHtml, array $footer): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('dompdf/dompdf is not installed.');
        }
        $var = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $var->create(self::TMP_DIR);
        $tmp = $var->getAbsolutePath(self::TMP_DIR);

        $options = new \Dompdf\Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setTempDir($tmp);
        $options->setFontCache($tmp);
        // No local file access: only data: URIs, file access limited to the temp and font folders.
        $options->setChroot([$tmp, $options->getFontDir()]);
        if (method_exists($options, 'setAllowedProtocols')) {
            $options->setAllowedProtocols(['data://' => ['rules' => []]]);
        }

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($this->document($title, $contentHtml, $footer), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string)$dompdf->output();
    }

    /**
     * @param array<string, string> $footer
     */
    public function document(string $title, string $contentHtml, array $footer, string $lang = 'tr'): string
    {
        $footerRows = '';
        foreach ($footer as $label => $value) {
            $footerRows .= sprintf(
                '<tr><td class="l">%s</td><td>%s</td></tr>',
                $this->escaper->escapeHtml($label),
                $this->escaper->escapeHtml($value)
            );
        }
        $title = (string)$this->escaper->escapeHtml($title);
        $lang = preg_match('/^[a-z]{2}$/', $lang) ? $lang : 'tr';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{$title}</title>
<style>
    @page { margin: 18mm 15mm 20mm 15mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; line-height: 1.45; color: #222; }
    p { margin: 0 0 6px; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; }
    .meta { page-break-inside: avoid; margin-top: 18px; border-top: 1px solid #999; padding-top: 6px; font-size: 9px; color: #555; }
    .meta td { padding: 1px 6px 1px 0; vertical-align: top; }
    .meta td.l { white-space: nowrap; font-weight: bold; }
</style>
</head>
<body>
{$contentHtml}
<table class="meta">{$footerRows}</table>
</body>
</html>
HTML;
    }
}
