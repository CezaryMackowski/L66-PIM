<?php

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__.DIRECTORY_SEPARATOR.'./src')
    ->in(__DIR__.DIRECTORY_SEPARATOR.'./tests')
;

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        '@PSR2' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'date_time_immutable' => true,
        'fully_qualified_strict_types' => true,
        'no_useless_else' => true,
        'ternary_to_null_coalescing' => true,
        'void_return' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => false,
        'return_type_declaration' => [
            'space_before' => 'none'
        ],
        'phpdoc_to_comment' => false,
        'phpdoc_align' => false,
        'align_multiline_comment' => false,
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => false
        ],
        'global_namespace_import' => true,
        'single_blank_line_at_eof' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arguments',
                'arrays',
                'match',
                'parameters',
            ]
        ],
        'array_indentation' => true,
    ])
    ->setFinder($finder)
    ;
