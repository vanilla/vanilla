#!/usr/bin/env bash
printf "\nInstalling node_modules\n"
yarn install --pure-lockfile
INSTALL_RESULT=$?
if [[ $INSTALL_RESULT -ne 0 ]]
then
    echo "Installing node_modules failed."
    exit $INSTALL_RESULT
fi

VANILLA_BUILD_NODE_ARGS=${VANILLA_BUILD_NODE_ARGS:-""}
VANILLA_BUILD_MEMORY_RESTRICTIONS=${VANILLA_BUILD_MEMORY_RESTRICTIONS:-false}

BUILD_FLAGS="";
if [[ "$VANILLA_BUILD_MEMORY_RESTRICTIONS" = true ]]
then
    BUILD_FLAGS="--low-memory"
fi

printf "\nBuilding frontend assets\n"

set -x;
# Run the build
TS_NODE_PROJECT=build/tsconfig.json \
node $VANILLA_BUILD_NODE_ARGS \
-r ../node_modules/ts-node/register \
build/scripts/build.ts $BUILD_FLAGS

BUILD_RESULT=$?
if [[ $BUILD_RESULT -ne 0 ]]
then
    echo "Building frontend assets failed."
    exit $BUILD_RESULT
fi
