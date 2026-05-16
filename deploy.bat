@echo off
REM Portaye Deployment Script for Windows

echo.
echo 🚀 Portaye Deployment Script for Windows
echo =========================================
echo.

REM Check if Docker is installed
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not installed or not in PATH
    echo Please install Docker Desktop from https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo ✓ Docker is installed
echo.

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Docker Compose might not be available
    echo Attempting to use 'docker compose' instead...
    docker compose version >nul 2>&1
    if errorlevel 1 (
        echo ❌ Docker Compose not found
        pause
        exit /b 1
    )
    set COMPOSE_CMD=docker compose
) else (
    set COMPOSE_CMD=docker-compose
)

echo ✓ Docker Compose is ready
echo.

REM Build and deploy
echo 📦 Building Docker image...
%COMPOSE_CMD% build

if errorlevel 1 (
    echo ❌ Build failed
    pause
    exit /b 1
)

echo ✓ Build successful
echo.

echo 🐳 Starting containers...
%COMPOSE_CMD% up -d

if errorlevel 1 (
    echo ❌ Failed to start containers
    pause
    exit /b 1
)

echo ✓ Containers started
echo.

echo ⏳ Waiting for service to be ready...
timeout /t 10 /nobreak

echo.
echo ========== DEPLOYMENT COMPLETE ==========
echo.
echo 🌐 Website URL: http://localhost/
echo.
%COMPOSE_CMD% ps
echo.
echo Useful commands:
echo   - View logs: %COMPOSE_CMD% logs -f portaye-web
echo   - Stop service: %COMPOSE_CMD% down
echo   - Restart service: %COMPOSE_CMD% restart
echo   - Shell access: %COMPOSE_CMD% exec portaye-web /bin/sh
echo.
pause
