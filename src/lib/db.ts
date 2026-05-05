import mysql from 'mysql2/promise';

const dbConfig = {
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  // Para evitar queda de conexão em Serverless, usamos o pool
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
};

// Singleton para o pool de conexões (evita múltiplos pools em ambiente serverless/Next.js)
let pool: mysql.Pool;

export async function getDbConnection() {
  if (!pool) {
    pool = mysql.createPool(dbConfig);
  }
  return pool;
}
