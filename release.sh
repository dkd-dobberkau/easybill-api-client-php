#!/bin/bash
set -e

if [ -z "$1" ]; then
    echo "Usage: ./release.sh <version>"
    echo "Example: ./release.sh 1.0.0"
    exit 1
fi

VERSION="$1"

# Validate semver format
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in semver format (e.g., 1.0.0)"
    exit 1
fi

# Check for uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: Uncommitted changes found. Commit or stash them first."
    exit 1
fi

# Check if tag already exists
if git rev-parse "v$VERSION" >/dev/null 2>&1; then
    echo "Error: Tag v$VERSION already exists"
    exit 1
fi

echo "Releasing version $VERSION..."

# Create and push tag
git tag -a "v$VERSION" -m "Release v$VERSION"
git push origin "v$VERSION"

echo "Released v$VERSION"
echo "https://github.com/dkd-dobberkau/easybill-api-client-php/releases/tag/v$VERSION"
