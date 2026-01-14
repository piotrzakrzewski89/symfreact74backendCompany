#!/bin/bash
set -e

# Utwórz cert i klucz, jeśli nie istnieją
if [ ! -f /certs/server.key ]; then
  openssl req -new -x509 -days 3650 -nodes \
    -text -out /certs/server.crt \
    -keyout /certs/server.key \
    -subj "/CN=postgres"
  chmod 600 /certs/server.key
  chmod 644 /certs/server.crt
fi
