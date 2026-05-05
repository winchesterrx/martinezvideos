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
    
    const [setores] = await pool.query('SELECT id, nome FROM setores WHERE ativo = "S" ORDER BY nome ASC');
    const [modulos] = await pool.query('SELECT id, nome, setor_id FROM modulos WHERE ativo = "S" ORDER BY nome ASC');

    return NextResponse.json({ setores, modulos });
    
  } catch (error) {
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
