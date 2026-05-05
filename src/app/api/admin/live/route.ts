// API de controle de Live
import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';

export async function POST(request: Request) {
  try {
    const session = await getSession();
    if (!session || session.adm !== 'S') {
      return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
    }

    const { titulo, url, ativo, descricao, subtexto } = await request.json();
    
    const pool = await getDbConnection();
    
    // Atualiza tabela principal de transmissão
    await pool.query(`
      UPDATE transmissao_ao_vivo 
      SET titulo = ?, url = ?, ativo = ?, descricao = ?, subtexto = ?
      WHERE id = 1
    `, [titulo, url, ativo ? 1 : 0, descricao || '', subtexto || '']);

    // Atualiza tabela de status secundária (live_status)
    await pool.query(`
      UPDATE live_status 
      SET is_live = ?, live_active = ?
      WHERE id = 1
    `, [ativo ? 1 : 0, ativo ? 1 : 0]);

    return NextResponse.json({ success: true });
    
  } catch (error) {
    console.error('Erro ao atualizar live:', error);
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
