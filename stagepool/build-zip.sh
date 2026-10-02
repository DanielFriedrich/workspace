#!/bin/sh
# Baut ein upload-fertiges ZIP für das Webhosting: dist/stagepool-<version>.zip
# Enthält keine Zugangsdaten, Datenbanken, Logs oder hochgeladenen Fotos.
set -e
cd "$(dirname "$0")"
VERSION=$(sed -n "s/.*define('SP_VERSION', '\([^']*\)').*/\1/p" inc/bootstrap.php)
OUT="dist/stagepool-$VERSION.zip"
mkdir -p dist
rm -f "$OUT"
zip -qr "$OUT" . \
  -x 'dist/*' -x 'docs/screenshots/*' -x 'build-zip.sh' -x '.gitignore' \
  -x 'config/config.php' -x 'storage/*.sqlite' -x 'storage/*.lock' -x 'storage/logs/*.log' \
  -x 'storage/receipts/2*' -x 'storage/logs/*.pdf' -x 'storage/probe.txt' \
  -x 'uploads/products/*.jpg' -x 'uploads/products/*.jpeg' -x 'uploads/products/*.png' -x 'uploads/products/*.webp' -x 'uploads/products/*.gif'
echo "$OUT"
