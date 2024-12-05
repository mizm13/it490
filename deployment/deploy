#!/bin/bash

# Check if the required argument (directory) is provided
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <directory>"
    exit 1
fi

DIRECTORY=$1

if [ ! -d "$DIRECTORY" ]; then
    echo "Error: Directory '$DIRECTORY' does not exist."
    exit 1
fi

# Check for .ini file inside the directory
INI_FILE="$DIRECTORY/package.ini"
if [ ! -f "$INI_FILE" ]; then
    echo "Error: .ini file 'package.ini' not found in the directory '$DIRECTORY'."
    exit 1
fi

echo "Found .ini file: $INI_FILE"

PACKAGE_NAME=$(grep -E "^name[ ]*=" "$INI_FILE" | awk -F= '{print $2}' | xargs)
PACKAGE_TYPE=$(grep -E "^type[ ]*=" "$INI_FILE" | awk -F= '{print $2}' | xargs)
PACKAGE_ID=$(grep -E "^id[ ]*=" "$INI_FILE" | awk -F= '{print $2}' | xargs)
PACKAGE_DESCRIPTION=$(grep -E "^description[ ]*=" "$INI_FILE" | awk -F= '{print $2}' | xargs)
PROD=$(grep -E "^PROD[ ]*=" "$INI_FILE" | awk -F= '{print $2}' | xargs)

# Clean up the PROD variable remove square brackets and trim spaces
PROD=$(echo "$PROD" | tr -d '[]' | xargs)
PROD=$(echo "$PROD" | xargs)

# Debugging: Echo values to ensure they're captured correctly
echo "Package Name: $PACKAGE_NAME"
echo "Package Type: $PACKAGE_TYPE"
echo "Package Description: $PACKAGE_DESCRIPTION"
echo "Production: $PROD"

if [ -z "$PACKAGE_NAME" ]; then
    echo "Error: Missing required values in the .ini file (name or version)."
    exit 1
fi

# Check to see if directory name matches the $PACKAGE_TYPE_$PACKAGE_NAME
if [ "$DIRECTORY" != "$PACKAGE_TYPE"_"$PACKAGE_NAME" ]; then
    echo "Error: Directory name does not match the package name and type specified in the .ini file."
    exit 1
fi

# Create a tarball including the .ini file
TARBALL="${DIRECTORY%/}.tar.gz"
tar -czf "$TARBALL" "$DIRECTORY"
if [ $? -ne 0 ]; then
    echo "Error: Failed to compress the directory."
    exit 1
fi

echo "Directory compressed to: $TARBALL"

# Upload the tarball using curl
echo "Deploying package: $TARBALL to QA"
RESPONSE=$(/usr/bin/curl -F "file=@$TARBALL" -F "name=$PACKAGE_NAME" -F "type=$PACKAGE_TYPE" -F "id=$PACKAGE_ID" -F "description=$PACKAGE_DESCRIPTION" http://172.233.182.205:5000/upload 2>/dev/null)

if [ $? -ne 0 ]; then
    echo "Error: Failed to upload the tarball."
    exit 1
fi

echo "Server Response:"
echo "$RESPONSE"

if ! echo "$RESPONSE" | grep -q '"success": true'; then
    echo "Error: Upload failed. Please check the server response for more details."
    exit 1
fi

echo "Package uploaded successfully and QA passed."
echo "Deploying latest package to production..."

# For loop to deploy to production
for i in $PROD; do
    echo "Deploying package: $TARBALL to $i"
    RESPONSE=$(/usr/bin/curl -F "table=$PACKAGE_TYPE" -X POST "http://172.233.182.205:5000/deploy/prod/$i" 2>/dev/null)

    if [ $? -ne 0 ]; then
        echo "Error: Failed to upload the tarball to $i."
        exit 1
    fi

    echo "Server Response:"
    echo "$RESPONSE"

    if ! echo "$RESPONSE" | grep -q '"success": true'; then
        echo "Error: Upload failed. Please check the server response for more details."
        exit 1
    fi

    echo "Package uploaded successfully to $i."
done

echo "Deployment to production completed successfully."

# Clean up space
rm "$TARBALL"
