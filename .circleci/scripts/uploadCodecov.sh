#!/bin/bash
# Collect and submit coverage reports.
#
# Args:
# $1: Flag(s) for codecov, separated by comma.

set -ex

# Curl's built-in retry system is not very robust; it gives up on lots of
# work around a curl bug:	# network errors that we want to retry on. Wget might work better, but it's
#   https://github.com/curl/curl/issues/4461	# not installed on azure pipelines's windows boxes. So... let's try some good
# old-fashioned brute force. (This is also a convenient place to put options
# we always want, like -f to tell curl to give an error if the server sends an
# error response, and -L to follow redirects.)
function curl-harder() {
    for BACKOFF in 0 1 2 4 8 15 15 15 15; do
        sleep $BACKOFF
        if curl -fL --connect-timeout 5 "$@"; then
            return 0
        fi
    done
    return 1
}

echo "=== running submit_coverage in $PWD: $* ==="

codecov_sh="${TEMP:-/tmp}/codecov.bash"
curl-harder -o "$codecov_sh" https://codecov.io/bash
chmod +x "$codecov_sh"

# Upload to codecov.
# -X gcov: disable gcov, done manually above.
# -Z: exit non-zero on failure
# -F: flag(s)
# NOTE: ignoring flags for now, since this causes timeouts on codecov.io then,
#       which they know about for about a year already...
# Flags must match pattern ^[\w\,]+$ ("," as separator).
if ! "$codecov_sh" -s "$HOME/workspace/repo" -Z; then
  echo "codecov upload failed."
  exit 1;
fi
