#!/usr/bin/env bash

# Run the audit, capturing the result.
$(yarn audit)
RETURN=$?

# Determine if we should exit with our error.
# We only want to block the build in cron jobs.
# This way a newly reported low severity vulnerability can be triaged and dealt with properly.
if [[ ${TRAVIS_EVENT_TYPE} = 'cron' && ${RETURN} != 0 ]]
then
    exit ${RETURN}
else
    exit 0
fi
