## Gush

Gush is a rapid workflow for project maintainers and contributors

[![Build Status](https://travis-ci.org/cordoval/gush.png?branch=master)](https://travis-ci.org/cordoval/gush)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/cordoval/gush/badges/quality-score.png?s=f54effe2042a7eb161b0263322b3b4979d2de900)](https://scrutinizer-ci.com/g/cordoval/gush/)
[![Code Coverage](https://scrutinizer-ci.com/g/cordoval/gush/badges/coverage.png?s=fbd3a27c4b0b05fbb82de21108c44f0cf7a12661)](https://scrutinizer-ci.com/g/cordoval/gush/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451/mini.png)](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451)
[![Latest Stable Version](https://poser.pugx.org/cordoval/gush/v/stable.png)](https://packagist.org/packages/cordoval/gush)
[![Latest Unstable Version](https://poser.pugx.org/cordoval/gush/v/unstable.png)](https://packagist.org/packages/cordoval/gush)
[![Total Downloads](https://poser.pugx.org/cordoval/gush/downloads.png)](https://packagist.org/packages/cordoval/gush)

<img src="https://f.cloud.github.com/assets/328359/1930603/3bd6fec6-7eb0-11e3-9945-f41820336d8c.png" alt="Gush logo"  width="200px"/>

Logo courtesy from [@maxakawizard](https://twitter.com/MAXakaWIZARD) and [@kotosharic](https://twitter.com/kotosharic)

Logo explanation is best depicted from this passage from Psalm 78:

True, he struck the rock, and water gushed out, streams flowed abundantly, but can he also give us bread?
Can he supply meat for his people?” When the Lord heard them, he was furious; his fire broke out against
Jacob, and his wrath rose against Israel, for they did not believe in God or trust in his deliverance.

There first thread of blood and the following are water gushing out of a rock, connecting the Old Testament
prophecy fulfillment in the New Testament at the cross when Jesus was opened on his side and gushed out
water and blood.

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

We can use [Box](https://github.com/kherge/Box) to build the phar file. Once installed, you can build it easily:

```bash
$ box build -v
```
### Upgrade to latest version

```bash
$ composer global update cordoval/gush
```

**Note:** if you installed it any other way you would need to install it again.

### Usage

You may want to start by configuring it:

```bash
$ gsh configure
Insert your github credentials:
username: cordoval
password:
Cache folder [/Users/cordoval/.gush/cache]:
Configuration saved successfully.
```

Let's go into a repo, list, take ticket, send PR and merge it:

List it:
```bash
$ cd project_directory
$ gsh issue:list
 #   State  PR?  Title                                     User       Assignee   Milestone        Labels       Created
 14  open        Tests and Documentation for Commands      cordoval                                            2014-01-10
```

Take it:
```bash
$ gsh p:take 14
OUT > Fetching cordoval
OUT > Fetching origin
ERR > Note: checking out 'origin/master'.
You are in 'detached HEAD' state ...
ERR > HEAD is now at 681e0d6... Merge pull request #93 from cordoval/configure-command-test
ERR > Switched to a new branch '14-tests-and-documentation-for-commands'
~ git branch
* 14-tests-and-documentation-for-commands
```

Do your changes and commit them:
```bash
$ git commit -am "added instructions to use gush"
```

Send PR:
```
$ gsh p:create
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

Merge it:
```bash
$ gsh p:merge 94
Pull Request successfully merged
```

### Contributions

Please send your PR using Gush and it will have 100% chances to be merged.
See the [issues](https://github.com/cordoval/gush/issues?state=open).

### Mailing list and IRC channel

Join the [Mailing List](https://groups.google.com/forum/#!forum/gushphp)
and also on IRC channel #gushphp for discussions and questions.
