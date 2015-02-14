#!/bin/bash

set -e
set -u


################################################################################
## functions

function _usage()
{
    echo "Usage: `basename ${0}` [command]

Available commands:
  help      Shows this text.
  composer  Runs php composer.
  qa        Runs quality assurance tools.
"
    exit 1
}


################################################################################
## main

# move to the script's root folder
cd `dirname ${0}`

case "${1:-}" in
    composer|qa)
        ./build/bin/${1}.sh ${@:2}
        ;;
    *)
        _usage
        ;;
esac
