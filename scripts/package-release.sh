#!/usr/bin/env bash
# Build an installable plugin ZIP from a committed tree, never from uncommitted files.
set -euo pipefail
ref="${1:-HEAD}"
out="${2:-}"
root="$(git rev-parse --show-toplevel)"
work="$(mktemp -d "${TMPDIR:-/tmp}/pcw-release.XXXXXXXX")"
trap 'rm -rf "$work"' EXIT
mkdir -p "$work/phehrest-custom-widgets"
git -C "$root" archive "$ref" -- phehrest-custom-widgets.php uninstall.php src assets widgets README.md README.fa.md CHANGELOG.md | tar -x -C "$work/phehrest-custom-widgets"
version="$(sed -n 's/^ \* Version: //p' "$work/phehrest-custom-widgets/phehrest-custom-widgets.php")"
if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo 'Plugin header does not contain a stable semantic version.' >&2
  exit 1
fi
if ! grep -Fq "define('PCW_VERSION', '$version');" "$work/phehrest-custom-widgets/phehrest-custom-widgets.php"; then
  echo 'Plugin header and PCW_VERSION differ.' >&2
  exit 1
fi
rm -rf "$work/phehrest-custom-widgets/widgets/ajax-smart-add-to-cart/tests"
if [[ -z "$out" ]]; then out="$root/dist/phehrest-custom-widgets-$version.zip"; fi
case "$out" in /*) ;; *) out="$PWD/$out";; esac
mkdir -p "$(dirname "$out")"
(cd "$work" && zip -X -qr "$work/package.zip" phehrest-custom-widgets)
unzip -tq "$work/package.zip" >/dev/null
mv "$work/package.zip" "$out"
printf 'Created %s from %s\n' "$out" "$ref"
