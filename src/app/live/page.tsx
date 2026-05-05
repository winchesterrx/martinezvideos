import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, MessageSquare, Radio, Users } from 'lucide-react';
import Link from 'next/link';
import { headers } from 'next/headers';

export default async function LivePage() {
  const pool = await getDbConnection();
  const [lives] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
  const live = (lives as any[])[0];

  if (!live) {
    return (
      <div className="min-h-[80vh] flex flex-col items-center justify-center p-6 text-center">
        <div className="w-20 h-20 rounded-full bg-slate-900 flex items-center justify-center text-slate-700 mb-6">
          <Radio size={40} />
        </div>
        <h1 className="text-2xl font-bold text-white mb-2">Nenhuma Transmissão Ativa</h1>
        <p className="text-slate-400 max-w-xs">No momento não há nenhuma live acontecendo. Fique atento às nossas notificações!</p>
        <Link href="/" className="mt-8 text-orange-500 font-bold hover:underline flex items-center gap-2">
          <ArrowLeft size={18} /> Voltar ao Início
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
    <div className="flex flex-col lg:flex-row h-[calc(100vh-64px)] bg-slate-950 overflow-hidden">
      
      {/* Lado Esquerdo: Player e Info */}
      <div className="flex-1 flex flex-col h-full">
        {/* Player */}
        <div className="flex-1 bg-black relative">
          {videoId ? (
            <iframe 
              className="w-full h-full absolute inset-0"
              src={`https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`}
              title="Live Stream" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowFullScreen
            ></iframe>
          ) : (
            <div className="w-full h-full flex items-center justify-center text-slate-500">
              Erro ao carregar o player da transmissão.
            </div>
          )}
        </div>

        {/* Letreiro de Avisos (Marquee) */}
        {live.subtexto && (
          <div className="bg-orange-500/10 border-y border-white/5 py-3 overflow-hidden">
            <div className="animate-marquee">
              <span className="text-orange-400 font-bold text-sm flex items-center gap-10">
                <span>{live.subtexto}</span>
                <span>•</span>
                <span>{live.subtexto}</span>
                <span>•</span>
                <span>{live.subtexto}</span>
                <span>•</span>
              </span>
            </div>
          </div>
        )}

        {/* Info da Live */}
        <div className="p-6 md:p-8 bg-slate-900/50 border-t border-white/5">
          <div className="flex items-center gap-3 mb-4">
             <div className="flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <span className="text-[10px] text-red-500 font-bold uppercase tracking-widest">Ao Vivo</span>
             </div>
             <div className="flex items-center gap-2 text-slate-400 text-xs font-medium">
                <Users size={14} />
                <span>Transmissão em Tempo Real</span>
             </div>
          </div>
          <h1 className="text-2xl font-bold text-white mb-2">{live.titulo}</h1>
          <p className="text-slate-400 text-sm leading-relaxed max-w-3xl">
            {live.descricao || 'Acompanhe nossa transmissão ao vivo exclusiva para alunos da plataforma Martinez.'}
          </p>
        </div>
      </div>

      {/* Lado Direito: Chat do YouTube */}
      <div className="w-full lg:w-[400px] border-l border-white/5 bg-slate-950 flex flex-col">
        <div className="p-4 border-b border-white/5 flex items-center gap-2 bg-slate-900/30">
          <MessageSquare size={18} className="text-orange-500" />
          <h2 className="text-sm font-bold text-white uppercase tracking-wider">Chat ao Vivo</h2>
        </div>
        <div className="flex-1 bg-black">
          {videoId ? (
            <iframe 
              className="w-full h-full"
              src={`https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${domain}`}
              title="YouTube Live Chat"
            ></iframe>
          ) : (
            <div className="p-10 text-center text-slate-600 text-sm italic">
              Chat indisponível para esta transmissão.
            </div>
          )}
        </div>
        <div className="p-3 bg-slate-900/50 text-[10px] text-slate-500 text-center border-t border-white/5">
          * Para comentar, você precisa estar logado na sua conta Google.
        </div>
      </div>

    </div>
  );
}
