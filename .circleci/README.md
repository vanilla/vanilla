# Vanilla CircleCI setup.

## Overview

### Custom `checkout` script

We have a custom checkout script that does a few thing better than the built in one.

-   Provides an ENV var `$CUSTOM_TARGET_BRANCH` (gives target branch for a PR as proposed in [CCI-I-894](https://ideas.circleci.com/ideas/CCI-I-894)).
-   Does not screw up the reference to master branch (a la [#23781](https://discuss.circleci.com/t/git-checkout-of-a-branch-destroys-local-reference-to-master/23781)
    and [24975](https://discuss.circleci.com/t/the-checkout-step-mangles-branches-messes-the-history/24975)
-   Attempts to merge the target branch into the current branch and runs all tests against that as proposed in [CCI-I-431](https://ideas.circleci.com/ideas/CCI-I-431)

### Inline Orbs

Currently the `vanilla/checkout` orb is declared inline. This along with much of the content of `aliases:` could be made into properly published orbs.

### Required Github access token

Running this requires a configured github access token with permission to fetch repo info set in the environmental variable `$GITHUB_TOKEN`.

**_WARNING: Be sure to provide a token with the minimum required permissions & make sure it is set securely through the [circleci env vars](https://circleci.com/docs/2.0/env-vars/#setting-an-environment-variable-in-a-project) or [a context](https://circleci.com/docs/2.0/contexts/)_**

### Workflows

The current workflows branch like this:

```
frontend_setup -> frontend_test
                  frontend_build
                  frontend_lint
                  frontend_typecheck
php_setup      -> php_72_tests
                  php_72_integration
                  php_71_tests (nightly only)
                  php_71_integration (nightly only)
                  php_73_tests (nightly only)
                  php_73_integration (nightly only)
php_72_lint
php_71_lint (nightly only)
php_73_lint (nightly only)
dependency_audit (nightly only)
```

Some checks only run in nighly builds (See the `nightly` workflow vs the `commit` workflow).

## Tips & Tricks

## Good general resources

-   [Orb authoring guide](https://circleci.com/docs/2.0/orb-author/)
-   [Minimal Config example w/ inline orbs](https://github.com/CircleCI-Public/config-preview-sdk/tree/v2.1/docs/example_config_pack)
