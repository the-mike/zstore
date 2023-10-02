FROM alpine as builder
RUN apk update && apk add openssl
WORKDIR /dh
RUN openssl dhparam -dsaparam -out ./dhparam 4096

FROM nginx:stable-alpine as nginx
RUN mkdir -p /etc/nginx/ssl
COPY --from=builder /dh/* /etc/nginx/ssl/
RUN rm /etc/nginx/conf.d/default.conf
COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d

