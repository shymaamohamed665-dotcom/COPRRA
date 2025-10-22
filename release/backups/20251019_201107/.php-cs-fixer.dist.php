<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder($finder);
