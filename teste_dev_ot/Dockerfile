FROM ubuntu:24.04 AS base

# Evitar prompts interativos
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Sao_Paulo

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    nano \
    lsb-release \
    ca-certificates \
    gnupg \
    && curl -sSL https://packages.sury.org/php/README.txt | bash \
    && apt-get update

# Instalar PHP 8.4 e extensões necessárias
RUN apt-get install -y \
    php8.4 \
    php8.4-cli \
    php8.4-fpm \
    php8.4-common \
    php8.4-mysql \
    php8.4-pgsql \
    php8.4-redis \
    php8.4-mongodb \
    php8.4-curl \
    php8.4-xml \
    php8.4-mbstring \
    php8.4-zip \
    php8.4-gd \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-soap \
    php8.4-xdebug \
    php8.4-imagick \
    nginx \
    supervisor \
    cron \
    redis-tools \
    mongodb-mongosh \
    mariadb-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Configurar PHP 8.4
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/memory_limit = .*/memory_limit = 2G/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/post_max_size = .*/post_max_size = 500M/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/max_execution_time = .*/max_execution_time = 3600/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/max_input_time = .*/max_input_time = 3600/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/;date.timezone =/date.timezone = America\/Sao_Paulo/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/;opcache.enable=.*/opcache.enable=1/' /etc/php/8.4/fpm/php.ini \
    && sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=256/' /etc/php/8.4/fpm/php.ini

# Configurar PHP CLI (mesmas configurações)
RUN sed -i 's/memory_limit = .*/memory_limit = 2G/' /etc/php/8.4/cli/php.ini \
    && sed -i 's/max_execution_time = .*/max_execution_time = 0/' /etc/php/8.4/cli/php.ini \
    && sed -i 's/;date.timezone =/date.timezone = America\/Sao_Paulo/' /etc/php/8.4/cli/php.ini

# Configurar diretórios
RUN mkdir -p /var/www/html \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /var/run/php \
    && mkdir -p /var/run/nginx \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer \
    --version=2.7.1

# Configurar Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/sites-available/laravel.conf /etc/nginx/sites-available/laravel.conf
RUN ln -sf /etc/nginx/sites-available/laravel.conf /etc/nginx/sites-enabled/ \
    && rm -f /etc/nginx/sites-enabled/default

# Configurar Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor/conf.d/

# Script de inicialização
COPY docker/scripts/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Scripts auxiliares
COPY docker/scripts/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

COPY docker/scripts/health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/health-check.sh

# Copiar aplicação
WORKDIR /var/www/html
COPY . .

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Instalar dependências do Composer
RUN if [ -f composer.json ]; then \
    composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Expor portas
EXPOSE 80
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD /usr/local/bin/health-check.sh

# Comando padrão
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
