<?php

declare(strict_types=1);
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

namespace Magento2\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detects api annotation for an abstract class.
 */
class AbstractApiSniff implements Sniff
{
    /**
     * String representation of warning.
     *
     * @var string
     */
    protected $warningMessage = 'Abstract classes MUST NOT be marked as public @api.';

    /**
     * Warning violation code.
     *
     * @var string
     */
    protected $warningCode = 'AbstractApi';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CLASS];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($prev !== false && $tokens[$prev]['code'] !== T_ABSTRACT) {
            return;
        }

        $commentStartPtr = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPtr - 1, 0);
        if ($commentStartPtr === false) {
            return;
        }
        $commentCloserPtr = $tokens[$commentStartPtr]['comment_closer'];

        for ($i = $commentStartPtr; $i <= $commentCloserPtr; $i++) {
            $token = $tokens[$i];

            if ($token['code'] !== T_DOC_COMMENT_TAG) {
                continue;
            }

            if (!str_contains((string) $token['content'], '@api')) {
                continue;
            }

            $phpcsFile->addWarning($this->warningMessage, $i, $this->warningCode);
        }
    }
}
