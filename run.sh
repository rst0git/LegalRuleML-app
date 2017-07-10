#!/bin/bash
# Description: Shell-script to build and start the containers

if [ $# -eq 1 ]; then

    if [[ $1 = "start" ]]; then
        docker-compose up -d
        exit 0
    elif [[ $1 = "stop" ]]
    then
        docker-compose stop
        exit 0
    else
        echo "Usage: $0 [start|stop]"
        exit 1
    fi
fi


# To romove created containers execute "docker-compose down"
read -r -p "Rebuild container images? [y/N] " response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])+$ ]]
then
    docker-compose build
fi

read -r -p "Migrate database? [y/N] " response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])+$ ]]
then
  docker-compose run web bash -c "php artisan migrate && php artisan db:seed --class=UsersTableSeeder"
fi

docker-compose up -d
