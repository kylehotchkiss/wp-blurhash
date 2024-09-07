#!/bin/bash

# Define the output zip file name
OUTPUT_ZIP="wp-blurhash.zip"

# Run composer install
echo "Running composer install..."
composer install

# Check if composer install was successful
if [ $? -ne 0 ]; then
    echo "Composer install failed. Exiting."
    exit 1
fi

# Create a temporary directory for files to be zipped
TEMP_DIR=$(mktemp -d)

# Function to clean up the temporary directory
cleanup() {
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT

# Copy files and folders to the temporary directory, excluding the specified ones
rsync -av --exclude='.git' \
      --exclude='.DS_Store' \
      --exclude='build.sh' \
      --exclude='*.zip' \
      . "$TEMP_DIR/"

# Change to the temporary directory
cd "$TEMP_DIR"

# Create the zip file from the contents of the temporary directory
zip -r "$OLDPWD/$OUTPUT_ZIP" .

echo "Created zip file: $OUTPUT_ZIP"
