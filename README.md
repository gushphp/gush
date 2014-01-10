## Gush

Gush is a rapid workflow for project maintainers and contributors

[![Build Status](https://travis-ci.org/cordoval/gush.png?branch=master)](https://travis-ci.org/cordoval/gush)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/cordoval/gush/badges/quality-score.png?s=f54effe2042a7eb161b0263322b3b4979d2de900)](https://scrutinizer-ci.com/g/cordoval/gush/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451/mini.png)](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451)

### What is this?

Gush is an app console whose intention is to automate common maintainer and contributor tasks.

- create a Pull Request with a formatted table description of the changes
- create github release notes
- change the base branch of a Pull Request
- automate retrieval of issue's message, title and comments as a text
- merge a PR with just the number and include all github discussion on the commit message
- tagging signing off, change branch name and some queue of common tasks

### Install

There are different ways to use Gush:

#### 1) Installing as a composer global dependency (recommended)

If it is the first time you globally install a dependency then make sure
you follow the instructions [here](http://getcomposer.org/doc/03-cli.md#global).

```bash
$ composer global require 'cordoval/gush=dev-master'
```

#### 2) Cloning this repository and building a PHAR

First, clone Gush repository into your local machine and install the dependencies:

```bash
$ git clone git@github.com:cordoval/gush.git
$ cd gush
$ composer install
```

We recommend you to use [Box](https://github.com/kherge/Box) to build the phar file. Once installed, you can build it easily:

```bash
$ box build -v
```

### Usage

You may want to start by configuring it:

```bash
$ gsh configure                                            Luiss-MacBook-Pro-3 [0:21:06]
Insert your github credentials:
username: cordoval
password:
Cache folder [/Users/cordoval/.gush/cache]:
Configuration saved successfully.
```

Let's go into a repo, take a ticket and send PR and merge it:

```bash
$ gsh p:take 14                                            Luiss-MacBook-Pro-3 [0:26:25]
OUT > Fetching cordoval
OUT > Fetching origin
ERR > Note: checking out 'origin/master'.
You are in 'detached HEAD' state ...
ERR > HEAD is now at 681e0d6... Merge pull request #93 from cordoval/configure-command-test
ERR > Switched to a new branch '14-tests-and-documentation-for-commands'
~ git branch
* 14-tests-and-documentation-for-commands
```

Now we add changes and just commit locally:
```bash
$ git commit -am "added instructions to use gush"
```

Let's send the PR:
```bash
$ gsh p:create                                             Luiss-MacBook-Pro-3 [0:31:01]
Bug fix? [y]
New feature? [n]
BC breaks? [n]
Deprecations? [n]
Tests pass? [y]
Fixed tickets [#000] #14
License [MIT]
Doc PR
PR Title: Added a bit of documentation under usage
ERR > fatal: remote cordoval already exists.
OUT > Fetching cordoval
OUT > Fetching origin
ERR > To git@github.com:cordoval/gush.git
 * [new branch]      14-tests-and-documentation-for-commands -> 14-tests-and-documentation-for-commands
OUT > Branch 14-tests-and-documentation-for-commands set up to track remote branch 14-tests-and-documentation-for-commands from cordoval.
https://github.com/cordoval/gush/pull/94
```

And if we have permissions for merging:
