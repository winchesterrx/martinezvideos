import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';

export async function DELETE(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json({ error: 'ID não fornecido' }, { status: 400 });
    }

    const pool = await getDbConnection();
    
    // Deleta a transmissão
    await pool.query('DELETE FROM transmissao_ao_vivo WHERE id = ?', [id]);

    return NextResponse.json({ success: true, message: 'Live removida com sucesso' });
  } catch (error: any) {
    console.error('Erro ao deletar live:', error);
    return NextResponse.json({ error: 'Erro interno no servidor' }, { status: 500 });
  }
}
