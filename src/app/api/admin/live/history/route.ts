import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { initMasterLogs } from '@/lib/db-init';

export async function GET() {
  await initMasterLogs();
  const pool = await getDbConnection();

  // Busca o histórico e soma as ações da Tabela Mestra
  const [history]: any = await pool.query(`
    SELECT 
      t.id, t.titulo, t.url, t.video_id, t.ativo, t.created_at, t.iniciada_em,
      (SELECT COUNT(*) FROM martinez_logs_master WHERE live_id = t.id AND tipo_acao = 'LIKE') as likes,
      (SELECT COUNT(*) FROM martinez_logs_master WHERE live_id = t.id AND tipo_acao = 'VIEW') as views,
      (SELECT COUNT(*) FROM martinez_logs_master WHERE live_id = t.id AND tipo_acao = 'SHARE') as shares,
      (SELECT COUNT(*) FROM martinez_logs_master WHERE live_id = t.id AND tipo_acao = 'NOTIFY') as notifications
    FROM transmissao_ao_vivo t
    ORDER BY t.created_at DESC
  `);

  return NextResponse.json({ history });
}
export async function DELETE(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    if (!id) return NextResponse.json({ error: 'Faltando ID' }, { status: 400 });

    const pool = await getDbConnection();
    
    // 1. Apaga os logs vinculados a esta live_id
    await pool.query('DELETE FROM martinez_logs_master WHERE live_id = ?', [id]);
    
    // 2. Apaga o registro da live
    await pool.query('DELETE FROM transmissao_ao_vivo WHERE id = ?', [id]);

    return NextResponse.json({ success: true });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao excluir' }, { status: 500 });
  }
}
