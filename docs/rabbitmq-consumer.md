# Setting Up the UniLab RabbitMQ Consumer

This document explains how to set up and run the RabbitMQ consumer that processes computer status updates sent by the Python agents.

## Overview

The UniLab system consists of two main components:

1. **Python Agent** (installed on lab computers): Sends heartbeat messages and executes commands
2. **Laravel Backend** (your server): Processes these messages and provides the web interface

The communication between these components happens via RabbitMQ, and a dedicated process must run on the server to consume the messages from RabbitMQ and update the database.

## Running the Consumer Command

### Manual Execution

To run the consumer manually for testing:

```bash
php artisan unilab:consume-computer-status
```

Options:
- `--timeout=X`: Run the consumer for X seconds then exit (0 for no timeout)

### Production Setup with Supervisor

For production environments, it's recommended to use Supervisor to manage the consumer process.

1. Install Supervisor:

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

2. Create a configuration file:

```bash
sudo nano /etc/supervisor/conf.d/unilab-consumer.conf
```

3. Add the following configuration:

```ini
[program:unilab-computer-status]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan unilab:consume-computer-status
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/consumer.log
stopwaitsecs=60
```

4. Update the paths to match your environment.

5. Reload Supervisor to apply the changes:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start unilab-computer-status:*
```

## Docker Setup

If you're using Docker, add the consumer to your `docker-compose.yml` file:

```yaml
services:
  # ...other services...
  
  consumer:
    image: ${COMPOSER_IMAGE:-your-laravel-image}
    container_name: unilab-consumer
    restart: unless-stopped
    command: php artisan unilab:consume-computer-status
    volumes:
      - .:/var/www/html
    depends_on:
      - rabbitmq
      - mysql
```

## Troubleshooting

### Common Issues

1. **Connection Errors**: Ensure RabbitMQ is running and accessible with the configured credentials
2. **Permission Issues**: Make sure your application has the correct file permissions
3. **Queue/Exchange Mismatch**: Verify that the queue and exchange names configured in `config/rabbitmq.php` match what the agent is using

### Logs

Check the consumer logs to diagnose issues:

- If running manually: output is displayed in the terminal
- If using Supervisor: `/path/to/your/app/storage/logs/consumer.log`
- In Laravel logs: `/path/to/your/app/storage/logs/laravel.log`

## Monitoring

### Supervisor Status

```bash
sudo supervisorctl status
```

### RabbitMQ Status

Access the RabbitMQ management interface (default: http://localhost:15672) to monitor queues, messages, and connections.
