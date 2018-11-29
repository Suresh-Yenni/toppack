# Use an official php runtime as a parent image
FROM php:7-alpine

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Set the working directory to /toppack
WORKDIR /toppack

# Copy the current directory contents into the container root
COPY . /toppack

# Install any needed packages specified in requirements.txt
RUN composer install
# RUN rm -rf /tmp/toppack

RUN docker-php-ext-install pdo pdo_mysql

# Make port 80 available to the world outside this container
EXPOSE 3000