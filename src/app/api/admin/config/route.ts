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
    const [rows] = await pool.query('SELECT chave, valor FROM plataforma_config');
    
    // Converter array de chaves/valores para um objeto
    const config = (rows as any[]).reduce((acc, curr) => {
      acc[curr.chave] = curr.valor;
      return acc;
    }, {});

    return NextResponse.json({ config });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar configurações' }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { config } = await request.json(); // { key1: val1, key2: val2 }
    const pool = await getDbConnection();

    for (const [chave, valor] of Object.entries(config)) {
      await pool.execute(
        'INSERT INTO plataforma_config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?',
        [chave, valor, valor]
      );
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao salvar configurações' }, { status: 500 });
  }
}
