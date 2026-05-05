import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import { headers } from 'next/headers';

export async function POST(request: Request) {
  try {
    const session = await getSession();
    const { videoId } = await request.json();
    
    if (!videoId) return NextResponse.json({ error: 'Missing Video ID' }, { status: 400 });

    // Get IP from headers
    const headerList = await headers();
    const ip = headerList.get('x-forwarded-for')?.split(',')[0] || headerList.get('x-real-ip') || '127.0.0.1';

    // Get Geo Location (Legacy Logic)
    let geo = { cidade: 'Desconhecida', regiao: 'Desconhecida', pais: 'Desconhecido', latitude: null, longitude: null };
    try {
      const geoRes = await fetch(`http://www.geoplugin.net/json.gp?ip=${ip}`);
      const geoData = await geoRes.json();
      if (geoData && geoData.geoplugin_status === 200) {
        geo = {
          cidade: geoData.geoplugin_city || 'Desconhecida',
          regiao: geoData.geoplugin_region || 'Desconhecida',
          pais: geoData.geoplugin_countryName || 'Desconhecido',
          latitude: geoData.geoplugin_latitude || null,
          longitude: geoData.geoplugin_longitude || null
        };
      }
    } catch (e) {
      console.error('Geo API failed', e);
    }

    const pool = await getDbConnection();
    const userId = session?.id || null;
    const userName = session?.nome || null;

    // Check if already registered view in this session/IP (Optional legacy check)
    // For now we just follow the "insert and increment" rule
    
    await pool.query(`
      INSERT INTO video_visualizacoes (video_id, user_id, nome_usuario, ip_address, cidade, regiao, pais, latitude, longitude) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [videoId, userId, userName, ip, geo.cidade, geo.regiao, geo.pais, geo.latitude, geo.longitude]);

    await pool.query('UPDATE videos SET visualizacoes = visualizacoes + 1 WHERE id = ?', [videoId]);

    // Also update history
    if (userId) {
      await pool.query(`
        INSERT INTO usuario_historico (usuario_id, video_id, tempo_assistido, visualizado_em)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE tempo_assistido = GREATEST(tempo_assistido, ?), visualizado_em = NOW()
      `, [userId, videoId, 5, 5]); // 5 seconds fixed as per requirement
    }

    return NextResponse.json({ success: true });

  } catch (error) {
    console.error('View register error:', error);
    return NextResponse.json({ error: 'Internal Error' }, { status: 500 });
  }
}
