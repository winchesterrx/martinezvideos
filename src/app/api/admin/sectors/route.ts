import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';

export async function GET() {
  try {
    const pool = await getDbConnection();
    const [sectors] = await pool.query('SELECT id, nome FROM setores ORDER BY nome ASC');
    return NextResponse.json({ sectors });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar setores' }, { status: 500 });
  }
}
