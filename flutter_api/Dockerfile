FROM php:8.1-apache

# 安装必要的扩展
RUN docker-php-ext-install mysqli

# 拷贝 Composer 可执行文件
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 拷贝项目文件到容器
COPY . /var/www/html

# 安装 PHP 依赖
RUN composer install --no-dev

# 启用 Apache rewrite 模块（Laravel 或 REST API 有用）
RUN a2enmod rewrite

EXPOSE 80