<?php

/**
 * This file is part of Gush package.
 *
 * (c) 2013-2014 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Tests\Helper;

use Gush\Helper\MetaHelper;

class MetaHelperTest extends \PHPUnit_Framework_TestCase
{
    static private $header;

    /**
     * @var MetaHelper
     */
    private $helper;

    public static function setUpBeforeClass()
    {
        self::$header = "/**\n * This file is part of Your Package package.\n *\n *".
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
            $this->helper->filterFilesList($fileList, ['tests/', 'src/bootstrap.php'])
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

/**
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

/**
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

/**
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Gush\Meta\Meta
     */
    private function getMetaForPhp()
    {
        $meta = $this->getMock('Gush\Meta\Meta');

        $meta
            ->expects($this->any())
            ->method('getStartDelimiter')
            ->will($this->returnValue('/**'))
        ;

        $meta
            ->expects($this->any())
            ->method('getDelimiter')
            ->will($this->returnValue('*'))
        ;

        $meta
            ->expects($this->any())
            ->method('getEndDelimiter')
            ->will($this->returnValue('*/'))
        ;

        $meta
            ->expects($this->any())
            ->method('getStartTokenRegex')
            ->will($this->returnValue('{^(<\?(php)?\s+)|<%|(<\?xml[^>]+)}i'))
        ;

        return $meta;
    }
}
