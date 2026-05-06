'use client';

import { useState } from 'react';
import { Sparkles, Route, PlayCircle, Clock, Video } from 'lucide-react';
import AIChatClient from './AIChatClient';
import Link from 'next/link';

export default function VideoSidebarTabs({ 
  video, 
  trilha, 
  sequencia_titulo,
  sugestoes
}: { 
  video: any, 
  trilha: any[], 
  sequencia_titulo: string,
  sugestoes: any[]
}) {
  const hasTrilha = trilha.length > 0;
  const [activeTab, setActiveTab] = useState<'conteudo' | 'ai'>('conteudo');

  const getVideoId = (url: string) => {
    if (!url) return null;
    const ytMatch = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    if (ytMatch) return { type: 'youtube', id: ytMatch[1] };
    const driveMatch = url.match(/(?:id=|\/d\/)([0-9A-Za-z_-]{25,})/);
    if (driveMatch) return { type: 'drive', id: driveMatch[1] };
    if (url.includes('/uploads/')) return { type: 'local', id: url };
    return null;
  };

  const currentIdx = trilha.findIndex(t => t.id === video.id);
  const nextVideo = currentIdx !== -1 && currentIdx < trilha.length - 1 ? trilha[currentIdx + 1] : null;

  return (
    <div className="flex flex-col h-full lg:w-[350px] xl:w-[400px] border-l border-white/5 bg-slate-950/50 backdrop-blur-xl">
      {/* Tabs Header */}
      <div className="flex border-b border-white/5">
        <button 
          onClick={() => setActiveTab('conteudo')}
          className={`flex-1 py-4 font-bold text-[10px] uppercase tracking-[0.2em] transition-all border-b-2 ${
            activeTab === 'conteudo' 
              ? 'text-orange-500 border-orange-500 bg-orange-500/5' 
              : 'text-slate-500 border-transparent hover:text-slate-300'
          }`}
        >
          <div className="flex items-center justify-center gap-2">
            <Video size={14} /> Conteúdo
          </div>
        </button>
        <button 
          onClick={() => setActiveTab('ai')}
          className={`flex-1 py-4 font-bold text-[10px] uppercase tracking-[0.2em] transition-all border-b-2 ${
            activeTab === 'ai' 
              ? 'text-indigo-400 border-indigo-500 bg-indigo-500/5' 
              : 'text-slate-500 border-transparent hover:text-slate-300'
          }`}
        >
          <div className="flex items-center justify-center gap-2">
            <Sparkles size={14} /> Tutor IA
          </div>
        </button>
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-y-auto custom-scrollbar">
        {activeTab === 'conteudo' && (
          <div className="p-6 space-y-10">
            
            {/* PRÓXIMO NA SEQUÊNCIA (Destaque) */}
            {nextVideo && (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-[10px] font-black text-orange-500 uppercase tracking-widest">Próximo na Sequência</h3>
                  <span className="text-[9px] text-slate-500 font-bold uppercase">{currentIdx + 2} / {trilha.length}</span>
                </div>
                <Link 
                  href={`/video/${nextVideo.id}`}
                  className="group block relative aspect-video rounded-2xl overflow-hidden border border-orange-500/30 bg-slate-900 shadow-2xl shadow-orange-500/10"
                >
                  {(() => {
                    const info = getVideoId(nextVideo.url_video);
                    const thumb = nextVideo.thumbnail || (info?.type === 'youtube' ? `https://img.youtube.com/vi/${info.id}/maxresdefault.jpg` : null);
                    return thumb ? (
                      <img src={thumb} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center bg-slate-900">
                        <PlayCircle className="text-orange-500 w-12 h-12" />
                      </div>
                    );
                  })()}
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent" />
                  <div className="absolute bottom-4 left-4 right-4">
                    <h4 className="text-white font-bold text-sm line-clamp-1 group-hover:text-orange-400 transition-colors">{nextVideo.titulo}</h4>
                    <p className="text-[10px] text-slate-400 font-medium uppercase mt-1 flex items-center gap-2">
                      <Clock size={10} /> {nextVideo.visualizacoes || 0} views
                    </p>
                  </div>
                  <div className="absolute top-4 right-4 bg-orange-500 text-white p-2 rounded-full shadow-lg scale-90 group-hover:scale-100 transition-transform">
                    <PlayCircle size={20} fill="currentColor" />
                  </div>
                </Link>
              </div>
            )}

            {/* LISTA DA TRILHA (Compacta) */}
            {hasTrilha && (
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <Route size={14} className="text-slate-500" />
                  <h3 className="text-[10px] font-black text-white uppercase tracking-widest">{sequencia_titulo || 'Sequência'}</h3>
                </div>
                <div className="space-y-2">
                  {trilha.map((t, index) => {
                    const isActive = t.id === video.id;
                    const isNext = nextVideo && t.id === nextVideo.id;
                    return (
                      <Link 
                        key={t.id} 
                        href={`/video/${t.id}`}
                        className={`flex items-center gap-4 p-3 rounded-xl transition-all border ${
                          isActive 
                            ? 'bg-orange-500/5 border-orange-500/20' 
                            : 'bg-white/[0.02] border-transparent hover:border-white/10 hover:bg-white/[0.04]'
                        }`}
                      >
                        <div className={`w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black ${
                          isActive ? 'bg-orange-500 text-white' : 'bg-slate-900 text-slate-500 border border-white/5'
                        }`}>
                          {index + 1}
                        </div>
                        <div className="flex-1 min-w-0">
                           <p className={`text-[12px] font-bold truncate ${isActive ? 'text-orange-400' : 'text-slate-300'}`}>
                             {t.titulo}
                           </p>
                        </div>
                        {isActive && <div className="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse" />}
                      </Link>
                    );
                  })}
                </div>
              </div>
            )}

            {/* RECOMENDAÇÕES (YouTube Style) */}
            {sugestoes.length > 0 && (
              <div className="space-y-6 pt-6 border-t border-white/5">
                <h3 className="text-[10px] font-black text-slate-500 uppercase tracking-widest px-1">Recomendados para você</h3>
                <div className="space-y-4">
                  {sugestoes.map((s) => {
                    const info = getVideoId(s.url_video);
                    const thumb = s.thumbnail || (info?.type === 'youtube' ? `https://img.youtube.com/vi/${info.id}/mqdefault.jpg` : null);
                    return (
                      <Link 
                        key={s.id} 
                        href={`/video/${s.id}`}
                        className="group flex items-center gap-4 hover:bg-white/5 p-2 rounded-2xl transition-all"
                      >
                        <div className="relative w-28 aspect-video rounded-xl overflow-hidden bg-slate-900 shrink-0 shadow-lg">
                           {thumb ? (
                             <img src={thumb} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="" />
                           ) : (
                             <div className="w-full h-full flex items-center justify-center">
                               <PlayCircle className="text-slate-700" size={24} />
                             </div>
                           )}
                        </div>
                        <div className="flex-1 min-w-0">
                           <h4 className="text-[12px] font-bold text-slate-200 line-clamp-2 leading-tight group-hover:text-orange-500 transition-colors">
                             {s.titulo}
                           </h4>
                           <p className="text-[9px] text-slate-500 font-black uppercase mt-1.5 flex items-center gap-2">
                              <div className="w-1 h-1 rounded-full bg-slate-700" />
                              {s.visualizacoes || 0} views
                           </p>
                        </div>
                      </Link>
                    );
                  })}
                </div>
              </div>
            )}
          </div>
        )}

        {activeTab === 'ai' && (
          <div className="flex flex-col h-full">
            <div className="p-4 border-b border-white/5 bg-gradient-to-r from-slate-900 to-indigo-950/30">
              <p className="text-[10px] font-bold uppercase tracking-widest text-indigo-300">Inteligência Artificial Ativa</p>
              <p className="text-[11px] text-slate-400 mt-1">O tutor tem acesso ao conteúdo desta aula para tirar suas dúvidas.</p>
            </div>
            <div className="flex-1">
              <AIChatClient videoContext={video} />
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
