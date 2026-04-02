#!/bin/bash
# ===========================================
#  XentraPOS Automatic Docker Installer
# ===========================================

echo "==========================================="
echo "  XentraPOS Automatic Docker Installer"
echo "==========================================="

# Check if docker is installed
if ! [ -x "$(command -v docker)" ]; then
  echo "❌ Error: Docker is not installed. Please install Docker first." >&2
  exit 1
fi

echo "✅ Docker detected. Starting containers..."

# Build and start the containers
docker compose up -d --build

echo "⏳ Waiting for Database to be ready..."
# Wait for MariaDB to be healthy (retry up to 30 times)
for i in {1..30}; do
    if docker exec xentrapos_db mariadb-admin ping -h localhost -padmin123 --silent; then
        echo "✅ Database is online!"
        break
    fi
    echo -n "."
    sleep 2
    if [ $i -eq 30 ]; then
        echo "❌ Error: Database timed out."
        exit 1
    fi
done

echo "📦 Importing Master Enterprise Schema..."
# Import the consolidated database.sql
cat database.sql | docker exec -i xentrapos_db mariadb -u root -padmin123 pos_db

if [ $? -eq 0 ]; then
    echo "==========================================="
    echo " 🎉 XentraPOS successfully installed! 🎉"
    echo "==========================================="
    echo "Access the system at: http://localhost:8080/pos/"
    echo "Default Username: admin"
    echo "Default Password: admin123"
    echo "==========================================="
else
    echo "❌ Error: Database import failed."
    exit 1
fi
