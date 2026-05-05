import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, MessageSquare, Radio, Users, Share2, Info, Bell } from 'lucide-react';
import Link from 'next/link';
import { headers } from 'next/headers';
import MartinezChat from './MartinezChat';
import LiveActions from './LiveActions';
import { getYouTubeVideoStats } from '@/lib/youtube';

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
  const youtubeStats = videoId ? await getYouTubeVideoStats(videoId) : null;

  return (
    <div className="relative min-h-screen bg-slate-950 flex flex-col overflow-hidden p-6 lg:p-10 gap-8">
      
      {/* Background Mask */}
      <div className="fixed inset-0 z-0">
        <div className="absolute inset-0 bg-gradient-to-br from-orange-500/10 via-slate-950 to-indigo-500/5" />
      </div>

      {/* Header Martinez Compacto */}
      <div className="relative z-10 flex items-center gap-4">
        <Link href="/" className="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white transition-all backdrop-blur-md border border-white/10">
          <ArrowLeft size={20} />
        </Link>
        <div>
          <div className="flex items-center gap-2 mb-0.5">
            <span className="flex items-center gap-1.5 px-2 py-0.5 bg-red-500 rounded-md text-[10px] font-black text-white uppercase tracking-tighter">
              AO VIVO
            </span>
            <span className="text-slate-500 text-[11px] font-bold uppercase tracking-widest">Masterclass Martinez</span>
          </div>
          <h1 className="text-xl md:text-3xl font-black text-white leading-none">{live.titulo}</h1>
        </div>
      </div>

      {/* Main Content Area: YouTube Style Layout */}
      <div className="relative z-10 grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        {/* Lado Esquerdo: Player + Info (Padrão YouTube) */}
        <div className="lg:col-span-3 flex flex-col">
          <div className="relative group aspect-video">
            <div className="absolute -inset-1 bg-gradient-to-r from-orange-500 to-indigo-600 rounded-3xl blur opacity-20 group-hover:opacity-30 transition duration-1000" />
            <div className="relative w-full h-full bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10">
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
                  <p>Sinal indisponível</p>
                </div>
              )}
            </div>
          </div>

          {/* Área de Ações e Stats (Abaixo do Vídeo) */}
          {videoId && <LiveActions videoId={videoId} youtubeStats={youtubeStats} />}

          {/* Descrição e Letreiro */}
          <div className="mt-6 space-y-6">
             {live.subtexto && (
                <div className="bg-slate-900/60 backdrop-blur-md border border-white/5 py-4 rounded-2xl overflow-hidden shadow-lg border-l-4 border-l-orange-500">
                  <div className="animate-marquee flex items-center gap-20">
                    <span className="text-orange-400 font-black text-sm uppercase flex items-center gap-2 shrink-0">
                      <Info size={16} /> {live.subtexto}
                    </span>
                    <span className="text-slate-600">|</span>
                    <span className="text-orange-400 font-black text-sm uppercase shrink-0">{live.subtexto}</span>
                  </div>
                </div>
             )}
             
             <div className="bg-slate-900/40 backdrop-blur-xl border border-white/5 p-6 rounded-2xl">
                <h3 className="text-white font-black text-sm mb-3 flex items-center gap-2">
                  <Info size={16} className="text-orange-500" /> Detalhes da Transmissão
                </h3>
                <p className="text-slate-400 text-sm leading-relaxed">
                  {live.descricao || 'Sintonize nesta aula de alta performance. Conteúdo exclusivo Martinez.'}
                </p>
             </div>
          </div>
        </div>

        {/* Lado Direito: Chat (Fixado na lateral) */}
        <div className="lg:col-span-1 relative min-h-[500px]">
          <div className="absolute inset-0 flex flex-col rounded-2xl overflow-hidden border border-white/10 bg-slate-900/30 backdrop-blur-3xl shadow-xl">
             {videoId && <MartinezChat videoId={videoId} />}
          </div>
        </div>

      </div>

    </div>
  );
}
