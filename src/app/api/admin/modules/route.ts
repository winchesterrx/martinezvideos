import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';

export async function GET() {
  try {
    const pool = await getDbConnection();
    const [modules] = await pool.query('SELECT id, nome FROM modulos ORDER BY nome ASC');
    return NextResponse.json({ modules });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar módulos' }, { status: 500 });
  }
}
