#!/usr/bin/env bash

npx uglify-js@3.15.3 jquery-unminified.js --output jquery.js --compress hoist_funs=false,loops=false,unused=false,keep_fnames --beautify beautify=false,ascii_only=true --mangle --comments /!/
