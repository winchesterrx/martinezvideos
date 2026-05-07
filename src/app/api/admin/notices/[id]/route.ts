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
    const { tipo, titulo, mensagem, link, video_id, imagem_fundo } = await request.json();
    const pool = await getDbConnection();

    await pool.execute(
      'UPDATE notificacoes SET tipo = ?, titulo = ?, mensagem = ?, link = ?, video_id = ?, imagem_fundo = ? WHERE id = ?',
      [tipo, titulo, mensagem, link, video_id, imagem_fundo, id]
    );

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao atualizar aviso' }, { status: 500 });
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
    await pool.execute('DELETE FROM notificacoes WHERE id = ?', [id]);
    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao excluir aviso' }, { status: 500 });
  }
}
