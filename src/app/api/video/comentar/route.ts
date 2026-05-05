import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function POST(request: Request) {
  try {
    const session = await getSession();
    if (!session) {
      return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
    }

    const { videoId, conteudo } = await request.json();
    if (!videoId || !conteudo || !conteudo.trim()) {
      return NextResponse.json({ error: 'Dados inválidos' }, { status: 400 });
    }

    const pool = await getDbConnection();
    
    // Insere o comentário
    const [result] = await pool.query(
      'INSERT INTO comentarios (video_id, usuario_id, conteudo, data) VALUES (?, ?, ?, NOW())', 
      [videoId, session.id, conteudo.trim()]
    );

    // Incrementa contagem no vídeo
    await pool.query('UPDATE videos SET comentarios = comentarios + 1 WHERE id = ?', [videoId]);

    return NextResponse.json({ 
      success: true, 
      comentario: {
        id: (result as any).insertId,
        conteudo: conteudo.trim(),
        data: new Date().toISOString(),
        usuario_nome: session.nome,
      }
    });
    
  } catch (error) {
    console.error('Erro ao comentar:', error);
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
