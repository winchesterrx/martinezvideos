require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function fixTable() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  console.log('Criando tabela plataforma_config...');
  await connection.query(`
    CREATE TABLE IF NOT EXISTS plataforma_config (
      id INT AUTO_INCREMENT PRIMARY KEY,
      chave VARCHAR(100) UNIQUE NOT NULL,
      valor TEXT,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
  `);

  const initialConfigs = [
    { chave: 'home_hero_video_id', valor: '' },
    { chave: 'home_hero_titulo', valor: 'Bem-vindo ao Futuro do Aprendizado' },
    { chave: 'home_hero_subtitulo', valor: 'Explore nossa biblioteca de vídeos premium e acelere sua carreira.' },
  ];

  for (const conf of initialConfigs) {
    await connection.query('INSERT IGNORE INTO plataforma_config (chave, valor) VALUES (?, ?)', [conf.chave, conf.valor]);
  }

  console.log('Tabela criada e populada!');
  await connection.end();
}

fixTable().catch(console.error);
