#!/usr/bin/env bash

setRootDir() {
    INITIAL_DIR=$PWD
    cd $(dirname $0)/../
}

restoreRootDir() {
    cd $PWD;
}

bash ./scripts/validateDeps.sh

setRootDir

echo -e "\n==================== Preparing Command ===================="

# Ensure dependencies
# Composer install has everything else happening in a post-install script.
# composer install

# Bump versions
node bin/scripts/setReleaseVersion.js

# Make the release
restoreRootDir