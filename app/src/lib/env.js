/**
 * Validates that all required environment variables are present
 * @returns {void}
 * @throws {Error} If any required environment variable is missing
 */
export function validateEnv() {
  const required = [
    'VITE_WORDPRESS_GRAPHQL_URL',
    'VITE_STRIPE_PUBLIC_KEY',
    'VITE_BETTER_AUTH_BASE_URL',
    'VITE_VAPID_PUBLIC_KEY',
    'VITE_STRIPE_SPARK_ID',
    'VITE_STRIPE_PULSE_ID',
    'VITE_STRIPE_BOLT_ID',
    'VITE_GA_MEASUREMENT_ID',
    'VITE_HF_MODEL_REPO',
    'VITE_VOICE_SWITCH_INTERVAL',
    'VITE_USAGE_ANALYTICS_ENDPOINT',
    'VITE_COST_MARKUP_RATE'
  ];
  
  required.forEach(key => {
    if (!import.meta.env[key]) {
      throw new Error(`Missing required env var: ${key}`);
    }
  });
} 