import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function PATCH(
  request: Request,
  { params }: { params: { id: string } }
) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  const { id } = params;

  try {
    const { nome, ativo } = await request.json();
    const pool = await getDbConnection();

    await pool.execute(
      'UPDATE setores SET nome = ?, ativo = ? WHERE id = ?',
      [nome, ativo, id]
    );

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao atualizar setor' }, { status: 500 });
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: { id: string } }
) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  const { id } = params;

  try {
    const pool = await getDbConnection();
    // Verificar se existem vídeos vinculados
    const [videos]: any = await pool.query('SELECT id FROM videos WHERE setor_id = ? LIMIT 1', [id]);
    if (videos.length > 0) {
      return NextResponse.json({ error: 'Este sistema possui vídeos vinculados e não pode ser excluído' }, { status: 400 });
    }

    await pool.execute('DELETE FROM setores WHERE id = ?', [id]);
    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao excluir setor' }, { status: 500 });
  }
}
