# Portaye Deployment Guide

## Quick Start with Docker Compose

### Prerequisites
- Docker (latest)
- Docker Compose (latest)

### Build and Run

```bash
# Navigate to the project directory
cd portaye

# Build and start the container
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f portaye-web

# Stop the service
docker-compose down
```

## Manual Docker Commands

```bash
# Build image
docker build -t portaye:latest .

# Run container
docker run -d --name portaye-container -p 80:80 portaye:latest

# Stop container
docker stop portaye-container

# Remove container
docker rm portaye-container

# View logs
docker logs portaye-container

# Exec into container
docker exec -it portaye-container /bin/sh
```

## Production Deployment

### Option 1: AWS EC2 + Docker
1. Launch an EC2 instance (Ubuntu 22.04)
2. Install Docker and Docker Compose
3. Clone your repository
4. Run `docker-compose up -d`
5. Use Route53 for DNS or attach an Elastic IP

### Option 2: DigitalOcean App Platform
1. Connect your Git repository
2. Set build command: `docker build -t portaye .`
3. Set run command: `docker-compose up`
4. Deploy and configure domain

### Option 3: Azure Container Instances
```bash
az container create \
  --resource-group portaye-rg \
  --name portaye-app \
  --image portaye:latest \
  --ports 80 \
  --memory 1 \
  --cpu 1
```

### Option 4: Heroku
```bash
heroku login
heroku create portaye-app
heroku stack:set container
git push heroku main
```

## Environment Variables

Create a `.env` file in the root directory:
```
NODE_ENV=production
NGINX_WORKER_PROCESSES=auto
CALENDLY_TOKEN=your_token_here
```

## Health Checks

The container includes built-in health checks. Monitor with:
```bash
docker-compose ps
# or
docker ps --format "{{.Names}} {{.Status}}"
```

## Scaling

For multiple instances with load balancing:

```yaml
version: '3.8'
services:
  portaye-web:
    build: .
    ports:
      - "80:80"
    deploy:
      replicas: 3
      restart_policy:
        condition: on-failure

  nginx-lb:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
```

## Troubleshooting

- **Port already in use**: `docker ps` to find conflicting containers
- **Out of memory**: Increase Docker memory limit
- **SSL/TLS**: Use Nginx Proxy Manager or Traefik for HTTPS
- **Logs**: Check with `docker-compose logs portaye-web`

## Monitoring

Install monitoring tools:
```bash
# Watchtower auto-updates containers
docker run -d --name watchtower -v /var/run/docker.sock:/var/run/docker.sock containrrr/watchtower
```

## Cleanup

```bash
# Remove all Portaye containers
docker-compose down

# Remove image
docker rmi portaye:latest

# Deep clean (careful!)
docker system prune -a
```

## Security Best Practices

1. Use environment variables for sensitive data
2. Keep Docker and base images updated
3. Run as non-root user (Nginx does this by default)
4. Use secrets management for production
5. Implement rate limiting at reverse proxy level
6. Use HTTPS with valid SSL certificates
7. Regular security scanning: `docker scan portaye:latest`
