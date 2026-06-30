<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * @author    Fexa AI <support@fexaai.com>
 * @copyright 2025 Fexa AI
 * @license   Proprietary
 */

/*
 * PHP-CS-Fixer config for the Fexa AI Connector module.
 *
 * Clears the PrestaShop validator's "Standards" + "Licenses" sections in one pass:
 *   - stamps the file header (adds the missing @author / @copyright / @license),
 *   - normalises line endings to LF (line_ending),
 *   - orders + de-duplicates imports (ordered_imports, no_unused_imports),
 *   - imports global classes (global_namespace_import),
 *   - aligns docblocks, trailing commas, cast spacing, single quotes, etc.
 *
 * Install + run (in your PHP 8.1+ environment, NOT committed to the package):
 *   composer require --dev friendsofphp/php-cs-fixer
 *   vendor/bin/php-cs-fixer fix
 * Then re-zip and resubmit to https://validator.prestashop.com
 */

$header = <<<'EOF'
Copyright (c) 2025 Fexa AI

All Rights Reserved.

This module is proprietary software owned by Fexa AI.

@author    Fexa AI <support@fexaai.com>
@copyright 2025 Fexa AI
@license   Proprietary
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', 'views/.vite'])
    ->notName('*.min.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules([
        '@Symfony' => true,
        'header_comment' => [
            'header' => $header,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'bottom',
        ],
        // Header must sit immediately after <?php (the validator: "no blank lines before the file comment").
        'blank_line_after_opening_tag' => false,
        'line_ending' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
        'trailing_comma_in_multiline' => true,
        'cast_spaces' => ['space' => 'single'],
        'single_quote' => true,
        'no_extra_blank_lines' => true,
        'no_blank_lines_after_phpdoc' => true,
    ])
    ->setFinder($finder);
