#!/bin/bash
#
# Install Helper
#
# SPDX-License-Identifier: MIT
#

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail

APP_ROOT=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

cd "$APP_ROOT"

composer update --no-ansi --no-progress --classmap-authoritative

npm ci --ignore-scripts --no-audit --no-fund --no-progress --omit=dev

vendor/openthc/common/lib/make.sh install_bootstrap
vendor/openthc/common/lib/make.sh install_fontawesome
vendor/openthc/common/lib/make.sh install_jquery
vendor/openthc/common/lib/make.sh install_lodash
vendor/openthc/common/lib/make.sh install_htmx

#
#
# php <<PHP
# <?php
# require_once(__DIR__ . '/boot.php');
# \OpenTHC\Make::create_homepage('pub');
# PHP
