import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';

export async function GET() {
  try {
    const pool = await getDbConnection();
    const [sectors] = await pool.query('SELECT * FROM setores ORDER BY nome ASC');
    return NextResponse.json({ sectors });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar setores' }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { nome, ativo } = await request.json();
    if (!nome) return NextResponse.json({ error: 'Nome é obrigatório' }, { status: 400 });

    const pool = await getDbConnection();
    const [result]: any = await pool.execute(
      'INSERT INTO setores (nome, ativo) VALUES (?, ?)',
      [nome, ativo || 'S']
    );

    return NextResponse.json({ success: true, id: result.insertId });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao criar setor' }, { status: 500 });
  }
}

