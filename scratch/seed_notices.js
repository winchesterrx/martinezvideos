require('dotenv').config({ path: '.env.local' });
const mysql = require('mysql2/promise');

async function seedPremiumNotices() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT) || 3306,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
  });

  console.log('Limpando avisos antigos e inserindo exemplos premium...');
  await connection.query('DELETE FROM notificacoes WHERE tipo IN ("MASTERCLASS", "SISTEMA", "NOVIDADE")');

  const notices = [
    {
      tipo: 'MASTERCLASS',
      titulo: 'Novas Estratégias de Licitação 2026',
      mensagem: 'Aprenda as mudanças drásticas na nova lei e como se destacar no mercado público com Gabriel Martinez.',
      imagem_fundo: 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=2070&auto=format&fit=crop',
      video_id: 1, // ID genérico
      link: ''
    },
    {
      tipo: 'SISTEMA',
      titulo: 'Atualização Crítica: Sistema de Saúde',
      mensagem: 'O módulo de regulação de leitos recebeu melhorias de performance e segurança. Confira as mudanças.',
      imagem_fundo: 'https://images.unsplash.com/photo-1576091160550-2173bdd99602?q=80&w=2070&auto=format&fit=crop',
      video_id: null,
      link: 'https://google.com'
    },
    {
      tipo: 'NOVIDADE',
      titulo: 'IA M&C: O Futuro do Suporte Técnico',
      mensagem: 'Conheça o novo assistente inteligente que ajudará você a resolver dúvidas em tempo real dentro da plataforma.',
      imagem_fundo: 'https://images.unsplash.com/photo-1677442136019-21780ecad995?q=80&w=2070&auto=format&fit=crop',
      video_id: null,
      link: ''
    }
  ];

  for (const n of notices) {
    await connection.query(
      'INSERT INTO notificacoes (tipo, titulo, mensagem, imagem_fundo, video_id, link, lida, created_at) VALUES (?, ?, ?, ?, ?, ?, "N", NOW())',
      [n.tipo, n.titulo, n.mensagem, n.imagem_fundo, n.video_id, n.link]
    );
  }

  console.log('Avisos premium inseridos!');
  await connection.end();
}

seedPremiumNotices().catch(console.error);
