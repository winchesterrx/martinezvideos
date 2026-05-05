import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function GET() {
  try {
    const session = await getSession();
    if (!session || session.adm !== 'S') {
      return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
    }

    const pool = await getDbConnection();
    const [rows] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE id = 1');
    const live = (rows as any[])[0] || null;

    return NextResponse.json({ live });
    
  } catch (error) {
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
