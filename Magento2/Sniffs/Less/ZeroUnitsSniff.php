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
 * Class ZeroUnitsSniff
 *
 * Ensure that units for 0 is not specified
 * Omit leading "0"s in values, use dot instead
 *
 * @link https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-less.html#0-and-units
 * @link https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-less.html#floating-values
 */
class ZeroUnitsSniff implements Sniff
{
    private const CSS_PROPERTY_UNIT_PX = 'px';
    private const CSS_PROPERTY_UNIT_EM = 'em';
    private const CSS_PROPERTY_UNIT_REM = 'rem';

    private array $units = [
        self::CSS_PROPERTY_UNIT_PX,
        self::CSS_PROPERTY_UNIT_EM,
        self::CSS_PROPERTY_UNIT_REM,
    ];

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
        return [T_LNUMBER, T_DNUMBER];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $tokenCode = $tokens[$stackPtr]['code'];
        $tokenContent = $tokens[$stackPtr]['content'];

        $nextToken = $tokens[$stackPtr + 1];

        if (T_LNUMBER === $tokenCode
            && '0' === $tokenContent
            && T_STRING === $nextToken['code']
            && in_array($nextToken['content'], $this->units)
        ) {
            $phpcsFile->addError('Units specified for "0" value', $stackPtr, 'ZeroUnitFound');
        }

        if ((T_DNUMBER === $tokenCode)
            && str_starts_with((string) $tokenContent, '0')
            && ((float)$tokenContent < 1)
        ) {
            $phpcsFile->addError('Values starts from "0"', $stackPtr, 'ZeroUnitFound');
        }
    }
}
