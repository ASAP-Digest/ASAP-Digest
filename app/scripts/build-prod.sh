#!/bin/bash

# Production Build Script for ASAP Digest

echo "Starting production build process..."

# Clean previous build
echo "Cleaning previous build..."
rm -rf build || true

# Set environment variables
export NODE_ENV=production
export VITE_APP_ENV=production

# Install dependencies if needed
if [ "$1" == "--install" ]; then
  echo "Installing dependencies..."
  npm install
fi

# Build the application
echo "Building application..."
npm run build

# Optimize images
echo "Optimizing images..."
find build -type f -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.gif" | xargs -P 4 -I {} sh -c 'echo "Optimizing {}..." && npx imagemin {} --out-dir=$(dirname {})'

# Gzip assets for servers that support pre-compression
echo "Compressing assets..."
find build -type f -name "*.js" -o -name "*.css" -o -name "*.html" -o -name "*.svg" | xargs -P 4 -I {} sh -c 'echo "Compressing {}..." && gzip -9 -k {}'

echo "Build completed successfully!"
echo "Output files are in the build directory."

# Report build size
echo "Build size report:"
du -sh build
du -sh build/*

echo "Done!" 