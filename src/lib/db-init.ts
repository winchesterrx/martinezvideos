import { getDbConnection } from '@/lib/db';

export async function initMasterLogs() {
  const pool = await getDbConnection();
  try {
    // TABELA MESTRA - O Cérebro da Plataforma
    await pool.query(`
      CREATE TABLE IF NOT EXISTS martinez_logs_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id VARCHAR(100),
        video_url TEXT,
        tipo_acao VARCHAR(50), -- 'LIKE', 'VIEW', 'SHARE', 'NOTIFY'
        user_id INT NULL,
        user_name VARCHAR(255) NULL,
        ip_address VARCHAR(45),
        localizacao VARCHAR(255),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Tabela de Transmissões (Studio)
    await pool.query(`
      CREATE TABLE IF NOT EXISTS transmissao_ao_vivo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255),
        url VARCHAR(255),
        video_id VARCHAR(50),
        descricao TEXT,
        subtexto TEXT,
        ativo TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);


    // Tabela de Configurações da Plataforma
    await pool.query(`
      CREATE TABLE IF NOT EXISTS plataforma_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) UNIQUE NOT NULL,
        valor TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    `);

    // Inserir configurações iniciais se não existirem
    const initialConfigs = [
      { chave: 'home_hero_video_id', valor: '' },
      { chave: 'home_hero_titulo', valor: 'Bem-vindo ao Futuro do Aprendizado' },
      { chave: 'home_hero_subtitulo', valor: 'Explore nossa biblioteca de vídeos premium e acelere sua carreira.' },
    ];

    for (const conf of initialConfigs) {
      await pool.query('INSERT IGNORE INTO plataforma_config (chave, valor) VALUES (?, ?)', [conf.chave, conf.valor]);
    }


    // Sincroniza a coluna video_id se não existir
    try {
      await pool.query(`ALTER TABLE transmissao_ao_vivo ADD COLUMN video_id VARCHAR(50) NULL AFTER url`);
    } catch (e) {}

    console.log('Cérebro Martinez: Tabela Mestra Ativada.');
  } catch (error) {
    console.error('Erro ao ativar Cérebro Martinez:', error);
  }
}
