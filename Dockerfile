FROM php:8.4-fpm

# Arguments defined in docker-compose.yml
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    default-mysql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure & Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (v22 LTS) & npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Ensure www-data matches host user ID and group ID to avoid permission issues
RUN groupadd -o -g ${GROUP_ID} laravelgroup 2>/dev/null || true && \
    usermod -o -u ${USER_ID} -g ${GROUP_ID} www-data && \
    mkdir -p /var/www/html && \
    chown -R www-data:www-data /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
