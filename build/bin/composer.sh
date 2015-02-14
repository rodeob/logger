#!/bin/bash

set -e
set -u


################################################################################
## functions

function _usage()
{
    echo "Usage: ./logger.sh composer [command]

Available commands:
  help      Shows this text.
  install   Installs composer.
  update    Updates composer.
  run       Passes command to composer.
"
    exit
}

function _php() #<command>
{
    php -d ${@:1}
}

function _composer() #<command>
{
    _php ./vendor/bin/composer.phar ${@:1}
}


################################################################################
## main

# move into root directory
cd `dirname ${0}`/../..

case "${1:-}" in
    install)
        mkdir -p vendor/bin
        curl -sS https://getcomposer.org/installer | _php -- --install-dir=vendor/bin
        _composer install
        ;;
    production)
        mkdir -p vendor/bin
        curl -sS https://getcomposer.org/installer | _php -- --install-dir=vendor/bin
        _composer install --no-dev
        ;;
    update)
        _composer self-update
        ;;
    run)
        _composer "${2:-}"
        ;;
    *)
        _usage
        ;;
esac
