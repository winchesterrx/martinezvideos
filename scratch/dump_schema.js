require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function dumpSchema() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  const tables = ['usuarios', 'comentarios', 'setores', 'modulos', 'videos'];
  for (const table of tables) {
    try {
      const [cols] = await connection.query(`DESCRIBE ${table}`);
      console.log(`\n--- TABLE: ${table} ---`);
      console.table(cols);
    } catch (e) {
      console.log(`Table ${table} not found.`);
    }
  }
  await connection.end();
}

dumpSchema().catch(console.error);
