<?php

namespace Gush\Tests\Command;

use Gush\Command\ReleaseListCommand;

class ReleaseListCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->httpClient->whenGet('repos/cordoval/gush/releases')->thenReturn(array(
            array(
                'id' => '123',
                'name' => 'This is a Release',
                'tag_name' => 'Tag name',
                'target_commitish' => '123123',
                'draft' => true,
                'prerelease' => 'yes',
                'created_at' => '2014-01-05',
                'published_at' => '2014-01-05',
            ),
        ));

        $tester = $this->getCommandTester(new ReleaseListCommand());
        $tester->execute(array());

        $this->assertEquals(trim(<<<EOT
         
 ID   Name               Tag       Commitish  Draft  Prerelease  Created     Published  
         
 123  This is a Release  Tag name  123123     yes    yes         2014-01-05  2014-01-05 
EOT
        ), trim($tester->getDisplay()));
    }
}
