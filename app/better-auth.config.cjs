const { betterAuth } = require("better-auth");

const config = betterAuth({
    database: {
        type: "mysql",
        host: "localhost",
        port: 10018,
        user: "root",
        password: "root",
        database: "local"
    },
    secret: "yXV0JRKrprxZHpydQp0MhvONuX6IxrKEaS1xw9Kvphk=",
    baseURL: "http://localhost:5173",
    emailAndPassword: {
        enabled: true
    }
});

module.exports = config; 