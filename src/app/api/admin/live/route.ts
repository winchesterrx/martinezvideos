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

    const { titulo, url, ativo, descricao, subtexto, setor_id, modulo_id } = await request.json();
    
    // Extração do ID do Vídeo
    let videoId = '';
    const match = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    if (match) videoId = match[1];

    const pool = await getDbConnection();
    
    const isActivating = ativo;
    
    // 0. Busca a live que está ativa atualmente (se houver)
    const [activeLives]: any = await pool.query('SELECT id FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
    const currentActiveId = activeLives.length > 0 ? activeLives[0].id : null;

    let resultId = currentActiveId;

    if (isActivating === true && !currentActiveId) {
      // 1. ATIVAÇÃO: Cria uma NOVA sessão (Sempre que não houver uma ativa e o comando for ativar)
      await pool.query('UPDATE transmissao_ao_vivo SET ativo = 0');
      
      const [insertResult]: any = await pool.query(`
        INSERT INTO transmissao_ao_vivo (titulo, url, video_id, ativo, descricao, subtexto, setor_id, modulo_id, iniciada_em)
        VALUES (?, ?, ?, 1, ?, ?, ?, ?, NOW())
      `, [titulo, url, videoId, descricao || '', subtexto || '', setor_id || null, modulo_id || null]);
      
      resultId = insertResult.insertId;

      // 2. Notificações para a NOVA live
      const [subscribedUsers]: any = await pool.query("SELECT usuario_id FROM usuario_notificacoes_config WHERE notificar_lives = 'S'");
      if (subscribedUsers.length > 0) {
        const values = subscribedUsers.map((u: any) => [
          u.usuario_id, 
          'LIVE', 
          'Sinal Verde: Martinez Online!', 
          `Acompanhe agora: ${titulo}. Clique para entrar no Martinez Master.`,
          `/live?id=${resultId}`
        ]);

        await pool.query(
          "INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) VALUES ?",
          [values]
        );
      }
    } else if (isActivating === false && currentActiveId) {
      // 3. ENCERRAMENTO: Desativa a live atual
      await pool.query('UPDATE transmissao_ao_vivo SET ativo = 0 WHERE id = ?', [currentActiveId]);
    } else if (currentActiveId) {
      // 4. ATUALIZAÇÃO DE METADADOS: A live continua ativa, apenas mudamos os textos
      await pool.query(`
        UPDATE transmissao_ao_vivo 
        SET titulo = ?, url = ?, video_id = ?, descricao = ?, subtexto = ?, setor_id = ?, modulo_id = ?
        WHERE id = ?
      `, [titulo, url, videoId, descricao || '', subtexto || '', setor_id || null, modulo_id || null, currentActiveId]);
    }

    // Atualiza tabela de status secundária (live_status) - opcional mas mantido por compatibilidade
    await pool.query(`
      INSERT INTO live_status (id, is_live, live_active)
      VALUES (1, ?, ?)
      ON DUPLICATE KEY UPDATE is_live = ?, live_active = ?
    `, [ativo ? 1 : 0, ativo ? 1 : 0, ativo ? 1 : 0, ativo ? 1 : 0]);

    return NextResponse.json({ success: true, liveId: resultId });
    
  } catch (error) {
    console.error('Erro ao atualizar live:', error);
    return NextResponse.json({ error: 'Erro interno' }, { status: 500 });
  }
}
