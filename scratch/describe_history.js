require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function describeHistory() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  const [columns] = await connection.query('DESCRIBE historico_visualizacao');
  console.table(columns);

  await connection.end();
}

describeHistory().catch(console.error);
