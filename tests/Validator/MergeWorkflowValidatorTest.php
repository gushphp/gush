<?php

namespace Gush\Tests\Validator;

use Gush\Exception\MergeWorkflowException;
use Gush\Validator\MergeWorkflowValidator;

final class MergeWorkflowValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideValidSemverBranches
     *
     * @param string $source
     * @param string $target
     */
    public function testValidSemverPresetWithNoBranches($source, $target)
    {
        $validator = new MergeWorkflowValidator(MergeWorkflowValidator::PRESET_SEMVER);

        $this->assertTrue($validator->validate($source, $target));
    }

    public function provideValidSemverBranches()
    {
        return [
            ['1.0', '2.0'],
            ['1.0', '1.1'],
            ['1.0', '2.x'],
            ['1.x', '2.x'],
            ['1.x', '2.5'],
            ['1.5', '2.x'],
            ['x.5', 'x.0'], // version is invalid, so its ignored
            ['develop', 'master'], // version is invalid, so its ignored
        ];
    }

    /**
     * @dataProvider provideInvalidSemverBranches
     *
     * @param string $source
     * @param string $target
     * @param string $invalidPart
     */
    public function testInvalidSemverPresetWithNoBranches($source, $target, $invalidPart)
    {
        $validator = new MergeWorkflowValidator(MergeWorkflowValidator::PRESET_SEMVER);

        $this->setExpectedException(
            MergeWorkflowException::class,
            sprintf(
                'Semver: %1$s version of source "%2$s" is higher then %1$s version of target "%3$s".',
                $invalidPart,
                $source,
                $target
            )
        );

        $validator->validate($source, $target);
    }

    public function provideInvalidSemverBranches()
    {
        return [
            ['2.0', '1.0', 'major'],
            ['2.x', '1.x', 'major'],
            ['2.5', '2.0', 'minor'],
            ['1.8', '1.5', 'minor'],
        ];
    }

    // GitFlow

    /**
     * @dataProvider provideValidGitFlowBranches
     *
     * @param string $source
     * @param string $target
     */
    public function testValidGitFlowPresetWithNoBranches($source, $target)
    {
        $validator = new MergeWorkflowValidator(MergeWorkflowValidator::PRESET_GIT_FLOW);

        $this->assertTrue($validator->validate($source, $target));
    }

    public function provideValidGitFlowBranches()
    {
        return [
            ['develop', 'master'],
            ['master', 'develop'],
            ['master', 'master'],
            ['hotfix-255', 'master'],
            ['release-255', 'master'],
            ['hotfix-255', 'develop'],
            ['release-255', 'develop'],
            ['my-feature', 'develop'],
            // Semver branches, ignored
            ['1.0', '2.0'],
            ['1.x', '2.x'],
            ['2.8', '2.5'],
        ];
    }

    /**
     * @dataProvider provideInvalidGitFlowBranches
     *
     * @param string $source
     */
    public function testInvalidGitFlowPresetWithNoBranches($source)
    {
        $validator = new MergeWorkflowValidator(MergeWorkflowValidator::PRESET_GIT_FLOW);

        $this->setExpectedException(
            MergeWorkflowException::class,
            'Git-flow: Only "develop", "hotfix-" or "release-" branches are allowed to be merged into master.'
        );

        $validator->validate($source, 'master');
    }

    public function provideInvalidGitFlowBranches()
    {
        return [
            ['my-feature'],
            ['2.5'],
        ];
    }

    /**
     * @dataProvider provideAllowedBranches
     *
     * @param string $source
     * @param string $target
     */
    public function testBranchRestrictionsWithAllowUnknown($source, $target)
    {
        $validator = new MergeWorkflowValidator(
            MergeWorkflowValidator::PRESET_NONE,
            [
                'master' => ['master', 'develop'],
                'develop' => ['develop', 'new-feature'],
            ]
        );

        $this->assertTrue($validator->validate($source, $target));
    }

    public function provideAllowedBranches()
    {
        return [
            ['master', 'develop'],
            ['master', 'master'],
            ['develop', 'new-feature'],
            ['2.5', '2.8'], // not mentioned, so allowed
        ];
    }

    /**
     * @dataProvider provideDeniedBranches
     *
     * @param string $source
     * @param string $target
     */
    public function testBranchRestrictionsWithDeniedBranchAndAllowUnknown($source, $target)
    {
        $restrictions = [
            'master' => ['master', 'develop'],
            'develop' => ['develop', 'new-feature'],
        ];

        $validator = new MergeWorkflowValidator(MergeWorkflowValidator::PRESET_NONE, $restrictions);

        $this->setExpectedException(
            MergeWorkflowException::class,
            sprintf(
                'Branches: Only branches "%s" are allowed to be merged into "%s".',
                implode('", "', $restrictions[$source]),
                $target
            )
        );

        $validator->validate($source, $target);
    }

    public function provideDeniedBranches()
    {
        return [
            ['develop', 'master'],
            ['master', 'new-feature'],
        ];
    }

    public function testBranchRestrictionsDeniesAccessForUnknownBranchWithPolicyDeny()
    {
        $restrictions = [
            'master' => ['master', 'develop'],
            'develop' => ['develop', 'new-feature'],
        ];

        $validator = new MergeWorkflowValidator(
            MergeWorkflowValidator::PRESET_NONE,
            $restrictions,
            MergeWorkflowValidator::BRANCH_POLICY_DENY
        );

        $this->assertTrue($validator->validate('master', 'master'));
        $this->assertTrue($validator->validate('master', 'develop'));
        $this->assertTrue($validator->validate('develop', 'new-feature'));

        $this->setExpectedException(
            MergeWorkflowException::class,
            'No branch constraint is set for source "new-feature" and policy denies merging unknown branches.'
        );

        $validator->validate('new-feature', 'develop');
    }
}
