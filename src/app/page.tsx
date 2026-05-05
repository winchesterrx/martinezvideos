import { getDbConnection } from '@/lib/db';
import { PlayCircle, Video, TrendingUp, Bell, Sparkles, Folder, Compass } from 'lucide-react';
import Link from 'next/link';

// Componente para evitar erros de renderização em datas se houver problemas de timezone
function formatDate(dateString: string) {
  try {
    return new Date(dateString).toLocaleDateString('pt-BR');
  } catch {
    return dateString;
  }
}

export default async function DashboardPage() {
  const pool = await getDbConnection();
  
  // 1. Buscar Live Ao Vivo
  const [lives] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
  const liveAtiva = (lives as any[])[0] || null;

  // 2. Buscar Notificações Recentes
  const [notificacoes] = await pool.query('SELECT * FROM notificacoes ORDER BY created_at DESC LIMIT 3');
  const noticias = notificacoes as any[];

  // 3. Buscar Vídeos Recentes
  const [recentes] = await pool.query('SELECT * FROM videos ORDER BY id DESC LIMIT 5'); // Usando ID como aproximação de recência já que não temos created_at claro
  const videosRecentes = recentes as any[];

  // 4. Buscar Trilhas (Vídeos em Sequência)
  const [trilhasQuery] = await pool.query('SELECT * FROM videos WHERE is_sequencia = 1 GROUP BY sequencia_id LIMIT 4');
  const trilhas = trilhasQuery as any[];

  // 5. Sistemas Ativos (Para a lista secundária)
  const [setores] = await pool.query('SELECT * FROM setores WHERE ativo = "S" ORDER BY nome ASC');
  const sistemas = setores as any[];

  return (
    <div className="min-h-[200vh] bg-slate-950 text-white pb-24">
      {/* Background Mask */}
      <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-fixed bg-center opacity-5 pointer-events-none z-0" />
      <div className="absolute inset-0 bg-gradient-to-b from-slate-950/40 via-slate-950/90 to-slate-950 pointer-events-none z-0" />

      <div className="relative z-10 px-4 md:px-8 py-8 max-w-7xl mx-auto space-y-12">
        
        {/* HERO SECTION - LIVE */}
        {liveAtiva ? (
          <div className="relative rounded-3xl overflow-hidden bg-slate-900 border border-orange-500/30 shadow-[0_0_40px_rgba(249,115,22,0.15)] group">
            <div className="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/80 to-transparent z-10" />
            {liveAtiva.url && liveAtiva.url.includes('youtube') && (
               <img src={`https://img.youtube.com/vi/${liveAtiva.url.split('v=')[1]}/maxresdefault.jpg`} className="absolute inset-0 w-full h-full object-cover opacity-40 group-hover:scale-105 transition-transform duration-700" alt="Live Background" />
            )}
            <div className="relative z-20 p-8 md:p-12 md:w-2/3">
              <div className="flex items-center gap-3 mb-4">
                <span className="relative flex h-3 w-3">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
                <span className="text-red-500 font-bold uppercase tracking-widest text-sm">Ao Vivo Agora</span>
              </div>
              <h1 className="text-4xl md:text-5xl font-extrabold text-white mb-4 leading-tight">{liveAtiva.titulo}</h1>
              <p className="text-lg text-slate-300 mb-8 max-w-xl leading-relaxed">{liveAtiva.descricao || 'Acompanhe nossa transmissão exclusiva ao vivo na plataforma.'}</p>
              <Link href="/live" className="inline-flex items-center gap-3 px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-orange-500/25">
                <PlayCircle className="w-6 h-6" />
                Acessar Transmissão
              </Link>
            </div>
          </div>
        ) : (
          <div className="relative rounded-3xl overflow-hidden bg-slate-900 border border-white/5 group">
            <div className="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/80 to-transparent z-10" />
            <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=2070&auto=format&fit=crop')] bg-cover opacity-20 group-hover:scale-105 transition-transform duration-700" />
            <div className="relative z-20 p-8 md:p-12 md:w-2/3">
              <h1 className="text-4xl md:text-5xl font-extrabold text-white mb-4 leading-tight">Conhecimento<br/><span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">Sem Limites.</span></h1>
              <p className="text-lg text-slate-300 mb-8 max-w-xl leading-relaxed">Continue sua trilha de desenvolvimento. Explore módulos, assista a novas aulas e expanda suas habilidades agora mesmo.</p>
              <Link href="#recentes" className="inline-flex items-center gap-3 px-8 py-4 bg-white/10 hover:bg-white/20 backdrop-blur-md text-white font-medium rounded-xl transition-colors border border-white/10">
                <Compass className="w-5 h-5" />
                Explorar Conteúdos
              </Link>
            </div>
          </div>
        )}

        {/* MURAL DE NOTÍCIAS */}
        {noticias.length > 0 && (
          <section>
            <div className="flex items-center gap-3 mb-6">
              <Bell className="w-6 h-6 text-orange-500" />
              <h2 className="text-2xl font-bold text-white">Mural de Avisos</h2>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {noticias.map((noticia) => (
                <div key={noticia.id} className="bg-slate-900/50 backdrop-blur-sm border border-white/5 rounded-2xl p-5 hover:bg-slate-800/50 transition-colors">
                  <div className="flex items-start justify-between mb-3">
                    <span className="text-xs font-semibold uppercase tracking-wider text-orange-400 bg-orange-500/10 px-2.5 py-1 rounded-md">{noticia.tipo || 'Aviso'}</span>
                    <span className="text-xs text-slate-500">{formatDate(noticia.created_at)}</span>
                  </div>
                  <h3 className="text-base font-bold text-slate-200 mb-2">{noticia.titulo}</h3>
                  <p className="text-sm text-slate-400 line-clamp-2">{noticia.mensagem}</p>
                </div>
              ))}
            </div>
          </section>
        )}

        {/* TRILHAS DE DESTAQUE */}
        {trilhas.length > 0 && (
          <section>
            <div className="flex items-center gap-3 mb-6">
              <TrendingUp className="w-6 h-6 text-orange-500" />
              <h2 className="text-2xl font-bold text-white">Trilhas Recomendadas</h2>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              {trilhas.map((trilha) => (
                <Link key={trilha.id} href={`/video/${trilha.id}`} className="group block relative rounded-2xl overflow-hidden aspect-video bg-slate-900 border border-white/5">
                   <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent z-10" />
                   {/* Fallback image */}
                   <img src={`https://images.unsplash.com/photo-[RANDOM_ID]?q=80&w=600&auto=format&fit=crop`} className="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-500" style={{ backgroundImage: 'url("https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop")' }} alt="" />
                   <div className="absolute inset-0 z-20 p-4 flex flex-col justify-end">
                      <span className="text-xs font-bold text-orange-400 mb-1">TRILHA DE APRENDIZADO</span>
                      <h3 className="text-sm font-bold text-white line-clamp-2">{trilha.titulo || `Sequência ${trilha.sequencia_id}`}</h3>
                   </div>
                </Link>
              ))}
            </div>
          </section>
        )}

        {/* VÍDEOS RECENTES */}
        <section id="recentes">
          <div className="flex items-center gap-3 mb-6">
            <Sparkles className="w-6 h-6 text-orange-500" />
            <h2 className="text-2xl font-bold text-white">Adicionados Recentemente</h2>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            {videosRecentes.map((video) => (
              <Link key={video.id} href={`/video/${video.id}`} className="group bg-slate-900/40 rounded-xl border border-white/5 overflow-hidden hover:border-orange-500/30 transition-colors">
                <div className="aspect-video bg-slate-800 relative overflow-hidden">
                   {video.url_video && video.url_video.includes('youtube') ? (
                     <img src={`https://img.youtube.com/vi/${video.url_video.split('v=')[1]}/mqdefault.jpg`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={video.titulo} />
                   ) : (
                     <div className="w-full h-full flex items-center justify-center bg-slate-800 group-hover:scale-105 transition-transform duration-500">
                       <Video className="w-8 h-8 text-slate-600" />
                     </div>
                   )}
                   <div className="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors" />
                </div>
                <div className="p-4">
                  <h3 className="text-sm font-semibold text-slate-200 line-clamp-2 group-hover:text-orange-400 transition-colors">{video.titulo}</h3>
                  <p className="text-xs text-slate-500 mt-2 line-clamp-1">{video.setor || 'Geral'}</p>
                </div>
              </Link>
            ))}
          </div>
        </section>

        {/* NOSSOS SISTEMAS - PRATELEIRA SECUNDÁRIA */}
        <section>
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-3">
              <Folder className="w-6 h-6 text-orange-500" />
              <h2 className="text-2xl font-bold text-white">Explorar por Sistemas</h2>
            </div>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {sistemas.map((sistema) => (
              <Link key={sistema.id} href={`/sistema/${sistema.id}`} className="flex items-center gap-3 p-3 rounded-xl bg-slate-900/50 border border-white/5 hover:bg-slate-800 hover:border-orange-500/30 transition-all group">
                <div className="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                  <Folder className="w-4 h-4 text-slate-400 group-hover:text-orange-500" />
                </div>
                <span className="text-sm font-medium text-slate-300 group-hover:text-white truncate">{sistema.nome}</span>
              </Link>
            ))}
          </div>
        </section>

      </div>
    </div>
  );
}
