require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function checkLiveAndConfig() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  const [lives] = await connection.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1');
  console.log('Live Ativa:', lives);

  const [configs] = await connection.query('SELECT * FROM plataforma_config');
  console.log('Configurações:', configs);

  await connection.end();
}

checkLiveAndConfig().catch(console.error);
