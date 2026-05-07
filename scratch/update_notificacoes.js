require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function updateNotificationsTable() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  console.log('Atualizando tabela notificacoes...');
  
  try {
    await connection.query('ALTER TABLE notificacoes ADD COLUMN imagem_fundo VARCHAR(500) DEFAULT NULL');
    console.log('Coluna imagem_fundo adicionada.');
  } catch (e) {}

  try {
    await connection.query('ALTER TABLE notificacoes ADD COLUMN video_id INT DEFAULT NULL');
    console.log('Coluna video_id adicionada.');
  } catch (e) {}

  await connection.end();
}

updateNotificationsTable().catch(console.error);
