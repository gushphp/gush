<?php

/*
 * This file is part of Gush package.
 *
 * (c) Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\MetaHelper;
use Gush\Meta\Base;
use Gush\Meta\Meta;

class MetaHelperTest extends \PHPUnit_Framework_TestCase
{
    private static $header;

    /**
     * @var MetaHelper
     */
    private $helper;

    public static function setUpBeforeClass()
    {
        self::$header = "/*\n * This file is part of Your Package package.\n *\n *".
            " (c) 2009-2014 You <you@yourdomain.com>\n *\n".
            " * This source file is subject to the MIT license that is bundled\n".
            " * with this source code in the file LICENSE.\n */\n\n"
        ;
    }

    protected function setUp()
    {
        $this->helper = new MetaHelper([]);
    }

    /**
     * @dataProvider getUpdatableFiles
     */
    public function testIsUpdatable($content, $valid)
    {
        $meta = $this->getMetaForPhp();

        if ($valid) {
            $this->assertTrue($this->helper->isUpdatable($meta, $content), 'content is updatable');
        } else {
            $this->assertFalse($this->helper->isUpdatable($meta, $content), 'content is not updatable');
        }
    }

    public function testFilterFilesList()
    {
        $fileList = [
            'src/Tester/QuestionToken.php',
            'src/Util/ArrayUtil.php',
            'src/bootstrap.php',
            'bootstrap.inc',
            'assets/file.js',
            'tests/ApplicationTest.php',
            'tests/Command/BaseTestCase.php',
            'tests/Command/Branch/BranchChangelogCommandTest.php',
            'tests/Command/Branch/BranchDeleteCommandTest.php',
        ];

        $expectedFileList = [
            'src/Tester/QuestionToken.php',
            'src/Util/ArrayUtil.php',
        ];

        $this->assertEquals(
            $expectedFileList,
            $this->helper->filterFilesList(
                $fileList,
                [
                    '{^tests/.+}', // regex
                    'src/bootstrap.php', // glob
                    'bootstrap.inc', // glob
                    '*.js', // glob
                ]
            )
        );
    }

    public function testUpdateContentPhpFileWithNoHeader()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $expected = <<<'EOT'
<?php

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $this->assertEquals(ltrim($expected), $this->helper->updateContent($meta, self::$header, $input));
    }

    public function testUpdateContentPhpFileWithNoHeaderAndStrictType()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php

declare(strict_types = 1);

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $expected = <<<'EOT'
<?php

declare(strict_types = 1);

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $this->assertEquals(ltrim($expected), $this->helper->updateContent($meta, self::$header, $input));
    }

    public function testUpdateContentPhpFileWithHeader()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */



namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $expected = <<<'EOT'
<?php

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $this->assertEquals($expected, $this->helper->updateContent($meta, self::$header, $input));
    }

    public function testUpdateContentPhpFileWithHeaderWithStrictType()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php 

declare(strict_types = 1);

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */



namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $expected = <<<'EOT'
<?php 

declare(strict_types = 1);

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $this->assertEquals($expected, $this->helper->updateContent($meta, self::$header, $input));
    }

    public function testUpdateContentPhpFileWithHeaderWithStrictTypeAndEncoding()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php declare(strict_types = 1);
declare(encoding='ISO-8859-1');

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */



namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $expected = <<<'EOT'
<?php declare(strict_types = 1);
declare(encoding='ISO-8859-1');

/*
 * This file is part of Your Package package.
 *
 * (c) 2009-2014 You <you@yourdomain.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Tester;

use Gush\Tester\HttpClient\TestHttpClient;

EOT;

        $this->assertEquals($expected, $this->helper->updateContent($meta, self::$header, $input));
    }

    public function testUpdateContentPhpFileWithPreservedHeader()
    {
        $meta = $this->getMetaForPhp();

        $input = <<<'EOT'
<?php

/*!
 * This file is part of Your Package package.
 */

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $expected = <<<'EOT'
<?php

/*!
 * This file is part of Your Package package.
 */

namespace Test;

class MetaTest
{
    private $test;

    public function __construct($test)
    {
        $this->test = $test;
    }
}

EOT;

        $this->assertEquals(ltrim($expected), $this->helper->updateContent($meta, self::$header, $input));
    }

    public static function getUpdatableFiles()
    {
        $validContent = <<<'EOT'
<?php
%s

namespace Test;

EOT;

        $inValidContent = <<<'EOT'
%s

namespace Test;

EOT;

        return [
            [sprintf($validContent, "/*\n * This file is part of Your Package package.\n */"), true],
            [sprintf($validContent, "/*\n * This file is part of Your Package package.\n */"), true],
            [sprintf($validContent, ''), true],
            [sprintf($validContent, "/*!\n * This file is part of Your Package package.\n */"), false],
            [sprintf($inValidContent, "/*\n * This file is part of Your Package package.\n */"), false],
            [sprintf($inValidContent, "/*\n * This file is part of Your Package package.\n */"), false],
            [sprintf($inValidContent, "/*!\n * This file is part of Your Package package.\n */"), false],
        ];
    }

    private function getMetaForPhp(): Base
    {
        return new Base();
    }
}
