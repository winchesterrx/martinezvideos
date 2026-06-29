import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { initMasterLogs } from '@/lib/db-init';

// RECRIAÇÃO MARTINEZ - LIMPEZA DE CACHE
async function getDetailedGeoLocation(ip: string) {
  if (ip === '127.0.0.1' || ip === '::1') return { loc: 'Localhost', city: 'Desenvolvimento', region: 'SP', country: 'Brasil' };
  try {
    const res = await fetch(`http://ip-api.com/json/${ip}?fields=status,city,regionName,country,lat,lon`);
    const data = await res.json();
    if (data.status === 'success') {
      return {
        loc: `${data.city}, ${data.regionName} - ${data.country}`,
        city: data.city,
        region: data.regionName,
        country: data.country,
        lat: data.lat,
        lon: data.lon
      };
    }
    return { loc: 'Brasil', city: 'Desconhecido', region: '', country: 'Brasil' };
  } catch (e) {
    return { loc: 'Brasil', city: 'Desconhecido', region: '', country: 'Brasil' };
  }
}

export async function POST(request: Request) {
  try {
    const { videoId, videoUrl, type, userId, userName: providedUserName } = await request.json();

    if ((type === 'like' || type === 'notify') && !userId) {
      return NextResponse.json({ error: 'Você precisa estar logado para realizar esta ação.' }, { status: 401 });
    }

    const pool = await getDbConnection();
    const ip = request.headers.get('x-forwarded-for')?.split(',')[0] || '127.0.0.1';
    const userAgent = request.headers.get('user-agent') || 'Desconhecido';

    let cleanId = videoId;
    if (videoId?.includes('youtube.com') || videoId?.includes('youtu.be')) {
      const match = videoId.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
      if (match) cleanId = match[1];
    }

    if (!cleanId) return NextResponse.json({ error: 'Faltando ID do Vídeo' }, { status: 400 });

    // Busca a Live Ativa
    const [activeLives]: any = await pool.query('SELECT id FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
    const liveId = activeLives.length > 0 ? activeLives[0].id : null;

    // Identificação do Usuário
    let userName = providedUserName || 'Visitante';
    if (userId) {
      const [userRows]: any = await pool.query('SELECT nome FROM usuarios WHERE id = ?', [userId]);
      if (userRows && userRows.length > 0) userName = userRows[0].nome;
    } else if (ip === '127.0.0.1' || ip === '::1') {
      userName = 'Gabriel (Visitante Local)';
    }

    // Geolocalização (Não-bloqueante ou com fallback rápido)
    const geo = await getDetailedGeoLocation(ip);

    // REGRA DE UNICIDADE (Por Sessão)
    if (type === 'view') {
      const [existing]: any = await pool.query(
        `SELECT id FROM martinez_logs_master 
         WHERE live_id = ? AND tipo_acao = "VIEW" 
         AND (${userId ? 'user_id = ?' : 'user_id IS NULL AND ip_address = ?'})`,
        [liveId, userId || ip]
      );
      if (existing && existing.length > 0) return NextResponse.json({ success: true, message: 'Já visto' });
    }

    // Alternador de LIKE
    if (type === 'like') {
      const [existing]: any = await pool.query(
        `SELECT id FROM martinez_logs_master 
         WHERE live_id = ? AND tipo_acao = "LIKE" 
         AND (${userId ? 'user_id = ?' : 'user_id IS NULL AND ip_address = ?'})`,
        [liveId, userId || ip]
      );

      if (existing && existing.length > 0) {
        await pool.query(
          `DELETE FROM martinez_logs_master 
           WHERE live_id = ? AND tipo_acao = "LIKE" 
           AND (${userId ? 'user_id = ?' : 'user_id IS NULL AND ip_address = ?'})`,
          [liveId, userId || ip]
        );
        const [count]: any = await pool.query('SELECT COUNT(*) as total FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "LIKE"', [liveId]);
        return NextResponse.json({ success: true, total: count[0].total, liked: false });
      }
    }

    // REGISTRO FINAL
    await pool.query(
      `INSERT INTO martinez_logs_master 
      (video_id, video_url, tipo_acao, user_id, user_name, ip_address, localizacao, user_agent, live_id) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [cleanId, videoUrl || '', type.toUpperCase(), userId || null, userName, ip, geo.loc, userAgent, liveId]
    );

    if (type === 'notify' && userId) {
      await pool.query(`
        INSERT INTO usuario_notificacoes_config (usuario_id, notificar_lives)
        VALUES (?, 'S')
        ON DUPLICATE KEY UPDATE notificar_lives = IF(notificar_lives = 'S', 'N', 'S')
      `, [userId]);
    }

    const [totalLikes]: any = await pool.query('SELECT COUNT(*) as total FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "LIKE"', [liveId]);
    return NextResponse.json({ success: true, total: totalLikes[0].total, liked: type === 'like' });

  } catch (error: any) {
    console.error('Erro Crítico Martinez:', error);
    return NextResponse.json({ error: error.message || 'Erro interno' }, { status: 500 });
  }
}

export async function GET(request: Request) {
  try {
    const { searchParams } = new URL(request.url);
    const userId = searchParams.get('userId');
    const ip = request.headers.get('x-forwarded-for') || '127.0.0.1';
    const pool = await getDbConnection();

    // 0. Busca a live ativa atualmente
    const [lives]: any = await pool.query('SELECT id FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
    const liveId = (lives && lives.length > 0) ? lives[0].id : null;

    // Contagens por Sessão
    const [likesCount]: any = await pool.query('SELECT COUNT(*) as total FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "LIKE"', [liveId]);
    const [viewsCount]: any = await pool.query('SELECT COUNT(*) as total FROM martinez_logs_master WHERE live_id = ? AND tipo_acao = "VIEW"', [liveId]);

    // Estado do Usuário na Sessão
    const [userLike]: any = await pool.query(
      `SELECT id FROM martinez_logs_master 
       WHERE live_id = ? AND tipo_acao = "LIKE"
       AND (${userId ? 'user_id = ?' : 'user_id IS NULL AND ip_address = ?'}) 
       LIMIT 1`,
      [liveId, userId || ip]
    );

    let isNotified = false;
    if (userId) {
      const [config]: any = await pool.query('SELECT notificar_lives FROM usuario_notificacoes_config WHERE usuario_id = ?', [userId]);
      if (config && config.length > 0) isNotified = config[0].notificar_lives === 'S';
    }

    return NextResponse.json({
      likes: likesCount[0].total,
      views: viewsCount[0].total,
      isLiked: userLike && userLike.length > 0,
      isNotified: isNotified
    });
  } catch (e) {
    return NextResponse.json({ likes: 0, views: 0, isLiked: false, isNotified: false });
  }
}
