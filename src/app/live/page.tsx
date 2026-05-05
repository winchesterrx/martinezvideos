import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, MessageSquare, Radio, Users, Share2, Info, Bell } from 'lucide-react';
import Link from 'next/link';
import { headers } from 'next/headers';

export default async function LivePage() {
  const pool = await getDbConnection();
  const [lives] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
  const live = (lives as any[])[0];

  if (!live) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center p-6 text-center bg-slate-950">
        <div className="w-24 h-24 rounded-full bg-slate-900 flex items-center justify-center text-slate-700 mb-8 border border-white/5 shadow-2xl">
          <Radio size={48} className="animate-pulse" />
        </div>
        <h1 className="text-3xl font-bold text-white mb-4">Radar de Transmissão</h1>
        <p className="text-slate-500 max-w-sm text-lg leading-relaxed">No momento as câmeras estão desligadas. Prepare a pipoca e aguarde o próximo sinal!</p>
        <Link href="/" className="mt-10 bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-full font-bold transition-all shadow-lg shadow-orange-500/20 flex items-center gap-2">
          <ArrowLeft size={20} /> Voltar ao Início
        </Link>
      </div>
    );
  }

  const getVideoId = (url: string) => {
    if (!url) return null;
    const match = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    return match ? match[1] : null;
  };

  const videoId = getVideoId(live.url);
  const headerList = await headers();
  const domain = headerList.get('host')?.split(':')[0] || 'localhost';

  return (
    <div className="relative min-h-screen bg-slate-950 flex flex-col lg:flex-row overflow-hidden">
      
      {/* Background Mask - Estilo Netflix */}
      <div className="fixed inset-0 z-0">
        <div className="absolute inset-0 bg-gradient-to-br from-orange-500/10 via-slate-950 to-indigo-500/5" />
        {videoId && (
          <div 
            className="absolute inset-0 bg-cover bg-center opacity-10 blur-3xl scale-110"
            style={{ backgroundImage: `url('https://img.youtube.com/vi/${videoId}/maxresdefault.jpg')` }}
          />
        )}
      </div>

      {/* Conteúdo Principal */}
      <div className="relative z-10 flex-1 flex flex-col min-w-0 p-4 lg:p-8">
        
        {/* Top Header Live */}
        <div className="flex items-center justify-between mb-6 px-2">
          <div className="flex items-center gap-4">
            <Link href="/" className="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white transition-all backdrop-blur-md border border-white/10">
              <ArrowLeft size={20} />
            </Link>
            <div>
              <div className="flex items-center gap-2 mb-0.5">
                <span className="flex items-center gap-1.5 px-2 py-0.5 bg-red-500 rounded-md text-[10px] font-black text-white uppercase tracking-tighter animate-pulse">
                  <div className="w-1.5 h-1.5 bg-white rounded-full" /> LIVE
                </span>
                <span className="text-slate-500 text-[11px] font-bold uppercase tracking-widest">Masterclass Martinez</span>
              </div>
              <h1 className="text-xl md:text-2xl font-black text-white leading-none">{live.titulo}</h1>
            </div>
          </div>
          <div className="hidden md:flex items-center gap-3">
             <button className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-slate-300 text-sm font-bold border border-white/5 transition-all backdrop-blur-md">
                <Share2 size={16} /> Compartilhar
             </button>
             <button className="flex items-center gap-2 px-4 py-2 bg-orange-500/10 hover:bg-orange-500/20 rounded-xl text-orange-500 text-sm font-bold border border-orange-500/20 transition-all backdrop-blur-md">
                <Bell size={16} /> Notificar
             </button>
          </div>
        </div>

        {/* Player Box */}
        <div className="relative group">
          {/* Glowing frame */}
          <div className="absolute -inset-1 bg-gradient-to-r from-orange-500 to-indigo-600 rounded-3xl blur opacity-20 group-hover:opacity-30 transition duration-1000" />
          
          <div className="relative aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10">
            {videoId ? (
              <iframe 
                className="w-full h-full absolute inset-0"
                src={`https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`}
                title="Live Stream" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowFullScreen
              ></iframe>
            ) : (
              <div className="w-full h-full flex flex-col items-center justify-center text-slate-600 gap-4">
                <Radio size={64} className="opacity-20 animate-pulse" />
                <p className="font-medium">O sinal está oscilando. Tente atualizar.</p>
              </div>
            )}
          </div>
        </div>

        {/* Letreiro Premium (Marquee) */}
        {live.subtexto && (
          <div className="mt-8 bg-slate-900/40 backdrop-blur-md border border-white/5 py-4 rounded-2xl overflow-hidden shadow-lg">
            <div className="animate-marquee flex items-center gap-20">
              <span className="text-orange-400 font-black text-sm flex items-center gap-2">
                <Info size={16} className="text-orange-500" />
                {live.subtexto.toUpperCase()}
              </span>
              <span className="text-slate-400 font-bold text-sm">|</span>
              <span className="text-orange-400 font-black text-sm uppercase">{live.subtexto}</span>
              <span className="text-slate-400 font-bold text-sm">|</span>
              <span className="text-orange-400 font-black text-sm uppercase">{live.subtexto}</span>
            </div>
          </div>
        )}

        {/* Detalhes Técnicos */}
        <div className="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
           <div className="bg-slate-900/50 backdrop-blur-xl border border-white/10 p-8 rounded-3xl shadow-xl">
              <h3 className="text-white font-black text-lg mb-4 flex items-center gap-2">
                <Info size={20} className="text-orange-500" /> Sobre esta Transmissão
              </h3>
              <p className="text-slate-400 text-sm leading-relaxed font-medium">
                {live.descricao || 'Sintonize nesta aula ao vivo de alta performance. Conteúdo exclusivo para o ecossistema Martinez.'}
              </p>
           </div>
           <div className="bg-slate-900/50 backdrop-blur-xl border border-white/10 p-8 rounded-3xl shadow-xl flex items-center justify-center">
              <div className="text-center">
                 <div className="flex items-center justify-center gap-3 text-orange-500 mb-2">
                    <Users size={32} />
                    <span className="text-4xl font-black text-white tracking-tighter">AO VIVO</span>
                 </div>
                 <p className="text-slate-500 text-xs font-bold uppercase tracking-widest">Interaja no Chat ao lado →</p>
              </div>
           </div>
        </div>

      </div>

      {/* Barra Lateral: Chat do YouTube Premium */}
      <div className="relative z-20 w-full lg:w-[420px] xl:w-[480px] h-[600px] lg:h-screen bg-slate-950/80 backdrop-blur-3xl border-l border-white/5 flex flex-col shadow-[-20px_0_50px_rgba(0,0,0,0.5)]">
        
        <div className="p-6 border-b border-white/10 flex items-center justify-between bg-slate-900/40">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-orange-500 rounded-lg shadow-lg shadow-orange-500/20">
              <MessageSquare size={20} className="text-white" />
            </div>
            <div>
              <h2 className="text-sm font-black text-white uppercase tracking-wider">Chat da Comunidade</h2>
              <div className="flex items-center gap-1.5 text-[10px] text-emerald-400 font-bold">
                <div className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse" /> SINAL ONLINE
              </div>
            </div>
          </div>
        </div>

        <div className="flex-1 bg-black/40">
          {videoId ? (
            <iframe 
              className="w-full h-full"
              src={`https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${domain}`}
              title="YouTube Live Chat"
            ></iframe>
          ) : (
            <div className="h-full flex flex-col items-center justify-center p-12 text-center text-slate-700">
              <MessageSquare size={48} className="mb-4 opacity-10" />
              <p className="text-sm font-bold uppercase tracking-widest opacity-30">Chat aguardando sinal...</p>
            </div>
          )}
        </div>

        <div className="p-4 bg-slate-900/60 text-[10px] text-slate-500 font-bold text-center border-t border-white/5 uppercase tracking-widest">
          Sua interação fortalece nossa comunidade
        </div>
      </div>

    </div>
  );
}
