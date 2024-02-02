#!/usr/bin/env bash

cd /app
#composer update --prefer-source
composer require sgc-fireball/tinyframework-opcache
while true; do
  php console tinyframework:migrate up
  EXITCODE="${?}"
  if [ "${EXITCODE}" -eq "0" ]; then
    break
  fi
  sleep 3
done
php console tinyframework:serve
