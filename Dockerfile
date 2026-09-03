ARG mediawiki_version=1.46.0-fpm
ARG composer_version=2.7.1
ARG imgguard_models_release=models-v1

# Trick to allow for COPY from for composer image
FROM composer:${composer_version} AS composer

FROM mediawiki:${mediawiki_version}

# Create script directory
RUN mkdir /wiki/

# Create sitemaps directory
RUN mkdir -p /var/www/html/sitemap && chown -R www-data:www-data /var/www/html/sitemap

# Create PHP-FPM log directory
RUN mkdir -p /var/log/php-fpm && chown -R www-data:www-data /var/log/php-fpm

# Copy extensions to the image
COPY ./extensions/ /var/www/html/extensions/

# Copy skins to the image
COPY ./skins/ /var/www/html/skins/

# Copy tests
COPY ./tests/ /var/www/html/tests/
COPY ./phpunit.xml.template /var/www/html/phpunit.xml.template

# Copy Phan
COPY ./.phan/ /var/www/html/.phan/

# Copy custom LocalSettings.php
COPY ./conf/LocalSettings.php /var/www/html/LocalSettings.php
COPY ./conf/LocalSettings /var/www/html/LocalSettings

# Copy robots.txt
COPY --chown=www-data:www-data ./data/robots.txt /var/www/html/robots.txt

# Copy PHP-FPM pool conf
COPY ./conf/www.conf /usr/local/etc/php-fpm.d/www.conf

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
ADD cron/generate_sitemap.sh /wiki/cron/generate_sitemap.sh
ADD cron/load_tor_nodes.sh /wiki/cron/load_tor_nodes.sh

# Update permissions
RUN chmod 0644 /wiki/cron/update_spamlist.sh
RUN chmod 0644 /wiki/cron/generate_sitemap.sh
RUN chmod 0644 /wiki/cron/load_tor_nodes.sh

# Update crontab
RUN crontab -l | { cat; echo "0 0 * * * bash /wiki/cron/update_spamlist.sh"; } | crontab -
RUN crontab -l | { cat; echo "0 * * * * bash /wiki/cron/run_jobs.sh"; } | crontab -
RUN crontab -l | { cat; echo "0 3 * * * bash /wiki/cron/generate_sitemap.sh"; } | crontab -
RUN crontab -l | { cat; echo "0 1 * * * bash /wiki/cron/load_tor_nodes.sh"; } | crontab -

# Imagemagick
RUN apt-get install -y imagemagick --no-install-recommends

# ImgGuard upload classifier
RUN apt-get install -y python3-pip curl --no-install-recommends
RUN pip3 install --break-system-packages --no-cache-dir onnxruntime pillow defusedxml pypdfium2
RUN apt-get install -y resvg poppler-utils djvulibre-bin ffmpeg --no-install-recommends
RUN mkdir -p /wiki/imgguard
ARG imgguard_models_release
RUN base="https://github.com/codedbyjake/ImgGuard/releases/download/${imgguard_models_release}" \
    && curl -fL -o /wiki/imgguard/model.onnx "${base}/image-safety-classifier-l.onnx" \
    && curl -fL -o /wiki/imgguard/mature.onnx "${base}/vit-mature-content-detection-int8.onnx" \
    && printf '%s  %s\n' \
        1430a5ec72b5f81165b19f352c7183765177b91723c4073df302231de3d91ff3 /wiki/imgguard/model.onnx \
        dedb56114a357172399cb2db05a681733e00de0d0e4fd2a55d97a90a0d758a63 /wiki/imgguard/mature.onnx \
       | sha256sum -c -

# PHP Extensions
RUN apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install -j "$(nproc)" curl bcmath
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
