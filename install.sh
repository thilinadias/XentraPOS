#!/bin/bash
# XentraPOS Docker Installer

echo "==========================================="
echo "  XentraPOS Automatic Docker Installer "
echo "==========================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker could not be found."
    echo "Please install Docker and Docker Compose first."
    exit 1
fi

echo "✅ Docker detected. Starting installation..."

# Run docker-compose
if docker compose up -d --build; then
    echo "==========================================="
    echo " 🎉 XentraPOS successfully installed! 🎉"
    echo "==========================================="
    echo "Access the system at: http://localhost:8080/pos/"
    echo "Default Username: admin"
    echo "Default Password: admin123"
    echo "==========================================="
else
    echo "❌ Failed to start Docker containers."
    exit 1
fi
