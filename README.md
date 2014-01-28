## Gush

Gush is a rapid workflow for project maintainers and contributors

[![Build Status](https://travis-ci.org/cordoval/gush.png?branch=master)](https://travis-ci.org/cordoval/gush)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/cordoval/gush/badges/quality-score.png?s=f54effe2042a7eb161b0263322b3b4979d2de900)](https://scrutinizer-ci.com/g/cordoval/gush/)
[![Code Coverage](https://scrutinizer-ci.com/g/cordoval/gush/badges/coverage.png?s=fbd3a27c4b0b05fbb82de21108c44f0cf7a12661)](https://scrutinizer-ci.com/g/cordoval/gush/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451/mini.png)](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451)
[![Latest Stable Version](https://poser.pugx.org/cordoval/gush/v/stable.png)](https://packagist.org/packages/cordoval/gush)
[![Latest Unstable Version](https://poser.pugx.org/cordoval/gush/v/unstable.png)](https://packagist.org/packages/cordoval/gush)
[![Total Downloads](https://poser.pugx.org/cordoval/gush/downloads.png)](https://packagist.org/packages/cordoval/gush)
[![Stories in Ready](https://badge.waffle.io/cordoval/gush.png?label=ready)](https://waffle.io/cordoval/gush)
[![Dependency Status](https://www.versioneye.com/php/cordoval:gush/1.3.0/badge.png)](https://www.versioneye.com/php/cordoval:gush/1.3.0)

<img src="https://f.cloud.github.com/assets/328359/1930603/3bd6fec6-7eb0-11e3-9945-f41820336d8c.png" alt="Gush logo"  width="200px"/>

Logo courtesy from [@maxakawizard](https://twitter.com/MAXakaWIZARD) and [@kotosharic](https://twitter.com/kotosharic)

*Logo explanation is best depicted from this passage from Psalms 78*:

> True, he struck the rock, and water gushed out, streams flowed abundantly, but can he also give us bread?
> Can he supply meat for his people?” When the Lord heard them, he was furious; his fire broke out against
> Jacob, and his wrath rose against Israel, for they did not believe in God or trust in his deliverance.

There first thread of blood and the following are water gushing out of a rock, connecting the Old Testament
prophecy fulfillment in the New Testament at the cross when Jesus was opened on his side and gushed out
water and blood.

### What is this?

Gush is an app console whose intention is to automate common maintainer and contributor tasks.

- creates a Pull Request with a formatted table description of the changes
- creates github release notes
- changes the base branch of a Pull Request
- automates retrieval of issue's message, title and comments as a text
- merges a PR with just the number and includes all github discussion on the commit message
- and much more in the form of intuitive commands!

### Install

Install Gush in two ways:

#### 1) Installing system-wide using composer (recommended)

```bash
$ composer global require 'cordoval/gush=dev-master'
```

If it is the first time you globally install a dependency then make sure
you include `~/.composer/vendor/bin` in $PATH as shown [here](http://getcomposer.org/doc/03-cli.md#global).

### Keep your Gush install always updated:

```bash
$ composer global update cordoval/gush
```

#### 2) Installing as a phar file:

```
$ curl -sS http://gushphp.org/installer | php
$ mv gush.phar /usr/local/bin/gush // optionally
```

or

```
$ curl -sS http://gushphp.org/installer | php -- --install-dir=bin
```

### Usage

You may want to start by configuring it:

```bash
$ gush configure
Insert your github credentials:
username: cordoval
password:
Cache folder [/Users/cordoval/.gush/cache]:
Configuration saved successfully.
```

Let's go into a repo, list issues, take one, send a pull request and merge it:

List it:
```bash
$ cd project_directory
$ gush issue:list
 #   State  PR?  Title                                     User       Assignee   Milestone        Labels       Created
 14  open        Tests and Documentation for Commands      cordoval                                            2014-01-10
```

Take it:
```bash
$ gush i:take 14
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
$ gush p:create
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
$ gush p:merge 94
Pull Request successfully merged
```

### Contributions

Please send your PR using Gush and it will have 100% chances to be merged.
See the [issues list](https://github.com/cordoval/gush/issues?state=open).

### Mailing list and IRC channel

Join the [Mailing List](https://groups.google.com/forum/#!forum/gushphp)
and also on IRC channel #gushphp for discussions and questions.
