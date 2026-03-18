<?php

declare(strict_types=1);
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

namespace Magento2\Sniffs\Legacy;

use DOMDocument;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Test to find obsolete acl declaration
 */
class ObsoleteMenuSniff implements Sniff
{
    private const WARNING_OBSOLETE_MENU_STRUCTURE = 'ObsoleteMenuStructure';

    private string $xpath = '/config/menu/*[boolean(./children) or boolean(./title) or boolean(./action)]';

    /**
     * @inheritdoc
     */
    public function register(): array
    {
        return [
            T_INLINE_HTML,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($stackPtr > 0) {
            return;
        }

        $xml = simplexml_load_string($this->getFormattedXML($phpcsFile));
        $foundElements = $xml->xpath($this->xpath);
        foreach ($foundElements as $element) {
            $phpcsFile->addWarning(
                'Obsolete menu structure detected in line ' . dom_import_simplexml($element)->getLineNo(),
                dom_import_simplexml($element)->getLineNo() - 1,
                self::WARNING_OBSOLETE_MENU_STRUCTURE
            );
        }
    }

    /**
     * Format the incoming XML to avoid tags split into several lines.
     *
     * @return false|string
     */
    private function getFormattedXML(File $phpcsFile): string|false
    {
        $doc = new DomDocument('1.0');
        $doc->formatOutput = true;
        $doc->loadXML($phpcsFile->getTokensAsString(0, 999999));
        return $doc->saveXML();
    }
}
