FROM nginx:1.29 AS nginx-base

WORKDIR /pim/

RUN groupadd --gid 1000 pim-user  && \
    useradd --uid 1000 --gid pim-user --shell /bin/bash --create-home pim-user

FROM nginx-base AS nginx-dev

CMD ["nginx-debug", "-g", "daemon off;"]
