require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function dumpUsersSchema() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  try {
    const [cols] = await connection.query(`DESCRIBE usuarios`);
    console.log(`\n--- TABLE: usuarios ---`);
    console.table(cols);
  } catch (e) {
    console.log(`Table usuarios not found.`);
  }
  await connection.end();
}

dumpUsersSchema().catch(console.error);
