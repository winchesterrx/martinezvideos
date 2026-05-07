import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import { TrendingUp, Sparkles, Folder } from 'lucide-react';
import SearchBar from '@/components/SearchBar';
import Hero from '@/components/Hero';
import ContinueCard from '@/components/ContinueCard';
import NoticeCard from '@/components/NoticeCard';
import TrailCard from '@/components/TrailCard';
import VideoCard from '@/components/VideoCard';
import SystemCard from '@/components/SystemCard';

// Componente para evitar erros de renderização em datas se houver problemas de timezone
function formatDate(dateString: string) {
  try {
    return new Date(dateString).toLocaleDateString('pt-BR');
  } catch {
    return dateString;
  }
}

export default async function DashboardPage() {
  const session = await getSession();
  const pool = await getDbConnection();
  
  // 1. Buscar Live Ao Vivo
  const [lives] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
  const liveAtiva = (lives as any[])[0] || null;

  // 2. Buscar Notificações Recentes
  const [notificacoes] = await pool.query('SELECT * FROM notificacoes ORDER BY created_at DESC LIMIT 3');
  const noticias = notificacoes as any[];

  // 3. Buscar Vídeos Recentes
  const [recentes] = await pool.query('SELECT * FROM videos ORDER BY id DESC LIMIT 5'); 
  const videosRecentes = recentes as any[];

  // 4. Buscar Trilhas (Vídeos em Sequência)
  const [trilhasQuery] = await pool.query('SELECT * FROM videos WHERE is_sequencia = 1 GROUP BY sequencia_id LIMIT 4');
  const trilhas = trilhasQuery as any[];

  // 5. Sistemas Ativos (Para a lista secundária)
  const [setores] = await pool.query('SELECT * FROM setores WHERE ativo = "S" ORDER BY nome ASC');
  const sistemas = setores as any[];

  // 6. Configurações da Plataforma (Home Personalizada)
  const [configs] = await pool.query('SELECT chave, valor FROM plataforma_config');
  const config = (configs as any[]).reduce((acc, curr) => {
    acc[curr.chave] = curr.valor;
    return acc;
  }, {});

  // 7. Buscar Último Vídeo Assistido (Continuidade)
  let ultimoVideo = null;
  if (session) {
    const [history] = await pool.query(`
      SELECT v.*, h.visualizado_em 
      FROM usuario_historico h
      JOIN videos v ON h.video_id = v.id
      WHERE h.usuario_id = ?
      ORDER BY h.visualizado_em DESC
      LIMIT 1
    `, [session.id]);
    ultimoVideo = (history as any[])[0] || null;
  }

  return (
    <div className="min-h-screen text-white pb-24 relative overflow-x-hidden">
      <div className="relative z-10 px-4 md:px-8 py-8 max-w-6xl mx-auto space-y-12">
        
        {/* BUSCA INTELIGENTE (COMMAND PALETTE) */}
        <SearchBar />

        {/* HERO SECTION - LIVE OU CONFIGURADO */}
        <Hero live={liveAtiva} config={config} />

        {/* SEÇÃO DE CONTINUIDADE (CÉREBRO) */}
        {ultimoVideo && <ContinueCard video={ultimoVideo} />}

        {/* MURAL DE NOTÍCIAS CINEMATOGRÁFICO */}
        {noticias.length > 0 && (
          <section className="space-y-8">
            <div className="flex items-center gap-4">
              <div className="w-1.5 h-10 bg-gradient-to-b from-orange-400 to-orange-600 rounded-full shadow-[0_0_15px_rgba(249,115,22,0.4)]" />
              <div>
                 <h2 className="text-3xl font-black text-white uppercase tracking-tighter leading-none" style={{ fontFamily: "'Outfit', sans-serif" }}>Mural de Avisos</h2>
                 <p className="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">Fique por dentro das novidades</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {noticias.map((noticia) => (
                <NoticeCard key={noticia.id} noticia={noticia} formatDate={formatDate} />
              ))}
            </div>
          </section>
        )}

        {/* TRILHAS DE DESTAQUE */}
        {trilhas.length > 0 && (
          <section>
            <div className="flex items-center gap-3 mb-6">
              <TrendingUp className="w-6 h-6 text-orange-500" />
              <h2 className="text-2xl font-bold text-white" style={{ fontFamily: "'Outfit', sans-serif" }}>Trilhas Recomendadas</h2>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              {trilhas.map((trilha) => (
                <TrailCard key={trilha.id} trilha={trilha} />
              ))}
            </div>
          </section>
        )}

        {/* VÍDEOS RECENTES */}
        <section id="recentes">
          <div className="flex items-center gap-3 mb-6">
            <Sparkles className="w-6 h-6 text-orange-500" />
            <h2 className="text-2xl font-bold text-white" style={{ fontFamily: "'Outfit', sans-serif" }}>Adicionados Recentemente</h2>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            {videosRecentes.map((video) => (
              <VideoCard key={video.id} video={video} />
            ))}
          </div>
        </section>

        {/* NOSSOS SISTEMAS - PRATELEIRA SECUNDÁRIA */}
        <section>
          <div className="flex items-center gap-3 mb-6">
            <Folder className="w-6 h-6 text-orange-500" />
            <h2 className="text-2xl font-bold text-white" style={{ fontFamily: "'Outfit', sans-serif" }}>Explorar por Sistemas</h2>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {sistemas.map((sistema) => (
              <SystemCard key={sistema.id} sistema={sistema} />
            ))}
          </div>
        </section>

      </div>
    </div>
  );
}
