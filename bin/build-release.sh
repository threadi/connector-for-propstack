#!/usr/bin/env bash
#
# Builds the release of "Connector for Propstack".
#
# Used by the GitHub workflow .github/workflows/build-zip.yml AND by the ant build in build/build.xml,
# so both create identical release zips. Change the steps or the list of files only here.
#
# Usage (always from the root of the repository):
#   bash bin/build-release.sh install                 install the PHP and npm packages and build the blocks
#   bash bin/build-release.sh nodev                   remove the dev packages and generate the release autoloader
#   bash bin/build-release.sh collect <version>       copy the files of the release to dist/connector-for-propstack
#   bash bin/build-release.sh zip <path-to-zip>       zip dist/connector-for-propstack
#
# Optional environment variables (used by ant):
#   CFPROP_COMPOSER  path to composer (default: composer)
#   CFPROP_NPM       path to npm (default: npm)

set -euo pipefail

# the name of the plugin folder in the zip.
RELEASE_SLUG="connector-for-propstack"

# the folder where the release is collected.
DIST_DIR="dist"

COMPOSER="${CFPROP_COMPOSER:-composer}"
NPM="${CFPROP_NPM:-npm}"

# always work in the root of the repository.
cd "$( dirname "${BASH_SOURCE[0]}" )/.."

case "${1:-}" in
	install)
		# also runs Mozart, which copies easy-transients-for-wordpress to app/Dependencies.
		"$COMPOSER" install --no-interaction --no-progress

		# build the blocks and the lightbox, copy driver.js.
		"$NPM" ci
		"$NPM" run build
		;;

	nodev)
		"$COMPOSER" install --no-dev --no-scripts --no-interaction --no-progress

		# its classes are shipped in app/Dependencies (see "install"). Without this line the
		# autoloader would also know them under their original names.
		rm -rf vendor/threadi/easy-transients-for-wordpress

		"$COMPOSER" dump-autoload -oa --no-dev
		;;

	collect)
		VERSION="${2:-}"
		if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.]+)?$ ]]; then
			echo "'$VERSION' is not a version number like 1.0.0 or 1.1.0-beta.1." >&2
			exit 1
		fi

		DIST="${DIST_DIR}/${RELEASE_SLUG}"
		rm -rf "$DIST"
		mkdir -p "$DIST"

		# only what is listed here ends up in the zip. Nothing is deleted from the checkout.
		# rsync stops with an error if one of these paths does not exist.
		rsync -aR --exclude='.*' --exclude='node_modules/' \
			"${RELEASE_SLUG}.php" uninstall.php readme.txt composer.json wpml-config.xml \
			admin app css gfx js lib templates \
			blocks/*/block.json blocks/*/build \
			vendor/autoload.php vendor/composer \
			vendor/threadi/crypt-for-wordpress/src \
			vendor/threadi/easy-dialog-for-wordpress/build \
			vendor/threadi/easy-settings-for-wordpress/assets \
			vendor/threadi/easy-settings-for-wordpress/build \
			vendor/threadi/easy-settings-for-wordpress/src \
			vendor/threadi/easy-setup-for-wordpress/build \
			vendor/threadi/easy-setup-for-wordpress/lib \
			"$DIST/"

		# set the version number - in the copy only.
		sed -i.bak "s/@@VersionNumber@@/${VERSION}/g" "$DIST/${RELEASE_SLUG}.php" "$DIST/uninstall.php" "$DIST/readme.txt"
		rm -f "$DIST/${RELEASE_SLUG}.php.bak" "$DIST/uninstall.php.bak" "$DIST/readme.txt.bak"

		# check that the version number has been set everywhere.
		if grep -q "@@VersionNumber@@" "$DIST/${RELEASE_SLUG}.php" "$DIST/uninstall.php" "$DIST/readme.txt"; then
			echo "The version number could not be set." >&2
			exit 1
		fi
		;;

	zip)
		ZIP="${2:-}"
		if [ -z "$ZIP" ]; then
			echo "Usage: bash bin/build-release.sh zip <path-to-zip>" >&2
			exit 1
		fi
		if [ ! -d "${DIST_DIR}/${RELEASE_SLUG}" ]; then
			echo "Run \"bash bin/build-release.sh collect <version>\" first." >&2
			exit 1
		fi

		# make the path absolute, as zip runs in the dist folder.
		case "$ZIP" in
			/*) ;;
			*) ZIP="$PWD/$ZIP" ;;
		esac
		mkdir -p "$( dirname "$ZIP" )"
		rm -f "$ZIP"

		# sorted list of files, so the zip has the same order on every system.
		( cd "$DIST_DIR" && find "$RELEASE_SLUG" -type f | LC_ALL=C sort | zip -qX "$ZIP" -@ )
		;;

	*)
		sed -n '3,17p' "$0"
		exit 1
		;;
esac
