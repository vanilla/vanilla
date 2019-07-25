#!/usr/bin/env bash

setRootDir() {
    INITIAL_DIR=$PWD
    cd $(dirname $0)/../
}

restoreRootDir() {
    cd $PWD;
}

setRootDir
bash bin/validateDeps.sh

echo "\n==================== Running Command ===================="


# Bump versions
node bin/scripts/buildRelease.js

# Make the release
restoreRootDir