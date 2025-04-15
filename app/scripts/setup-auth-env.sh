#!/bin/bash

# Export required Better Auth environment variables
export BETTER_AUTH_SECRET="yXV0JRKrprxZHpydQp0MhvONuX6IxrKEaS1xw9Kvphk="
export BETTER_AUTH_URL="https://localhost:5173"

# Run the provided command
exec "$@" 