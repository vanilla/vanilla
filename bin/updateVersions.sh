#!/usr/bin/env bash

setRootDir() {
    INITIAL_DIR=$PWD
    cd $(dirname $0)/../
}

restoreRootDir() {
    cd $PWD;
}

setRootDir

bash bin/scripts/validateDeps.sh

echo -e "\n==================== Preparing Command ===================="

# Ensure dependencies
# Composer install has everything else happening in a post-install script.
# composer install

# Bump versions
node bin/scripts/setReleaseVersion.js 2>&1

# Make the release
restoreRootDir