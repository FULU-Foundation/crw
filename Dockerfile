ARG mediawiki_version=1.44.0-fpm
ARG composer_version=2.7.1

# Trick to allow for COPY from for composer image
FROM composer:${composer_version} AS composer

FROM mediawiki:${mediawiki_version}

# Create script directory
RUN mkdir /wiki/

# Copy extensions to the image
COPY ./extensions/ /var/www/html/extensions/

# Copy skins to the image
COPY ./skins/ /var/www/html/skins/

# Copy custom LocalSettings.php
COPY ./conf/LocalSettings.php /var/www/html/LocalSettings.php
COPY ./conf/LocalSettings /var/www/html/LocalSettings

# Composer
RUN apt update
RUN apt install zip unzip
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Semantic Mediawiki
COPY ./conf/composer.local.json /var/www/html/composer.local.json
RUN chown -R root ./composer.json
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN /usr/local/bin/composer config --no-plugins allow-plugins.wikimedia/composer-merge-plugin
RUN /usr/local/bin/composer update --no-dev

# NGINX
RUN apt-get update -y \
    && apt-get install -y nginx

# Custom NGINX configuration
COPY ./conf/nginx.conf /etc/nginx/sites-enabled/default

# Supervisor daemon
RUN apt-get update && \
    apt-get install -y supervisor --no-install-recommends

COPY ./conf/supervisord.conf /etc/supervisor/conf.d/

# Cron
RUN apt-get install -y cron --no-install-recommends

RUN mkdir /wiki/cron

# Add cron files
ADD cron/update_spamlist.sh /wiki/cron/update_spamlist.sh
ADD cron/run_jobs.sh /wiki/cron/run_jobs.sh

# Update permissions
RUN chmod 0644 /wiki/cron/update_spamlist.sh

# Update crontab
RUN crontab -l | { cat; echo "0 0 * * * bash /wiki/cron/update_spamlist.sh"; } | crontab -
RUN crontab -l | { cat; echo "0 * * * * bash /wiki/cron/run_jobs.sh"; } | crontab -

# Imagemagick
RUN apt-get install -y imagemagick --no-install-recommends

# PHP Extensions
RUN apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install -j "$(nproc)" curl
RUN pecl install redis && docker-php-ext-enable redis

# Image directory
RUN chmod 766 /var/www/html/images

# Patches
# Apply patches, fail on any error
RUN --mount=type=bind,source=patches,target=/wiki/patches,readonly \
    set -eux; \
    find /wiki/patches -type f -name '*.patch' | while read -r patchfile; do \
        rel="${patchfile#/wiki/patches/}"; \
        targetdir="/var/www/html/$(dirname "$rel")"; \
        echo "Patching $patchfile -> $targetdir"; \
        if [ ! -d "$targetdir" ]; then \
            echo "Target directory not found: $targetdir" >&2; \
            exit 1; \
        fi; \
        (cd "$targetdir" && patch --verbose -p1 < "$patchfile"); \
    done

# Custom entrypoint
COPY entrypoint.sh /etc/entrypoint.sh
RUN chmod +x /etc/entrypoint.sh
ENTRYPOINT ["/etc/entrypoint.sh"]
