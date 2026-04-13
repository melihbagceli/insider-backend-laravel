# Laravel Backend Docker Deployment

## Quick Start

```bash
# Build and run with Docker Compose
docker-compose up --build -d

# Check if containers are running
docker-compose ps

# View logs
docker-compose logs -f

# Stop containers
docker-compose down
```

## Services

- **laravel-app**: PHP 8.3 FPM with Laravel application
- **nginx**: Web server proxying to PHP-FPM

## Ports

- **8000**: Nginx web server (main application)
- **9000**: PHP-FPM (internal, not exposed)

## Environment

The application runs with:

- SQLite database (file-based)
- File cache
- Production settings

## API Endpoints

Once running, the API will be available at:

- `http://localhost:8000/api/teams`
- `http://localhost:8000/api/fixtures`
- etc.

## Development

For development with hot reload:

```bash
# Run in development mode
docker-compose -f docker-compose.dev.yml up --build
```

## Production Deployment

1. Update environment variables in `.env`
2. Run database migrations if needed
3. Use a reverse proxy (nginx/caddy) in front of port 8080
4. Set up SSL certificates
5. Configure monitoring and logging
