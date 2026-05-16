#!/bin/bash

# Portaye Deployment Script for Linux/macOS

set -e

echo "🚀 Portaye Deployment Script"
echo "=============================="

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed. Please install it first.${NC}"
    exit 1
fi

echo -e "${BLUE}✓ Docker and Docker Compose are installed${NC}"

# Build and deploy
echo -e "${BLUE}📦 Building Docker image...${NC}"
docker-compose build

echo -e "${BLUE}🐳 Starting containers...${NC}"
docker-compose up -d

# Wait for service to be healthy
echo -e "${BLUE}⏳ Waiting for service to be ready...${NC}"
sleep 10

# Check health
if docker-compose ps | grep -q "healthy"; then
    echo -e "${GREEN}✓ Container is healthy and running!${NC}"
else
    echo -e "${RED}⚠️  Container started but may not be fully healthy yet${NC}"
fi

# Display info
echo ""
echo -e "${GREEN}========== DEPLOYMENT COMPLETE ==========${NC}"
echo ""
echo "🌐 Website URL: http://localhost/"
echo "📊 Status: $(docker-compose ps portaye-web | grep -oP 'Up.*')"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f portaye-web"
echo "  - Stop service: docker-compose down"
echo "  - Restart service: docker-compose restart"
echo "  - Shell access: docker-compose exec portaye-web /bin/sh"
echo ""
