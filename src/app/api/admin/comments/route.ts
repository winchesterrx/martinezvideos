import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function GET() {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const pool = await getDbConnection();
    const [rows] = await pool.query(`
      SELECT 
        c.id, 
        c.conteudo, 
        c.data, 
        u.nome as usuario_nome, 
        u.email as usuario_email,
        v.titulo as video_titulo,
        v.id as video_id
      FROM comentarios c
      JOIN usuarios u ON c.usuario_id = u.id
      JOIN videos v ON c.video_id = v.id
      ORDER BY c.data DESC
    `);
    return NextResponse.json({ comments: rows });
  } catch (error) {
    console.error(error);
    return NextResponse.json({ error: 'Erro ao buscar comentários' }, { status: 500 });
  }
}
