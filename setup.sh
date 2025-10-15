#!/bin/bash

echo "Setting up Local Event Network Service (LENSsf)..."
echo ""

# Create required directories
echo "Creating directories..."
mkdir -p public/uploads
chmod 755 public/uploads

# Create config file from example
if [ ! -f config.php ]; then
    echo "Creating config.php from config.example.php..."
    cp config.example.php config.php
    echo "✓ config.php created"
else
    echo "✓ config.php already exists"
fi

echo ""
echo "Setup complete!"
echo ""
echo "IMPORTANT: Before starting, you need to:"
echo "1. Create a MySQL database (e.g., lenssf)"
echo "2. Update config.php with your MySQL credentials"
echo ""
echo "Example MySQL commands:"
echo "  CREATE DATABASE lenssf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo ""
echo "The database schema will be automatically created on first run."
echo ""
echo "To start the development server, run:"
echo "  cd public && php -S localhost:8000"
echo ""
echo "Then open http://localhost:8000 in your browser."
echo ""
