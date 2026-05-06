require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function checkDatabase() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  console.log('Conectado ao BD!');

  const [tables] = await connection.query('SHOW TABLES');
  console.log('Tabelas existentes:', tables);

  // Criar tabelas recomendadas se não existirem
  await connection.query(`
    CREATE TABLE IF NOT EXISTS favoritos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NOT NULL,
      video_id INT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_favorito (usuario_id, video_id)
    )
  `);

  await connection.query(`
    CREATE TABLE IF NOT EXISTS historico_visualizacao (
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NOT NULL,
      video_id INT NOT NULL,
      tempo_assistido INT DEFAULT 0,
      ultima_visualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_historico (usuario_id, video_id)
    )
  `);

  console.log('Tabelas auxiliares verificadas/criadas com sucesso.');
  await connection.end();
}

checkDatabase().catch(console.error);
