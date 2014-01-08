<?php

use Symfony\CS\FixerInterface;

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('LICENSE')
    ->notName('README.md')
    ->notName('.php_cs')
    ->notName('composer.*')
    ->notName('phpunit.xml*')
    ->notName('box.json.dist')
    ->notName('sculpin.*')
    ->notName('*.phar')
    ->exclude('vendor')
    ->exclude('source')
    ->exclude('output*')
    ->exclude('.sculpin')
    ->notName('OutputFixtures.php')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()->finder($finder);