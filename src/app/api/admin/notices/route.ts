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
    const [rows] = await pool.query('SELECT * FROM notificacoes ORDER BY created_at DESC');
    return NextResponse.json({ notices: rows });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar avisos' }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { tipo, titulo, mensagem, link, video_id, imagem_fundo } = await request.json();

    if (!tipo || !titulo) {
      return NextResponse.json({ error: 'Tipo e Título são obrigatórios' }, { status: 400 });
    }

    const pool = await getDbConnection();
    const [result]: any = await pool.execute(
      'INSERT INTO notificacoes (tipo, titulo, mensagem, link, video_id, imagem_fundo, lida) VALUES (?, ?, ?, ?, ?, ?, "N")',
      [tipo, titulo, mensagem || '', link || '', video_id || null, imagem_fundo || '']
    );

    return NextResponse.json({ success: true, id: result.insertId });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao criar aviso' }, { status: 500 });
  }
}
