FROM node:lts-alpine AS development

WORKDIR /app
COPY package*.json ./
RUN npm ping
RUN npm install
COPY . .
EXPOSE 5173

CMD ["npm", "run", "dev"]

# Build stage - creates the production build
FROM node:lts-alpine AS build

WORKDIR /app

# Define build arguments
ARG VITE_API_URL
ARG VITE_IMAGES_PATH

# Set environment variables from build args
ENV VITE_API_URL=${VITE_API_URL}
ENV VITE_IMAGES_PATH=${VITE_IMAGES_PATH}

COPY client/package*.json ./
RUN npm ci
COPY client/ .
# RUN echo "Environment variables:" && cat client/.env.production
RUN npm run build

# Production stage - serves the built files
FROM node:lts-alpine AS production

WORKDIR /app

# Install a simple static file server
RUN npm install -g serve

# Copy built files from build stage
COPY --from=build /app/dist ./dist

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:3000/ || exit 1

EXPOSE 3000

# Serve the built files
CMD ["serve", "-s", "dist", "-l", "3000"]