# docker/postgres/Dockerfile
FROM postgres:17

# Utwórz katalog i wygeneruj samopodpisany certyfikat
RUN mkdir -p /var/lib/postgresql/certs && chown postgres:postgres /var/lib/postgresql/certs
RUN openssl req -new -x509 -days 365 \
    -nodes \
    -text \
    -out /var/lib/postgresql/certs/server.crt \
    -keyout /var/lib/postgresql/certs/server.key \
    -subj "/CN=localhost"
RUN chmod 600 /var/lib/postgresql/certs/server.key && \
    chown postgres:postgres /var/lib/postgresql/certs/server.key /var/lib/postgresql/certs/server.crt
