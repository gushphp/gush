<?php

/*
 * This file is part of Gush package.
 *
 * (c) 2013-2015 Luis Cordova <cordoval@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gush\Validator;

use Gush\Exception\MergeWorkflowException;
use Gush\Exception\UserException;
use Gush\Util\StringUtil;

final class MergeWorkflowValidator
{
    const PRESET_SEMVER = 'semver';
    const PRESET_GIT_FLOW = 'git-flow';
    const PRESET_NONE = 'none';

    const BRANCH_POLICY_ALLOW = 'allow-merge';
    const BRANCH_POLICY_DENY = 'deny-merge';

    /**
     * The regular expression for a valid version number.
     *
     * Eg: 1.0, 1.x but not x.x
     */
    const VERSION_REGEX = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>x|0|[1-9]\d*)$/';

    /**
     * @var string
     */
    private $preset;

    /**
     * @var string[]
     */
    private $branches;

    /**
     * @var string
     */
    private $policy;

    /**
     * Constructor.
     *
     * @param string   $preset
     * @param string[] $branches
     * @param string   $unknownBranchPolicy
     *
     * @throws UserException
     */
    public function __construct($preset, array $branches = [], $unknownBranchPolicy = self::BRANCH_POLICY_ALLOW)
    {
        if (!in_array($preset, [self::PRESET_SEMVER, self::PRESET_GIT_FLOW, self::PRESET_NONE], true)) {
            throw new \InvalidArgumentException(
                [
                    'Merge workflow preset is not valid.',
                    'Supported workflow presets are: semver, git-flow or none.'
                ]
            );
        }

        if (!in_array($unknownBranchPolicy, [self::BRANCH_POLICY_ALLOW, self::BRANCH_POLICY_DENY], true)) {
            throw new \InvalidArgumentException(
                [
                    'Merge workflow-policy is not valid.',
                    'Supported workflow policies are: allow-merge or deny-merge.'
                ]
            );
        }

        if (self::BRANCH_POLICY_DENY === $unknownBranchPolicy && 0 === count($branches)) {
            throw new \InvalidArgumentException(
                'Merge workflow-policy is set to "deny-merge" but no branches are configured.'
            );
        }

        $this->preset = $preset;
        $this->branches = $branches;
        $this->policy = $unknownBranchPolicy;
    }

    public function validate($source, $target)
    {
        $validateBranches = count($this->branches) > 1;

        if (self::PRESET_NONE !== $this->preset) {
            $preset = 'preset'.StringUtil::concatWords($this->preset);

            // When the policy is deny-merge for unknown branches
            // the preset is used as primary, if it grants access then branches are not checked,
            // else you would need configure branches for all checks!
            if (true === $this->$preset($source, $target)) {
                $validateBranches = self::BRANCH_POLICY_DENY === $this->policy ? false : $validateBranches;
            }
        }

        if ($validateBranches) {
            $this->validateBranches($source, $target, $this->branches, $this->policy);
        }

        return true;
    }

    protected function presetSemver($source, $target)
    {
        if (0 === preg_match(self::VERSION_REGEX, $source, $sourceMatch) ||
            0 === preg_match(self::VERSION_REGEX, $target, $targetMatch)
        ) {
            return; // cannot validate branches, abstain decision
        }

        if ((int) $sourceMatch['major'] > (int) $targetMatch['major']) {
            throw new MergeWorkflowException(
                [
                    sprintf('Workflow violation: Cannot merge "%s" into "%s".', $source, $target),
                    sprintf(
                        'Semver: major version of source "%s" is higher then major version of target "%s".',
                        $source,
                        $target
                    )
                ]
            );
        }

        if ('x' === $sourceMatch['minor'] || 'x' === $targetMatch['minor']) {
            return true; // allow merge
        }

        if ((int) $sourceMatch['minor'] > (int) $targetMatch['minor']) {
            throw new MergeWorkflowException(
                [
                    sprintf('Workflow violation: Cannot merge "%s" into "%s".', $source, $target),
                    sprintf(
                        'Semver: minor version of source "%s" is higher then minor version of target "%s".',
                        $source,
                        $target
                    )
                ]
            );
        }
    }

    protected function presetGitFlow($source, $target)
    {
        if ($target !== 'master') {
            return; // abstain decision
        }

        // Source can be master (from another org/repo)
        if ($source === 'master' || $source === 'develop' || preg_match('/^(hotfix|release)-/', $source)) {
            return true; // allow to merge into master
        }

        throw new MergeWorkflowException(
            [
                sprintf('Workflow violation: Cannot merge "%s" into "%s".', $source, $target),
                'Git-flow: Only "develop", "hotfix-" or "release-" branches are allowed to be merged into master.'
            ]
        );
    }

    private function validateBranches($source, $target, array $branches, $policy)
    {
        if (array_key_exists($source, $branches)) {
            $acceptedBranch = (array) $branches[$source];

            if (!in_array($target, $acceptedBranch, true)) {
                throw new MergeWorkflowException(
                    [
                        sprintf('Workflow violation: Cannot merge "%s" into "%s".', $source, $target),
                        sprintf(
                            'Branches: Only branches "%s" are allowed to be merged into "%s".',
                            implode('", "', $acceptedBranch),
                            $target
                        ),
                    ]
                );
            }

            return; // allow merge
        }

        if (self::BRANCH_POLICY_DENY === $policy) {
            throw new MergeWorkflowException(
                [
                    sprintf('Workflow violation: Cannot merge "%s" into "%s".', $source, $target),
                    sprintf(
                        'No branch constraint is set for source "%s" and policy denies merging unknown branches.',
                        $source
                    ),
                ]
            );
        }
    }
}
