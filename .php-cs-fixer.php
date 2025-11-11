<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->exclude('Tests')
    ->exclude('DependencyInjection')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,

        // Array notation
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // Imports
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha'
        ],
        'no_unused_imports' => true,
        'no_leading_import_slash' => true,
        'single_import_per_statement' => true,

        // Operators
        'not_operator_with_successor_space' => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align_single_line_minimal',
                '=' => 'single_space',
            ]
        ],
        'unary_operator_spaces' => true,
        'concat_space' => ['spacing' => 'one'],

        // Code structure
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try', 'if', 'switch', 'for', 'foreach', 'while', 'do'],
        ],
        'blank_line_after_opening_tag' => true,
        'blank_line_after_namespace' => true,
        'single_blank_line_at_eof' => true,
        'no_extra_blank_lines' => [
            'tokens' => ['extra', 'throw', 'use', 'use_trait']
        ],

        // PHPDoc
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_summary' => true,
        'phpdoc_trim' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'alpha'
        ],

        // Casting
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'short_scalar_cast' => true,

        // Classes and methods
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one']
        ],
        'method_chaining_indentation' => true,
        'no_blank_lines_after_class_opening' => true,
        'single_class_element_per_statement' => true,
        'visibility_required' => ['elements' => ['property', 'method', 'const']],

        // Control structures
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,

        // Functions
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'function_typehint_space' => true,
        'lambda_not_used_import' => true,
        'return_type_declaration' => ['space_before' => 'none'],

        // Strings
        'single_quote' => true,
        'string_line_ending' => true,

        // Whitespace
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'single_line_after_imports' => true,

        // Other
        'encoding' => true,
        'full_opening_tag' => true,
        'no_closing_tag' => true,
        'line_ending' => true,
        'no_php4_constructor' => true,
        'modernize_types_casting' => true,
        'native_function_casing' => true,
        'native_function_type_declaration_casing' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'semicolon_after_instruction' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
