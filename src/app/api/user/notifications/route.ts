import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function GET() {
  try {
    const session = await getSession();
    if (!session) return NextResponse.json({ notifications: [] });

    const pool = await getDbConnection();
    const [rows]: any = await pool.query(
      'SELECT * FROM notificacoes WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 20',
      [session.id]
    );

    return NextResponse.json({ notifications: rows });
  } catch (error) {
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}

export async function PATCH(request: Request) {
  try {
    const session = await getSession();
    if (!session) return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });

    const { id } = await request.json();
    const pool = await getDbConnection();
    
    if (id === 'all') {
      await pool.query('UPDATE notificacoes SET lida = "S" WHERE usuario_id = ?', [session.id]);
    } else {
      await pool.query('UPDATE notificacoes SET lida = "S" WHERE id = ? AND usuario_id = ?', [id, session.id]);
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
export async function DELETE(request: Request) {
  try {
    const session = await getSession();
    if (!session) return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });

    const { id } = await request.json();
    const pool = await getDbConnection();
    
    if (id === 'all') {
      await pool.query('DELETE FROM notificacoes WHERE usuario_id = ?', [session.id]);
    } else {
      await pool.query('DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?', [id, session.id]);
    }

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
