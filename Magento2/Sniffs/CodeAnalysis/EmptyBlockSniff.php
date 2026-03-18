<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
namespace Magento2\Sniffs\CodeAnalysis;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\EmptyStatementSniff;

/**
 * Detects possible empty blocks.
 */
class EmptyBlockSniff extends EmptyStatementSniff
{
    /**
     * @inheritdoc
     */
    public function register()
    {
        return array_merge(
            parent::register(),
            [
                T_FUNCTION,
                T_TRAIT
            ]
        );
    }
    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_FUNCTION &&
            str_starts_with($phpcsFile->getDeclarationName($stackPtr), 'around')) {
            return;
        }

        // Ignore empty constructor function blocks when using property promotion
        if ($tokens[$stackPtr]['code'] === T_FUNCTION &&
            str_starts_with($phpcsFile->getDeclarationName($stackPtr), '__construct') &&
            count($phpcsFile->getMethodParameters($stackPtr)) > 0 &&
            array_reduce($phpcsFile->getMethodParameters($stackPtr), static fn(bool $result, $methodParam) => $result && isset($methodParam['property_visibility']), true)) {

            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }//end process()
}
