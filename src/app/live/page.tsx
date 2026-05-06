import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, MessageSquare, Radio, Users, Share2, Info, Bell } from 'lucide-react';
import Link from 'next/link';
import { headers } from 'next/headers';
import MartinezChat from './MartinezChat';
import LiveActions from './LiveActions';
import { getYouTubeVideoStats } from '@/lib/youtube';
import { getSession } from '@/lib/auth';

export default async function LivePage() {
  const session = await getSession();
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

  // Busca Recomendações Relacionadas
  let relatedVideos: any[] = [];
  if (live.setor_id || live.modulo_id) {
    const [related] = await pool.query(`
      SELECT id, titulo, url_video, visualizacoes, thumbnail 
      FROM videos 
      WHERE (setor_id = ? OR modulo_id = ?) 
      AND url_video NOT LIKE ?
      ORDER BY data_upload DESC 
      LIMIT 6
    `, [live.setor_id, live.modulo_id, `%${videoId}%`]);
    relatedVideos = related as any[];
  }

  const getPreviewSource = (url: string, thumbnail: string | null) => {
    if (thumbnail) return { type: 'image', src: thumbnail };
    if (!url) return null;
    
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
      const id = getVideoId(url);
      return { type: 'image', src: `https://img.youtube.com/vi/${id}/mqdefault.jpg` };
    }
    
    if (url.includes('drive.google.com')) {
      const id = url.match(/(?:id=|\/d\/)([0-9A-Za-z_-]{25,})/)?.[1];
      return { type: 'image', src: `https://drive.google.com/thumbnail?id=${id}&sz=w400` };
    }
    
    if (url.includes('/uploads/')) {
      return { type: 'video', src: url };
    }
    
    return null;
  };

  return (
    <div className="relative min-h-screen bg-slate-950 flex flex-col overflow-y-auto p-6 lg:p-10 gap-8">
      
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
        
        {/* Lado Esquerdo: Player + Info */}
        <div className="lg:col-span-3 flex flex-col">
          <div className="relative aspect-video">
            <div className="absolute -inset-1 bg-gradient-to-r from-orange-500 to-indigo-600 rounded-3xl blur opacity-20 transition duration-1000" />
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
        </div>

        {/* Lado Direito: Chat + Recomendações */}
        <div className="lg:col-span-1 flex flex-col gap-6">
           <div className="h-[450px] lg:h-[500px] flex flex-col rounded-2xl overflow-hidden border border-white/10 bg-slate-900/30 backdrop-blur-3xl shadow-xl">
             {videoId && <MartinezChat videoId={videoId} />}
           </div>

           {/* Recomendações Minimalistas */}
           {relatedVideos.length > 0 && (
             <div className="space-y-4">
                <div className="flex items-center justify-between px-2">
                   <h4 className="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Recomendações</h4>
                   <div className="w-8 h-px bg-white/5" />
                </div>
                <div className="flex flex-col gap-3">
                   {relatedVideos.map((vid) => {
                      const preview = getPreviewSource(vid.url_video, vid.thumbnail);
                      return (
                        <Link 
                          key={vid.id} 
                          href={`/video/${vid.id}`}
                          className="group flex items-center gap-4 p-2.5 rounded-2xl hover:bg-white/5 transition-all border border-transparent hover:border-white/5"
                        >
                           <div className="relative w-32 aspect-video rounded-xl overflow-hidden bg-slate-900 shrink-0 shadow-lg">
                              {preview?.type === 'image' ? (
                                <img 
                                  src={preview.src} 
                                  alt={vid.titulo}
                                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                              ) : preview?.type === 'video' ? (
                                <video 
                                  src={preview.src}
                                  muted
                                  loop
                                  autoPlay
                                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center">
                                  <Radio className="text-slate-700" size={24} />
                                </div>
                              )}
                           </div>
                           <div className="flex flex-col min-w-0">
                              <h5 className="text-[13px] font-medium text-slate-200 line-clamp-2 leading-tight group-hover:text-orange-500 transition-colors">
                                {vid.titulo}
                              </h5>
                              <span className="text-[9px] text-slate-500 font-bold uppercase mt-1.5 flex items-center gap-1.5">
                                <div className="w-1 h-1 rounded-full bg-slate-700" />
                                {vid.visualizacoes || 0} visualizações
                              </span>
                           </div>
                        </Link>
                      );
                   })}
                </div>
             </div>
           )}
        </div>
      </div>

      {/* Área de Ações e Stats (Abaixo da Grid Principal) */}
      <div className="relative z-10 lg:w-[75%] -mt-4">
        {videoId && (
          <LiveActions 
            videoId={videoId} 
            youtubeStats={youtubeStats} 
            userId={session?.id} 
            userName={session?.nome} 
          />
        )}
      </div>

      {/* Área de Informação Minimalista */}
      <div className="relative z-10 lg:w-[75%] space-y-12 pb-20">
         
         {/* Ticker Elegante */}
         {live.subtexto && (
            <div className="border-y border-white/5 py-3 overflow-hidden">
              <div className="animate-marquee whitespace-nowrap flex items-center gap-24">
                <span className="text-[10px] text-slate-500 font-bold uppercase tracking-[0.4em] flex items-center gap-4">
                  <div className="w-1 h-1 bg-orange-500 rounded-full" /> {live.subtexto}
                </span>
                <span className="text-[10px] text-slate-500 font-bold uppercase tracking-[0.4em] flex items-center gap-4">
                  <div className="w-1 h-1 bg-orange-500 rounded-full" /> {live.subtexto}
                </span>
              </div>
            </div>
         )}
         
         {/* Descrição Clean */}
         <div className="grid grid-cols-1 md:grid-cols-3 gap-12 pt-4">
            <div className="md:col-span-2 space-y-6">
               <h3 className="text-white font-medium text-lg tracking-tight">Sobre esta transmissão</h3>
               <p className="text-slate-400 text-sm leading-relaxed max-w-2xl">
                  {live.descricao || 'Sintonize nesta aula de alta performance. Conteúdo exclusivo Martinez focado em resultados e experiência imersiva.'}
               </p>
            </div>

            <div className="space-y-6 border-l border-white/5 pl-12">
               <div className="space-y-1">
                  <span className="text-[10px] text-slate-600 font-bold uppercase tracking-widest">Qualidade</span>
                  <p className="text-xs font-medium text-slate-300">4K Ultra HD • 60 FPS</p>
               </div>
               <div className="space-y-1">
                  <span className="text-[10px] text-slate-600 font-bold uppercase tracking-widest">Áudio</span>
                  <p className="text-xs font-medium text-slate-300">Dolby Digital Plus</p>
               </div>
               <div className="space-y-1">
                  <span className="text-[10px] text-slate-600 font-bold uppercase tracking-widest">Segurança</span>
                  <p className="text-xs font-medium text-emerald-500/80">Criptografia Ativa</p>
               </div>
            </div>
         </div>
      </div>

    </div>
  );
}
