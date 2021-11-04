<?php

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR1'             => true,
        '@PSR2'             => true,
        '@Symfony'          => true,
        '@PhpCsFixer'       => true,
        'strict_comparison' => true,
        'array_syntax'      => [
            'syntax' => 'short',
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_no_empty_return' => false,
        'phpdoc_to_comment'      => false,
        'concat_space'           => [
            'spacing' => 'one',
        ],
        'blank_line_before_return'    => true,
        'hash_to_slash_comment'       => true,
        'protected_to_private'        => true,
        'linebreak_after_opening_tag' => true,
        'yoda_style'                  => false,
        'global_namespace_import'     => [
            'import_classes'   => true,
            'import_constants' => null,
            'import_functions' => null,
        ],
        'binary_operator_spaces' => [
            'default' => 'align',
        ],
    ])
    ->setUsingCache(false)
;
