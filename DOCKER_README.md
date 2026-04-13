# Laravel Backend Docker Deployment (Railway Ready)

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

- **laravel-app**: PHP 8.3 with Apache2 (all-in-one solution)

## Ports

- **8000**: Web server (local development) / **dynamically assigned on Railway**
- Railway automatically sets `PORT` environment variable

## Environment

The application runs with:

- SQLite database (file-based)
- File cache
- Production settings
- Apache with mod_rewrite enabled
- Dynamic PORT support for Railway

## API Endpoints

Once running, the API will be available at:

- `http://localhost:8000/api/teams`
- `http://localhost:8000/api/fixtures`
- etc.

## Railway Deployment

1. Connect your GitHub repository to Railway
2. Create a new project in Railway
3. Ensure these environment variables are set:
    ```
    PORT=8080
    ```
4. Railway will automatically:
    - Detect Dockerfile
    - Build the image
    - Deploy the application
    - Assign a unique domain (e.g., `yourdomain.railway.app`)

### Railway PORT Configuration

The Dockerfile supports Railway's dynamic PORT assignment. The CMD automatically updates Apache configuration to use the PORT variable.

## Development

For local development with hot reload using docker-compose:

```bash
docker-compose up --build
```

Access the application at `http://localhost:8000`

## Production Deployment

1. Update environment variables in `.env`
2. Run database migrations if needed
3. Use a reverse proxy (nginx/caddy) in front of port 8080
4. Set up SSL certificates
5. Configure monitoring and logging

## Troubleshooting

### Build fails with package not found error

**Solution**: The Dockerfile now includes `apk update` before installing packages. This ensures Alpine's package cache is fresh.

### Container can't connect to database

**Solution**: The SQLite database is stored at `/app/database/database.sqlite`. Ensure the storage directory has proper permissions:

```bash
docker-compose exec laravel-app chmod -R 755 /app/storage
```

### Logs show "key missing" errors

**Solution**: The .env file is generated automatically, but you may need to manually set APP_KEY:

```bash
docker-compose exec laravel-app php artisan key:generate
```

### Port already in use

**Solution**: If port 8000 is already in use, modify `docker-compose.yml`:

```yaml
ports:
    - "8001:80" # Use a different port
```

### Docker daemon not running

**Solution**: Start Docker Desktop or Docker Engine:

- **Windows**: Launch "Docker Desktop" application
- **Linux**: `systemctl start docker`
- **Mac**: Docker Desktop from Applications
