# Symfony Docker Application

This is a Symfony application configured to run with Docker.

## Getting Started

1. Build and start the containers:
```bash
docker-compose up --build
```

2. Access the application:
- Main application: http://localhost:8000
- MySQL database: localhost:3306

## Services

- **app**: PHP 8.2 with Symfony
- **webserver**: Nginx web server
- **db**: MySQL 8.0 database

## Database Configuration

- Database: `symfony_db`
- Username: `symfony`
- Password: `symfony`
- Root password: `root`

## Development

To install additional packages:
```bash
docker-compose exec app composer require package-name
```

To run Symfony commands:
```bash
docker-compose exec app php bin/console command-name
```