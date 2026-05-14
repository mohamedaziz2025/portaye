# Build stage (optional - if you need to process assets)
FROM node:20-alpine as builder

WORKDIR /app

# Copy necessary files
COPY main.html ./
COPY logo/ ./logo/

# If you have a package.json for any build process
# COPY package*.json ./
# RUN npm ci --only=production

# Runtime stage
FROM nginx:alpine

# Remove default nginx config
RUN rm /etc/nginx/conf.d/default.conf

# Create custom nginx config for SPA routing
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /usr/share/nginx/html; \
    \
    location / { \
        try_files $uri $uri/ /main.html; \
        expires -1; \
        add_header Cache-Control "public, max-age=0, must-revalidate"; \
    } \
    \
    location ~ \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ { \
        expires 1y; \
        add_header Cache-Control "public, immutable"; \
    } \
}' > /etc/nginx/conf.d/default.conf

# Copy built files from builder stage
COPY --from=builder /app /usr/share/nginx/html/

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget --no-verbose --tries=1 --spider http://localhost/ || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
