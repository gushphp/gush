## Gush

Gush is a rapid git workflow for project maintainers and contributors that works
with backend support for GitHub, Enterprise Github, and more!

Check Gush in action [here](https://vimeo.com/88283752) and [here](https://vimeo.com/85439368)!

[![Build Status](https://travis-ci.org/gushphp/gush.png?branch=master)](https://travis-ci.org/gushphp/gush)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/gushphp/gush/badges/quality-score.png?s=127d28d94969ef366d3bc78808cc89b8eeba51e2)](https://scrutinizer-ci.com/g/gushphp/gush/)
[![Code Coverage](https://scrutinizer-ci.com/g/gushphp/gush/badges/coverage.png?s=674a025b490123ccc3678eb1088d7d0a696004a1)](https://scrutinizer-ci.com/g/gushphp/gush/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451/mini.png)](https://insight.sensiolabs.com/projects/160ad92b-b065-482e-9ebd-4cff2b931451)
[![Latest Stable Version](https://poser.pugx.org/gushphp/gush/v/stable.png)](https://packagist.org/packages/gushphp/gush) [![Total Downloads](https://poser.pugx.org/gushphp/gush/downloads.png)](https://packagist.org/packages/gushphp/gush) [![Latest Unstable Version](https://poser.pugx.org/gushphp/gush/v/unstable.png)](https://packagist.org/packages/gushphp/gush) [![License](https://poser.pugx.org/gushphp/gush/license.png)](https://packagist.org/packages/gushphp/gush)
[![Stories in Ready](https://badge.waffle.io/gushphp/gush.png?label=ready)](https://waffle.io/gushphp/gush)
[![Dependency Status](https://www.versioneye.com/user/projects/52fd77f2ec1375edd50003cc/badge.png)](https://www.versioneye.com/user/projects/52fd77f2ec1375edd50003cc)

<a href="http://gushphp.org"><img src="https://f.cloud.github.com/assets/328359/1930603/3bd6fec6-7eb0-11e3-9945-f41820336d8c.png" alt="Gush logo"  width="200px"/></a>

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
$ composer global require gushphp/gush=dev-master
```

If it is the first time you globally install a dependency then make sure
you include `~/.composer/vendor/bin` in $PATH as shown [here](http://getcomposer.org/doc/03-cli.md#global).

### Keep your Gush install always updated:

```bash
$ composer global update gushphp/gush
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
// ...
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
$ gush issue:take 14
$ git branch
* 14-tests-and-documentation-for-commands
```

Do your changes and commit them:
```bash
$ git commit -am "added instructions to use gush"
```

Send PR:
```
$ gush pull-request:create
Bug fix? [y]
// ...
PR Title: Added a bit of documentation under usage
https://github.com/gushphp/gush/pull/94
```

Merge it:
```bash
$ gush pull-request:merge 94
Pull Request successfully merged
```

### Contributions

Please send your PR using Gush and it will have 100% chances to be merged.
See the [issues list](https://github.com/gushphp/gush/issues?state=open).

Running the test suite (npm required):

```bash
$ npm install
$ ./dev
```

### Mailing list and IRC channel

Join the [Mailing List](https://groups.google.com/forum/#!forum/gushphp)
and also on IRC channel #gushphp for discussions and questions.
