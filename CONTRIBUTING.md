# Contribute to Gush

Thank you for contributing to Gush!

Before we can merge your Pull-Request here are some guidelines that you need to follow.
These guidelines exist not to annoy you, but to keep the code base clean,
unified and future proof.

## We only accept PRs  to "master"

Our branching strategy is "everything to master first", even
bugfixes and we then merge them into the stable branches. You should only
open pull requests against the master branch. Otherwise we cannot accept the PR.

There is no exception to the rule. Also your PR must have been sent with Gush.

## Coding Standard

We use PSR-1 and PSR-2:

* https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
* https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md

with some exceptions/differences:

* Follow the Symfony CS
* No spaces around dots
* Consider current CS patterns

## Unit-Tests

Please try to add a test for your pull-request.

You can run the unit-tests by calling ``phpunit`` from the root of the project.

## Travis

We automatically run your pull request through [Travis CI](http://www.travis-ci.org).
If you break the tests, we cannot merge your code,
so please make sure that your code is working before opening up a Pull-Request.

## Getting merged

Please allow us time to review your pull requests. We will give our best to review
everything as fast as possible, but cannot always live up to our own expectations.

Thank you very much again for your contribution!
