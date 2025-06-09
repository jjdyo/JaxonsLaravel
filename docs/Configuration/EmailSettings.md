# Email Configuration Guide

## Overview
This document provides instructions for configuring email settings in the application, including fixing issues with email links and customizing the sender name.

## Configuration Steps

### 1. Update the .env File
The `.env` file contains environment-specific settings for your application. To fix email links and customize the sender name, you need to update the following settings:

```
APP_NAME=Jaxonville
APP_URL=https://your-domain.com
MAIL_FROM_NAME="Jaxonville"
```

Replace `https://your-domain.com` with your actual website domain.

### 2. Email Link Issues
By default, Laravel uses the `APP_URL` value to generate links in emails. If your emails contain localhost or IP addresses instead of your domain name, make sure:

- `APP_URL` is set correctly in your `.env` file
- Your application is not overriding the URL in any service providers or middleware

### 3. Custom Password Reset Messages
The application now includes custom password reset messages in `resources/lang/en/passwords.php`. These messages override the default Laravel messages without modifying core files.

If you want to further customize these messages, edit the following file:
```
resources/lang/en/passwords.php
```

## Troubleshooting

### Email Links Still Using Localhost/IP
If after updating the `APP_URL` your emails still contain localhost or IP addresses:

1. Clear the configuration cache:
   ```
   php artisan config:clear
   ```

2. Restart the queue worker if you're using queues for sending emails:
   ```
   php artisan queue:restart
   ```

3. Check if any service providers or middleware are overriding the URL generation.

### Sender Name Not Updating
If the sender name is still showing as "Laravel" after updating `APP_NAME` and `MAIL_FROM_NAME`:

1. Make sure you've updated both settings in the `.env` file
2. Clear the configuration cache:
   ```
   php artisan config:clear
   ```

3. Check if any mail configuration is overriding the sender name in your code.
