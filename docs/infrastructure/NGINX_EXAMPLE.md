# Nginx example (production)

Reference configuration for SCF Enterprise Suite behind PHP-FPM. Adapt paths, domain, and certificate locations. This file is documentation only — it is not applied automatically.

```nginx
# /etc/nginx/sites-available/scf.conf
server {
    listen 80;
    listen [::]:80;
    server_name erp.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name erp.example.com;

    root /var/www/scf/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/erp.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/erp.example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    client_max_body_size 32M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
        fastcgi_read_timeout 120s;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Application `.env` for this layout

```env
APP_URL=https://erp.example.com
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
```

Health probes: `/up`, `/health/live`, `/health/ready`.
