<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
namespace Magento2\Sniffs\Less;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Class TypeSelectorConcatenation
 *
 * Ensure that selector in one line, concatenation is not used
 *
 * @link https://devdocs.magento.com/guides/v2.4/coding-standards/code-standard-less.html#formatting-1
 */
class TypeSelectorConcatenationSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [TokenizerSymbolsInterface::TOKENIZER_CSS];

    private array $symbolsBeforeConcat = [
        TokenizerSymbolsInterface::INDENT_SPACES,
        TokenizerSymbolsInterface::NEW_LINE,
    ];

    /**
     * @inheritdoc
     */
    public function register(): array
    {
        return [T_BITWISE_AND];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (str_starts_with((string) $tokens[$stackPtr + 1]['content'], '-')
            && in_array($tokens[$stackPtr - 1]['content'], $this->symbolsBeforeConcat)
        ) {
            $phpcsFile->addError('Concatenation is used', $stackPtr, 'ConcatenationUsage');
        }
    }
}
