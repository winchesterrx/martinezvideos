import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { initMasterLogs } from '@/lib/db-init';

export async function GET(request: Request) {
  try {
    await initMasterLogs();
    const { searchParams } = new URL(request.url);
    const videoId = searchParams.get('videoId');
    if (!videoId) return NextResponse.json({ error: 'Faltando ID' }, { status: 400 });

    const pool = await getDbConnection();

    // 0. Busca a live ativa atualmente (ou a última se o ID não for passado)
    const [lives]: any = await pool.query('SELECT id FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
    const activeLiveId = (lives && lives.length > 0) ? lives[0].id : null;

    // Busca TUDO da Tabela Mestra para esta sessão (live_id)
    const [likes]: any = await pool.query('SELECT * FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "LIKE" ORDER BY created_at DESC', [activeLiveId]);
    const [views]: any = await pool.query('SELECT * FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "VIEW" ORDER BY created_at DESC', [activeLiveId]);
    const [shares]: any = await pool.query('SELECT * FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "SHARE" ORDER BY created_at DESC', [activeLiveId]);
    const [notifications]: any = await pool.query('SELECT * FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "NOTIFY" ORDER BY created_at DESC', [activeLiveId]);
    const [viewsByLocation]: any = await pool.query('SELECT localizacao, COUNT(*) as total FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "VIEW" GROUP BY localizacao ORDER BY total DESC', [activeLiveId]);

    return NextResponse.json({
      likes,
      views,
      shares,
      notifications,
      viewsByLocation
    });

  } catch (error: any) {
    return NextResponse.json({ error: 'Erro nos detalhes' }, { status: 500 });
  }
}
