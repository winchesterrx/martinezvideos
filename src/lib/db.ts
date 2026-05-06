import mysql from 'mysql2/promise';

const dbConfig = {
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT) || 3306,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  // Otimizações para evitar ECONNRESET e manter conexões vivas
  waitForConnections: true,
  connectionLimit: 10,
  maxIdle: 10, // número máximo de conexões ociosas
  idleTimeout: 60000, // tempo para expirar conexões ociosas (60s)
  queueLimit: 0,
  enableKeepAlive: true,
  keepAliveInitialDelay: 10000,
};

// Singleton para o pool de conexões (evita múltiplos pools em ambiente serverless/Next.js)
const globalForDb = global as unknown as { pool: mysql.Pool };

export async function getDbConnection() {
  if (!globalForDb.pool) {
    globalForDb.pool = mysql.createPool(dbConfig);
    
    // Log de erros no pool para facilitar debug
    globalForDb.pool.on('error', (err) => {
      console.error('Erro inesperado no Pool de Banco de Dados:', err);
    });
  }
  return globalForDb.pool;
}
