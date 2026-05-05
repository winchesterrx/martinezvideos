'use client';

import { useState } from 'react';
import { Sparkles, Route, PlayCircle } from 'lucide-react';
import AIChatClient from './AIChatClient';
import Link from 'next/link';

export default function VideoSidebarTabs({ 
  video, 
  trilha, 
  sequencia_titulo 
}: { 
  video: any, 
  trilha: any[], 
  sequencia_titulo: string 
}) {
  const hasTrilha = trilha.length > 0;
  const [activeTab, setActiveTab] = useState<'trilha' | 'ai'>(hasTrilha ? 'trilha' : 'ai');

  return (
    <div className="flex flex-col h-full">
      {/* Tabs Header */}
      <div className="flex border-b border-white/5">
        {hasTrilha && (
          <button 
            onClick={() => setActiveTab('trilha')}
            className={`flex-1 py-4 font-semibold text-sm transition-colors border-b-2 ${
              activeTab === 'trilha' 
                ? 'text-orange-400 border-orange-500 bg-orange-500/5' 
                : 'text-slate-400 border-transparent hover:text-slate-300'
            }`}
          >
            <div className="flex items-center justify-center gap-2">
              <Route size={16} /> Trilha
            </div>
          </button>
        )}
        <button 
          onClick={() => setActiveTab('ai')}
          className={`flex-1 py-4 font-semibold text-sm transition-colors border-b-2 ${
            activeTab === 'ai' 
              ? 'text-indigo-400 border-indigo-500 bg-indigo-500/5' 
              : 'text-slate-400 border-transparent hover:text-slate-300'
          }`}
        >
          <div className="flex items-center justify-center gap-2">
            <Sparkles size={16} /> Tutor IA
          </div>
        </button>
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-y-auto">
        {activeTab === 'trilha' && hasTrilha && (
          <div className="p-4 space-y-4">
            <div className="mb-6">
              <h3 className="font-bold text-white text-lg">{sequencia_titulo || 'Sequência Lógica'}</h3>
              <p className="text-xs text-slate-400 mt-1">{trilha.length} aulas nesta trilha</p>
            </div>
            
            <div className="space-y-3 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-white/10 before:to-transparent">
              {trilha.map((t, index) => {
                const isActive = t.id === video.id;
                return (
                  <Link 
                    href={`/video/${t.id}`} 
                    key={t.id}
                    className={`relative flex items-center gap-4 p-3 rounded-xl transition-all ${
                      isActive ? 'bg-orange-500/10 border border-orange-500/30' : 'hover:bg-white/5 border border-transparent'
                    }`}
                  >
                    <div className={`shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 z-10 bg-slate-900 ${
                      isActive ? 'border-orange-500 text-orange-500 shadow-[0_0_15px_rgba(249,115,22,0.5)]' : 'border-slate-700 text-slate-400'
                    }`}>
                      {index + 1}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className={`text-sm font-semibold truncate ${isActive ? 'text-orange-400' : 'text-slate-300'}`}>
                        {t.titulo}
                      </p>
                      {isActive && <span className="text-[10px] text-orange-500/80 uppercase font-bold">Assistindo</span>}
                    </div>
                  </Link>
                );
              })}
            </div>
          </div>
        )}

        {activeTab === 'ai' && (
          <div className="flex flex-col h-full">
            <div className="p-4 border-b border-white/5 bg-gradient-to-r from-slate-900 to-indigo-950/30">
              <p className="text-xs text-indigo-300">Faça perguntas sobre a aula atual. O tutor tem todo o contexto do vídeo.</p>
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
