## Building with Phing

Vanilla includes a buildfile for [Phing](https://www.phing.info/), a build system for PHP, in the build directory. Running the `phing` command from the build directory will create a deploy-ready copy of Vanilla. This process automatically fetches dependencies with Composer, filters out any unnecessary developer files (Git files/directories, .editorconfig, unit tests, etc.) and compresses the result into an archive.

The phing build requires `NodeJS` and `yarn` to build frontend assets for packaging. See [build tool documentation](https://docs.vanillaforums.com/developer/tools/building-frontend/) for installation instructions.