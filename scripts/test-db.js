const mysql = require('mysql2/promise');

const DB_CONFIG = {
    host: 'localhost',
    port: 10018,
    user: 'root',
    password: 'root',
    database: 'local'
};

async function testConnection() {
    try {
        const connection = await mysql.createConnection(DB_CONFIG);
        console.log('Connected to database successfully!');
        const [rows] = await connection.execute('SHOW TABLES');
        console.log('Tables in database:', rows);
        await connection.end();
    } catch (error) {
        console.error('Error connecting to database:', error);
    }
}

testConnection(); 