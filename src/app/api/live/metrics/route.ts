import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';

export async function POST(request: Request) {
  const { videoId, type, userId } = await request.json();
  const pool = await getDbConnection();

  if (type === 'like') {
    await pool.query('INSERT INTO curtidas_live (video_id, user_id) VALUES (?, ?)', [videoId, userId || null]);
    const [count]: any = await pool.query('SELECT COUNT(*) as total FROM curtidas_live WHERE video_id = ?', [videoId]);
    return NextResponse.json({ success: true, total: count[0].total });
  }

  if (type === 'view') {
    await pool.query('INSERT INTO visualizacoes_live (video_id) VALUES (?)', [videoId]);
    const [count]: any = await pool.query('SELECT COUNT(*) as total FROM visualizacoes_live WHERE video_id = ?', [videoId]);
    return NextResponse.json({ success: true, total: count[0].total });
  }

  return NextResponse.json({ error: 'Invalid type' }, { status: 400 });
}

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const videoId = searchParams.get('videoId');
  const pool = await getDbConnection();

  const [likes]: any = await pool.query('SELECT COUNT(*) as total FROM curtidas_live WHERE video_id = ?', [videoId]);
  const [views]: any = await pool.query('SELECT COUNT(*) as total FROM visualizacoes_live WHERE video_id = ?', [videoId]);

  return NextResponse.json({ 
    likes: likes[0].total, 
    views: views[0].total 
  });
}
