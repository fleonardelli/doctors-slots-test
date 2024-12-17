FROM alpine:3.15

WORKDIR /app
COPY bin/ bin/
COPY docker/unit-testing-api/entrypoint.sh entrypoint.sh
EXPOSE 2137
ENTRYPOINT /app/entrypoint.sh
