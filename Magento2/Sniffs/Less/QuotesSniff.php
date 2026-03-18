<?php

declare(strict_types=1);
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

namespace Magento2\Sniffs\Less;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class QuotesSniff
 *
 * Ensure that single quotes are used
 *
 * @link https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-less.html#quotes
 */
class QuotesSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [TokenizerSymbolsInterface::TOKENIZER_CSS];

    /**
     * @inheritdoc
     */
    public function register(): array
    {
        return [T_CONSTANT_ENCAPSED_STRING];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (str_contains((string) $tokens[$stackPtr]['content'], '"')) {
            $phpcsFile->addError('Use single quotes', $stackPtr, 'DoubleQuotes');
        }
    }
}
