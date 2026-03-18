<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types = 1);

namespace Magento2\Sniffs\Legacy;

use DOMDocument;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use SimpleXMLElement;

class ClassReferencesInConfigurationFilesSniff implements Sniff
{
    private const ERROR_MESSAGE_CONFIG = 'Incorrect format of PHP class reference';
    private const ERROR_CODE_CONFIG = 'IncorrectClassReference';
    private const ERROR_MESSAGE_MODULE = 'Incorrect format of module reference';
    private const ERROR_CODE_MODULE = 'IncorrectModuleReference';

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
     * @inheritdoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        if ($stackPtr > 0) {
            return;
        }

        // We need to format the incoming XML to avoid tags split into several lines. In that case, PHP's DOMElement
        // returns the position of the closing /> as the position of the tag, and we need the position of <
        // instead, as it is the one we compare with $stackPtr later on.
        $xml = simplexml_load_string($this->getFormattedXML($phpcsFile));
        if ($xml === false) {
            $phpcsFile->addError(
                sprintf(
                    "Couldn't parse contents of '%s', check that they are in valid XML format",
                    $phpcsFile->getFilename(),
                ),
                $stackPtr,
                self::ERROR_CODE_CONFIG
            );
        }

        $classes = $this->collectClassesInConfig($xml);
        $this->assertNonFactoryName($phpcsFile, $classes);

        $modules = $this->getValuesFromXmlTagAttribute($xml, '//@module', 'module');
        $this->assertNonFactoryNameModule($phpcsFile, $modules);
    }

    /**
     * Check whether specified class names are right according PSR-1 Standard.
     */
    private function assertNonFactoryName(File $phpcsFile, array $elements): void
    {
        foreach ($elements as $element) {
            if (stripos((string) $element['value'], 'Magento') === false) {
                continue;
            }
            if (preg_match('/^([A-Z][a-z\d\\\\]+)+$/', (string) $element['value']) !== 1) {
                $phpcsFile->addError(
                    self::ERROR_MESSAGE_CONFIG,
                    $element['lineNumber'],
                    self::ERROR_CODE_CONFIG,
                );
            }
        }
    }

    /**
     * Check whether specified class names in modules are right according PSR-1 Standard.
     */
    private function assertNonFactoryNameModule(File $phpcsFile, array $classes): void
    {
        foreach ($classes as $element) {
            if (preg_match('/^([A-Z][A-Za-z\d_]+)+$/', (string) $element['value']) !== 1) {
                $phpcsFile->addError(
                    self::ERROR_MESSAGE_MODULE,
                    $element['lineNumber'],
                    self::ERROR_CODE_MODULE,
                );
            }
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
        $doc->loadXML($phpcsFile->getTokensAsString(0, count($phpcsFile->getTokens())));
        return $doc->saveXML();
    }

    /**
     * Parse an XML for references to PHP class names in selected tags or attributes
     */
    private function collectClassesInConfig(SimpleXMLElement $xml): array
    {
        $classes = $this->getValuesFromXmlTagContent(
            $xml,
            '
            /config//resource_adapter | /config/*[not(name()="sections")]//class[not(ancestor::observers)]
                | //model[not(parent::connection)] | //backend_model | //source_model | //price_model
                | //model_token | //writer_model | //clone_model | //frontend_model | //working_model
                | //admin_renderer | //renderer',
        );

        $classes = array_merge(
            $classes,
            $this->getValuesFromXmlTagAttribute(
                $xml,
                '//@backend_model',
                'backend_model'
            ),
            $this->getValuesFromXmlTagAttribute(
                $xml,
                '/config//preference',
                'type'
            ),
            $this->getValuesFromXmlTagName(
                $xml,
                '/logging/*/expected_models/* | /logging/*/actions/*/expected_models/*',
            )
        );

        return array_map(
            function (array $extendedNode): array {
                $extendedNode['value'] = explode('::', trim((string) $extendedNode['value']))[0];
                return $extendedNode;
            },
            $classes
        );
    }

    /**
     * Extract value from tag contents which exist in the XML path
     */
    private function getValuesFromXmlTagContent(SimpleXMLElement $xml, string $xPath): array
    {
        $nodes = $xml->xpath($xPath) ?: [];
        return array_map(fn(\SimpleXMLElement $item) => [
            'value' => (string)$item,
            'lineNumber' => dom_import_simplexml($item)->getLineNo()-1,
        ], $nodes);
    }

    /**
     * Extract value from tag names which exist in the XML path
     */
    private function getValuesFromXmlTagName(SimpleXMLElement $xml, string $xPath): array
    {
        $nodes = $xml->xpath($xPath) ?: [];
        return array_map(fn(\SimpleXMLElement $item) => [
            'value' => $item->getName(),
            'lineNumber' => dom_import_simplexml($item)->getLineNo()-1,
        ], $nodes);
    }

    /**
     * Extract value from tag attributes which exist in the XML path
     */
    private function getValuesFromXmlTagAttribute(SimpleXMLElement $xml, string $xPath, string $attr): array
    {
        $nodes = $xml->xpath($xPath) ?: [];
        return array_map(function (\SimpleXMLElement $item) use ($attr) {
            $nodeArray = (array)$item;
            if (isset($nodeArray['@attributes'][$attr])) {
                return [
                    'value' => $nodeArray['@attributes'][$attr],
                    'lineNumber' => dom_import_simplexml($item)->getLineNo()-1,
                ];
            }
        }, $nodes);
    }
}
