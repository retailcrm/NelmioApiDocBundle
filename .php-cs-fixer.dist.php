<?php

require_once __DIR__ . '/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

return Retailcrm\PhpCsFixer\Defaults::rules()
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php_cs.cache');
