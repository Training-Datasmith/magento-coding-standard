# Architecture: magento-coding-standard

## Purpose

A PHP_CodeSniffer ruleset and set of custom sniffs enforcing Magento 2 coding standards. Covers PHP, Less/CSS, HTML, JavaScript (via ESLint), GraphQL, and Magento-specific patterns (DI config, layout XML, legacy API usage).

## Directory Structure

```
Magento2/
  Sniffs/
    Annotation/         — PHPDoc annotation format validation
    Classes/            — Discouraged dependencies, API class rules
    CodeAnalysis/       — Empty blocks, dead code
    Commenting/         — PHPDoc on classes, properties, constants
    Exceptions/         — Direct throw, catch-rethrow, system resource handling
    Functions/          — Discouraged functions, static methods, deprecated calls
    GraphQL/            — Naming conventions for GraphQL type/field/argument names
    Html/               — Knockout binding syntax, void tags, directives
    Legacy/             — Magento 1 patterns forbidden in M2 (Mage::, old layout XML, etc.)
    Less/               — LESS/CSS style rules (indentation, color definitions, properties sort)
    Methods/            — Deprecated model methods
    Namespaces/         — Import restrictions (test namespace in production code)
    NamingConvention/   — Interface naming (must end in Interface), reserved words
    Performance/        — Anti-patterns like array_merge() in foreach loops
    PHP/                — Final classes, autovivification, literal namespaces, var keyword
    Security/           — XSS template checks, superglobal access, insecure functions
    ...
  Helpers/
    Assert.php                           — Assertion helpers used by sniffs
    Commenting/PHPDocFormattingValidator.php — Validates PHPDoc structure
    Tokenizer/                           — Parameter and variable tokenizers
  Internal/
    Cache.php                            — Sniff-result caching
  Rector/
    Src/                                 — Rector rules for automated Magento code migrations
    Tests/                               — Tests for Rector rules
  ruleset.xml                            — Main PHPCS ruleset
  ruleset-functional.xml                 — Functional test ruleset
```

## Key Design Decisions

- **PHP_CodeSniffer sniff architecture**: Each `*Sniff` implements `PHP_CodeSniffer\Sniffs\Sniff` with `register()` (token types to listen for) and `process()` (inspection logic)
- **Rector integration**: Automated fixers in `Rector/Src/` handle mechanical migrations (deprecated API calls, null parameter removal) that PHPCS can only warn about
- **Multi-language**: PHPCS handles PHP; separate config files delegate HTML, Less, and JS to other linters (ESLint, Stylelint)

## Extension Points

- Add a new sniff in the appropriate `Sniffs/` subdirectory and reference it in `ruleset.xml`
- Extend `Abstract_Api_Sniff` for sniffs that need to check `@api` annotations

## Dependency Flow

```
phpcs --standard=Magento2 src/
  → PHP_CodeSniffer
  → Magento2/ruleset.xml
  → Magento2/Sniffs/**/*Sniff.php (one per token-based rule)
```
