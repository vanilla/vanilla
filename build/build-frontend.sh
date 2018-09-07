#!/usr/bin/env bash
printf "\nInstalling node_modules\n"
yarn install --pure-lockfile
INSTALL_RESULT=$?
if [[ $INSTALL_RESULT -ne 0 ]]
then
    echo "Installing node_modules failed."
    exit $INSTALL_RESULT
fi

printf "\nBuilding frontend assets\n"
yarn build
BUILD_RESULT=$?
if [[ $BUILD_RESULT -ne 0 ]]
then
    echo "Building frontend assets failed."
    exit $BUILD_RESULT
fi
