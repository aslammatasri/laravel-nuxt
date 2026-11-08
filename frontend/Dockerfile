# ---- Stage 1: build static site ----
FROM node:20-alpine AS build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

ARG NUXT_PUBLIC_API_BASE=/api
ARG NUXT_PUBLIC_STORAGE_BASE=/storage
ARG NUXT_PUBLIC_GOOGLE_CLIENT_ID
ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE
ENV NUXT_PUBLIC_STORAGE_BASE=$NUXT_PUBLIC_STORAGE_BASE
ENV NUXT_PUBLIC_GOOGLE_CLIENT_ID=$NUXT_PUBLIC_GOOGLE_CLIENT_ID

RUN npm run generate

# ---- Stage 2: serve static files + reverse proxy to backend ----
FROM nginx:alpine

COPY --from=build /app/.output/public /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
