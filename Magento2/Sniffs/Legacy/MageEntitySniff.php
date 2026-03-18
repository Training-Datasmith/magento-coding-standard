<?php

declare(strict_types=1);
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

namespace Magento2\Sniffs\Legacy;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detects typical Magento 1.x classes constructions.
 */
class MageEntitySniff implements Sniff
{
    /**
     * String representation of error.
     *
     * @var string
     */
    protected $errorMessage = 'Possible Magento 2 design violation. Detected typical Magento 1.x construction "%s".';

    /**
     * Error violation code.
     *
     * @var string
     */
    protected $errorCode = 'FoundLegacyEntity';

    /**
     * Legacy entity from Magento 1.
     *
     * @var string
     */
    protected $legacyEntity = 'Mage';

    /**
     * Legacy prefixes from Magento 1.
     *
     * @var array
     */
    protected $legacyPrefixes = [
        'Mage_',
        'Enterprise_',
    ];

    /**
     * @inheritdoc
     */
    public function register(): array
    {
        return [
            T_DOUBLE_COLON,
            T_NEW,
        ];
    }

    /**
     * List of tokens for which we should find name before his position.
     *
     * @var array
     */
    protected $nameBefore = [
        T_DOUBLE_COLON,
    ];

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if (in_array($tokens[$stackPtr]['code'], $this->nameBefore)) {
            $oldPosition = $stackPtr;
            $stackPtr = $phpcsFile->findPrevious(T_STRING, $stackPtr - 1, null, false, null, true);
            if ($stackPtr === false) {
                return;
            }
            $entityName = $tokens[$stackPtr]['content'];
            $error = $entityName . $tokens[$oldPosition]['content'];
        } else {
            $oldPosition = $stackPtr;
            $stackPtr = $phpcsFile->findNext(T_STRING, $oldPosition + 1, null, false, null, true);
            if ($stackPtr === false) {
                return;
            }
            $entityName = $tokens[$stackPtr]['content'];
            $error = $tokens[$oldPosition]['content'] . ' ' . $entityName;
        }
        if ($entityName === $this->legacyEntity || $this->isPrefixLegacy($entityName)) {
            $phpcsFile->addError(
                $this->errorMessage,
                $stackPtr,
                $this->errorCode,
                [$error]
            );
        }
    }

    /**
     * Method checks if passed string contains legacy prefix from Magento 1.
     *
     * @param string $entityName
     */
    private function isPrefixLegacy($entityName): bool
    {
        foreach ($this->legacyPrefixes as $entity) {
            if (str_starts_with($entityName, (string) $entity)) {
                return true;
            }
        }
        return false;
    }
}
