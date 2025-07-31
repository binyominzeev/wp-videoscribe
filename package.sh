#!/bin/bash

# WP VideoScribe Plugin Packaging Script
# This script creates a distributable zip file of the plugin

PLUGIN_NAME="wp-videoscribe"
VERSION="1.0.0"
PACKAGE_NAME="${PLUGIN_NAME}-${VERSION}"

echo "Packaging ${PLUGIN_NAME} version ${VERSION}..."

# Create temporary directory
TEMP_DIR="/tmp/${PACKAGE_NAME}"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

# Copy plugin files
cp -r . "$TEMP_DIR/"

# Remove development files
cd "$TEMP_DIR"
rm -f package.sh
rm -rf .git
rm -f .gitignore
rm -f .DS_Store
find . -name "*.log" -delete
find . -name "Thumbs.db" -delete

# Create zip file
cd ..
zip -r "${PACKAGE_NAME}.zip" "${PACKAGE_NAME}/"

# Move to current directory
mv "${PACKAGE_NAME}.zip" "$OLDPWD/"

# Cleanup
rm -rf "$TEMP_DIR"

echo "Package created: ${PACKAGE_NAME}.zip"
echo "Ready for WordPress installation!"

# Show installation instructions
echo ""
echo "Installation Instructions:"
echo "1. Upload ${PACKAGE_NAME}.zip to your WordPress site"
echo "2. Go to Plugins > Add New > Upload Plugin"
echo "3. Choose the zip file and click 'Install Now'"
echo "4. Activate the plugin"
echo "5. Configure API keys in VideoScribe > Settings"