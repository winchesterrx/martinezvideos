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
    const [rows] = await pool.query('SELECT id, titulo FROM videos ORDER BY titulo ASC');
    return NextResponse.json({ videos: rows });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar vídeos' }, { status: 500 });
  }
}
