<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento2\Sniffs\Annotation;

use PHP_CodeSniffer\Files\File;

/**
 * Class to validate annotation format
 */
class AnnotationFormatValidator
{
    /**
     * Gets the short description end pointer position
     */
    private function getShortDescriptionEndPosition(File $phpcsFile, int $shortPtr, int $commentEndPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $shortPtrEnd = $shortPtr;
        for ($i = ($shortPtr + 1); $i < $commentEndPtr; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                if ($tokens[$i]['line'] === $tokens[$shortPtrEnd]['line'] + 1) {
                    $shortPtrEnd = $i;
                } else {
                    break;
                }
            }
        }
        return $shortPtrEnd;
    }

    /**
     * Validates whether the short description has multi lines in description
     */
    private function validateMultiLinesInShortDescription(
        File $phpcsFile,
        int $shortPtr,
        int $commentEndPtr
    ): void {
        $tokens = $phpcsFile->getTokens();
        $shortPtrEnd = $this->getShortDescriptionEndPosition(
            $phpcsFile,
            $shortPtr,
            $commentEndPtr
        );
        $shortPtrEndContent = $tokens[$shortPtrEnd]['content'];
        if (preg_match('/^[a-z]/', (string) $shortPtrEndContent)
            && $shortPtrEnd != $shortPtr
            && !preg_match('/\bSee\b/', (string) $shortPtrEndContent)
            && $tokens[$shortPtr]['line'] + 1 === $tokens[$shortPtrEnd]['line']
            && $tokens[$shortPtrEnd]['code'] !== T_DOC_COMMENT_TAG
        ) {
            $error = 'Short description should not be in multi lines';
            $phpcsFile->addError($error, $shortPtrEnd + 1, 'MethodAnnotation');
        }
    }

    /**
     * Validates whether the spacing between short and long descriptions
     */
    private function validateSpacingBetweenShortAndLongDescriptions(
        File $phpcsFile,
        int $shortPtr,
        int $commentEndPtr,
        array $emptyTypeTokens
    ): void {
        $tokens = $phpcsFile->getTokens();
        $shortPtrEnd = $this->getShortDescriptionEndPosition(
            $phpcsFile,
            $shortPtr,
            $commentEndPtr
        );
        $shortPtrEndContent = $tokens[$shortPtrEnd]['content'];
        if (preg_match('/^[A-Z]/', (string) $shortPtrEndContent)
            && !preg_match('/\bSee\b/', (string) $shortPtrEndContent)
            && $tokens[$shortPtr]['line'] + 1 === $tokens[$shortPtrEnd]['line']
            && $tokens[$shortPtrEnd]['code'] !== T_DOC_COMMENT_TAG
        ) {
            $error = 'There must be exactly one blank line between lines short and long descriptions';
            $phpcsFile->addError($error, $shortPtrEnd + 1, 'MethodAnnotation');
        }
        if ($shortPtrEnd != $shortPtr) {
            $this->validateLongDescriptionFormat($phpcsFile, $shortPtrEnd, $commentEndPtr, $emptyTypeTokens);
        } else {
            $this->validateLongDescriptionFormat($phpcsFile, $shortPtr, $commentEndPtr, $emptyTypeTokens);
        }
    }

    /**
     * Validates short description format
     */
    private function validateShortDescriptionFormat(
        File $phpcsFile,
        int $shortPtr,
        int $stackPtr,
        int $commentEndPtr,
        array $emptyTypeTokens
    ): void {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$shortPtr]['line'] !== $tokens[$stackPtr]['line'] + 1) {
            $error = 'No blank lines are allowed before short description';
            $phpcsFile->addError($error, $shortPtr, 'MethodAnnotation');
        }
        if (strtolower((string) $tokens[$shortPtr]['content']) === '{@inheritdoc}') {
            $error = 'If the @inheritdoc not inline it shouldn’t have braces';
            $phpcsFile->addError($error, $shortPtr, 'MethodAnnotation');
        }
        $shortPtrContent = $tokens[$shortPtr]['content'];
        if (preg_match('/^\p{Ll}/u', (string) $shortPtrContent) === 1) {
            $error = 'Short description must start with a capital letter';
            $phpcsFile->addError($error, $shortPtr, 'MethodAnnotation');
        }
        $this->validateNoExtraNewLineBeforeShortDescription(
            $phpcsFile,
            $stackPtr,
            $commentEndPtr,
            $emptyTypeTokens
        );
        $this->validateSpacingBetweenShortAndLongDescriptions(
            $phpcsFile,
            $shortPtr,
            $commentEndPtr,
            $emptyTypeTokens
        );
        $this->validateMultiLinesInShortDescription(
            $phpcsFile,
            $shortPtr,
            $commentEndPtr
        );
    }

    /**
     * Validates long description format
     */
    private function validateLongDescriptionFormat(
        File $phpcsFile,
        int $shortPtrEnd,
        int $commentEndPtr,
        array $emptyTypeTokens
    ): void {
        $tokens = $phpcsFile->getTokens();
        $longPtr = $phpcsFile->findNext($emptyTypeTokens, $shortPtrEnd + 1, $commentEndPtr - 1, true);
        if (strtolower((string) $tokens[$longPtr]['content']) === '@inheritdoc') {
            $error = '@inheritdoc imports only short description, annotation must have long description';
            $phpcsFile->addError($error, $longPtr, 'MethodAnnotation');
        }
        if ($longPtr !== false && $tokens[$longPtr]['code'] === T_DOC_COMMENT_STRING) {
            if ($tokens[$longPtr]['line'] !== $tokens[$shortPtrEnd]['line'] + 2) {
                $error = 'There must be exactly one blank line between descriptions';
                $phpcsFile->addError($error, $longPtr, 'MethodAnnotation');
            }
            if (preg_match('/^\p{Ll}/u', (string) $tokens[$longPtr]['content']) === 1) {
                $error = 'Long description must start with a capital letter';
                $phpcsFile->addError($error, $longPtr, 'MethodAnnotation');
            }
        }
    }

    /**
     * Validates tags spacing format
     */
    public function validateTagsSpacingFormat(File $phpcsFile, int $commentStartPtr, array $emptyTypeTokens): void
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$commentStartPtr]['comment_tags'][0])) {
            $firstTagPtr = $tokens[$commentStartPtr]['comment_tags'][0];
            $commentTagPtrContent = $tokens[$firstTagPtr]['content'];
            $prevPtr = $phpcsFile->findPrevious($emptyTypeTokens, $firstTagPtr - 1, $commentStartPtr, true);
            if ($tokens[$firstTagPtr]['line'] !== $tokens[$prevPtr]['line'] + 2
                && strtolower((string) $commentTagPtrContent) !== '@inheritdoc'
            ) {
                $error = 'There must be exactly one blank line before tags';
                $phpcsFile->addError($error, $firstTagPtr, 'MethodAnnotation');
            }
        }
    }

    /**
     * Validates tag grouping format
     */
    public function validateTagGroupingFormat(File $phpcsFile, int $commentStartPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $tagGroups = [];
        $groupId = 0;
        $paramGroupId = null;
        foreach ($tokens[$commentStartPtr]['comment_tags'] as $position => $tag) {
            if ($position > 0) {
                $prevPtr = $phpcsFile->findPrevious(
                    T_DOC_COMMENT_STRING,
                    $tag - 1,
                    $tokens[$commentStartPtr]['comment_tags'][$position - 1]
                );
                if ($prevPtr === false) {
                    $prevPtr = $tokens[$commentStartPtr]['comment_tags'][$position - 1];
                }

                if ($tokens[$prevPtr]['line'] !== $tokens[$tag]['line'] - 1) {
                    $groupId++;
                }
            }

            if (strtolower((string) $tokens[$tag]['content']) === '@param') {
                if ($paramGroupId !== null
                    && $paramGroupId !== $groupId) {
                    $error = 'Parameter tags must be grouped together';
                    $phpcsFile->addError($error, $tag, 'MethodAnnotation');
                }
                if ($paramGroupId === null) {
                    $paramGroupId = $groupId;
                }
            }
            $tagGroups[$groupId][] = $tag;
        }
    }

    /**
     * Validates tag aligning format
     */
    public function validateTagAligningFormat(File $phpcsFile, int $commentStartPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $noAlignmentPositions = [];
        $actualPositions = [];
        $stackPtr = null;
        foreach ($tokens[$commentStartPtr]['comment_tags'] as $tag) {
            $content = $tokens[$tag]['content'];
            if (preg_match('/^@/', (string) $content) && ($tokens[$tag]['line'] === $tokens[$tag + 2]['line'])) {
                $noAlignmentPositions[] = $tokens[$tag + 1]['column'] + 1;
                $actualPositions[] = $tokens[$tag + 2]['column'];
                $stackPtr ??= $tag;
            }
        }

        if (!$this->allTagsAligned($actualPositions)
            && !$this->noneTagsAligned($actualPositions, $noAlignmentPositions)) {
            $phpcsFile->addError(
                'Tags visual alignment must be consistent',
                $stackPtr,
                'MethodArguments'
            );
        }
    }

    /**
     * Check whether all docblock params are aligned.
     */
    private function allTagsAligned(array $actualPositions): bool
    {
        return count(array_unique($actualPositions)) === 1;
    }

    /**
     * Check whether all docblock params are not aligned.
     */
    private function noneTagsAligned(array $actualPositions, array $noAlignmentPositions): bool
    {
        return $actualPositions === $noAlignmentPositions;
    }

    /**
     * Validates extra newline before short description
     */
    private function validateNoExtraNewLineBeforeShortDescription(
        File $phpcsFile,
        int $commentStartPtr,
        int $commentEndPtr,
        array $emptyTypeTokens
    ): void {
        $tokens = $phpcsFile->getTokens();
        $prevPtr = $phpcsFile->findPrevious($emptyTypeTokens, $commentEndPtr - 1, $commentStartPtr, true);
        if ($tokens[$prevPtr]['line'] < ($tokens[$commentEndPtr]['line'] - 1)) {
            $error = 'Additional blank lines found at end of the annotation block';
            $phpcsFile->addError($error, $commentEndPtr, 'MethodAnnotation');
        }
    }

    /**
     * Validates structure description format
     */
    public function validateDescriptionFormatStructure(
        File $phpcsFile,
        int $commentStartPtr,
        int $shortPtr,
        int $commentEndPtr,
        array $emptyTypeTokens
    ): void {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$commentStartPtr]['comment_tags'][0])
        ) {
            $commentTagPtr = $tokens[$commentStartPtr]['comment_tags'][0];
            $commentTagPtrContent = $tokens[$commentTagPtr]['content'];
            if ($tokens[$shortPtr]['code'] !== T_DOC_COMMENT_STRING
                && strtolower((string) $commentTagPtrContent) !== '@inheritdoc'
            ) {
                $error = 'Missing short description';
                $phpcsFile->addError($error, $commentStartPtr, 'MethodAnnotation');
            } else {
                $this->validateShortDescriptionFormat(
                    $phpcsFile,
                    $shortPtr,
                    $commentStartPtr,
                    $commentEndPtr,
                    $emptyTypeTokens
                );
            }
        } else {
            $this->validateShortDescriptionFormat(
                $phpcsFile,
                $shortPtr,
                $commentStartPtr,
                $commentEndPtr,
                $emptyTypeTokens
            );
        }
    }
}
