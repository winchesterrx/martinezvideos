import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function POST(request: Request) {
  try {
    const session = await getSession();
    if (!session) {
      return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
    }

    const { videoId } = await request.json();
    if (!videoId) {
      return NextResponse.json({ error: 'Video ID obrigatório' }, { status: 400 });
    }

    const pool = await getDbConnection();
    
    // Check se já curtiu
    const [existing] = await pool.query('SELECT * FROM curtidas WHERE usuario_id = ? AND video_id = ?', [session.id, videoId]);
    const jaCurtiu = (existing as any[]).length > 0;

    if (jaCurtiu) {
      // Remove curtida
      await pool.query('DELETE FROM curtidas WHERE usuario_id = ? AND video_id = ?', [session.id, videoId]);
      await pool.query('UPDATE videos SET curtidas = GREATEST(curtidas - 1, 0) WHERE id = ?', [videoId]);
      return NextResponse.json({ success: true, action: 'unliked' });
    } else {
      // Adiciona curtida
      await pool.query('INSERT INTO curtidas (usuario_id, video_id) VALUES (?, ?)', [session.id, videoId]);
      await pool.query('UPDATE videos SET curtidas = curtidas + 1 WHERE id = ?', [videoId]);
      return NextResponse.json({ success: true, action: 'liked' });
    }
    
  } catch (error) {
    console.error('Erro ao curtir:', error);
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
