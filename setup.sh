#!/bin/bash

echo "Setting up Local Event Network Service (LENSsf)..."
echo ""

# Create required directories
echo "Creating directories..."
mkdir -p data public/uploads
chmod 755 data public/uploads

# Create config file from example
if [ ! -f config.php ]; then
    echo "Creating config.php from config.example.php..."
    cp config.example.php config.php
    echo "✓ config.php created"
else
    echo "✓ config.php already exists"
fi

# Create empty data files if they don't exist
for file in events venues photos; do
    if [ ! -f "data/${file}.json" ]; then
        echo "[]" > "data/${file}.json"
        echo "✓ Created data/${file}.json"
    else
        echo "✓ data/${file}.json already exists"
    fi
done

echo ""
echo "Setup complete!"
echo ""
echo "To start the development server, run:"
echo "  cd public && php -S localhost:8000"
echo ""
echo "Then open http://localhost:8000 in your browser."
echo ""
