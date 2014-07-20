<?php

/**
 * This file is part of Gush.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\CS\FixerInterface;

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('LICENSE')
    ->notName('README.md')
    ->notName('.php_cs')
    ->notName('composer.*')
    ->notName('phpunit.xml*')
    ->notName('box.json')
    ->notName('*.phar')
    ->notName('installer')
    ->notName('OutputFixtures.php')
    ->exclude('web')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()->finder($finder);
