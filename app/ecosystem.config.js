module.exports = {
    apps: [{
        name: 'asapdigest-app',
        script: 'build/index.js',
        env: {
            NODE_ENV: 'development',
            PORT: 5173
        },
        env_production: {
            NODE_ENV: 'production',
            PORT: 3000
        },
        instances: 'max',
        exec_mode: 'cluster',
        max_memory_restart: '1G',
        watch: false,
        autorestart: true,
        merge_logs: true,
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        error_file: 'logs/pm2/error.log',
        out_file: 'logs/pm2/out.log',
        time: true
    }]
}; 