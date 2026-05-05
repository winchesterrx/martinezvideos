'use client';

import { useState } from 'react';
import { MessageSquare, RefreshCw } from 'lucide-react';

export default function YouTubeChat({ videoId, domain }: { videoId: string, domain: string }) {
  const [isReplay, setIsReplay] = useState(false);

  const currentDomain = typeof window !== 'undefined' ? window.location.hostname : domain;

  const chatUrl = isReplay 
    ? `https://www.youtube.com/live_chat_replay?v=${videoId}&embed_domain=${currentDomain}`
    : `https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${currentDomain}`;

  return (
    <div className="flex flex-col h-full">
      <div className="p-4 border-b border-white/10 flex items-center justify-between bg-slate-900/40">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-orange-500 rounded-lg shadow-lg shadow-orange-500/20">
            <MessageSquare size={20} className="text-white" />
          </div>
          <div>
            <h2 className="text-sm font-black text-white uppercase tracking-wider">
              {isReplay ? 'Repetição do Chat' : 'Chat da Comunidade'}
            </h2>
            <div className="flex items-center gap-1.5 text-[10px] text-emerald-400 font-bold">
              <div className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse" /> 
              {isReplay ? 'MODO REPLAY' : 'SINAL ONLINE'}
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button 
            onClick={() => window.open(chatUrl, '_blank', 'width=400,height=600,menubar=no,status=no,location=no')}
            className="p-2 text-orange-500 hover:text-white hover:bg-orange-500/10 rounded-lg transition-all flex items-center gap-2 text-[10px] font-bold uppercase border border-orange-500/20"
          >
            Abrir Pop-out
          </button>
          <button 
            onClick={() => setIsReplay(!isReplay)}
            className="p-2 text-slate-500 hover:text-white hover:bg-white/5 rounded-lg transition-all flex items-center gap-2 text-[10px] font-bold uppercase"
            title="Alternar entre Ao Vivo e Replay"
          >
            <RefreshCw size={14} className={isReplay ? 'rotate-180' : ''} />
            {isReplay ? 'Ao Vivo' : 'Replay'}
          </button>
        </div>
      </div>

      <div className="flex-1 bg-black/40">
        <iframe 
          key={chatUrl}
          className="w-full h-full"
          src={chatUrl}
          title="YouTube Live Chat"
        ></iframe>
      </div>
    </div>
  );
}
