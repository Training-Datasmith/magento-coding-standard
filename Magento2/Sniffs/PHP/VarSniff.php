<?php

declare(strict_types=1);
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

namespace Magento2\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Detects possible usage of 'var' language construction.
 */
class VarSniff implements Sniff
{
    /**
     * String representation of warning.
     *
     * @var string
     */
    protected $warningMessage = 'Use of var class variables is discouraged.';

    /**
     * Warning violation code.
     *
     * @var string
     */
    protected $warningCode = 'FoundVar';

    /**
     * @inheritdoc
     */
    public function register(): array
    {
        return [T_VAR];
    }

    /**
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $phpcsFile->addWarning($this->warningMessage, $stackPtr, $this->warningCode);
    }
}
