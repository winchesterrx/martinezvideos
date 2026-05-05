'use client';

import { useState, useEffect, useRef } from 'react';
import { MessageSquare, Users, ThumbsUp, Eye, Send, Radio } from 'lucide-react';

interface Message {
  id: string;
  author: string;
  avatar: string;
  text: string;
  timestamp: string;
}

export default function MartinezChat({ videoId }: { videoId: string }) {
  const [messages, setMessages] = useState<Message[]>([]);
  const [stats, setStats] = useState<any>(null);
  const [chatId, setChatId] = useState<string | null>(null);
  const scrollRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Busca estatísticas iniciais e ChatId
    const fetchInitial = async () => {
      const res = await fetch(`/api/youtube/stats?videoId=${videoId}`);
      const data = await res.json();
      if (data.stats) {
        setStats(data.stats);
        if (data.stats.liveChatId) {
          setChatId(data.stats.liveChatId);
        } else {
          // Fallback para comentários se não houver chat ativo
          const cRes = await fetch(`/api/youtube/stats?type=comments&videoId=${videoId}`);
          const cData = await cRes.json();
          if (cData.comments) {
            const formatted = cData.comments.map((c: any) => ({
              id: c.id,
              author: c.snippet.topLevelComment.snippet.authorDisplayName,
              avatar: c.snippet.topLevelComment.snippet.authorProfileImageUrl,
              text: c.snippet.topLevelComment.snippet.textDisplay,
              timestamp: c.snippet.topLevelComment.snippet.publishedAt
            }));
            setMessages(formatted);
          }
        }
      }
    };
    fetchInitial();

    // Polling de mensagens se for Live
    let interval: any;
    if (chatId) {
      interval = setInterval(async () => {
        const res = await fetch(`/api/youtube/stats?chatId=${chatId}`);
        const data = await res.json();
        if (data.messages) {
          const formatted = data.messages.map((m: any) => ({
            id: m.id,
            author: m.authorDetails.displayName,
            avatar: m.authorDetails.profileImageUrl,
            text: m.snippet.displayMessage,
            timestamp: m.snippet.publishedAt
          }));
          setMessages(formatted);
        }
      }, 5000);
    }

    return () => clearInterval(interval);
  }, [videoId, chatId]);

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [messages]);

  return (
    <div className="flex flex-col h-full bg-slate-950/50 backdrop-blur-3xl border-l border-white/5 shadow-[-20px_0_50px_rgba(0,0,0,0.5)]">
      
      {/* Header com Stats em Tempo Real */}
      <div className="p-6 border-b border-white/10 bg-slate-900/40">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-orange-500 rounded-lg shadow-lg shadow-orange-500/20">
              <MessageSquare size={20} className="text-white" />
            </div>
            <div>
              <h2 className="text-sm font-black text-white uppercase tracking-wider">Chat Martinez</h2>
              <div className="flex items-center gap-1.5 text-[10px] text-emerald-400 font-bold uppercase">
                <div className="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse" /> 
                {stats?.isLive ? 'Conectado Ao Vivo' : 'Histórico de Transmissão'}
              </div>
            </div>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-3 gap-2">
           <div className="bg-white/5 border border-white/5 rounded-xl p-2 text-center">
              <div className="flex items-center justify-center gap-1 text-slate-500 mb-1">
                <Eye size={12} />
                <span className="text-[9px] font-bold uppercase">Views</span>
              </div>
              <div className="text-sm font-black text-white">{stats?.views ? Number(stats.views).toLocaleString() : '---'}</div>
           </div>
           <div className="bg-white/5 border border-white/5 rounded-xl p-2 text-center">
              <div className="flex items-center justify-center gap-1 text-slate-500 mb-1">
                <ThumbsUp size={12} />
                <span className="text-[9px] font-bold uppercase">Likes</span>
              </div>
              <div className="text-sm font-black text-white">{stats?.likes ? Number(stats.likes).toLocaleString() : '---'}</div>
           </div>
           <div className="bg-orange-500/10 border border-orange-500/20 rounded-xl p-2 text-center">
              <div className="flex items-center justify-center gap-1 text-orange-500 mb-1">
                <Users size={12} />
                <span className="text-[9px] font-bold uppercase">Online</span>
              </div>
              <div className="text-sm font-black text-white">{stats?.isLive ? 'LIVE' : 'OFF'}</div>
           </div>
        </div>
      </div>

      {/* Área de Mensagens */}
      <div 
        ref={scrollRef}
        className="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar"
      >
        {messages.length > 0 ? (
          messages.map((m) => (
            <div key={m.id} className="flex gap-3 group animate-in fade-in slide-in-from-bottom-2 duration-300">
              <img src={m.avatar} className="w-8 h-8 rounded-full border border-white/10 shadow-lg" alt="" />
              <div className="flex-1">
                <div className="flex items-baseline gap-2 mb-0.5">
                  <span className="text-[11px] font-black text-orange-400 uppercase">{m.author}</span>
                  <span className="text-[9px] text-slate-600">{new Date(m.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                <p className="text-sm text-slate-300 leading-snug">{m.text}</p>
              </div>
            </div>
          ))
        ) : (
          <div className="h-full flex flex-col items-center justify-center p-12 text-center text-slate-700">
            <Radio size={48} className="mb-4 opacity-10 animate-pulse" />
            <p className="text-xs font-bold uppercase tracking-widest opacity-30">
              {stats?.isLive ? 'Aguardando primeiras mensagens...' : 'O Chat só fica disponível durante a transmissão ao vivo.'}
            </p>
            {!stats?.isLive && (
               <p className="mt-2 text-[10px] text-slate-800">Para replays, as mensagens do YouTube são bloqueadas por segurança externa.</p>
            )}
          </div>
        )}
      </div>

      {/* Input Fake (Apenas visual para premium) */}
      <div className="p-4 bg-slate-900/60 border-t border-white/5">
        <div className="relative">
          <input 
            type="text" 
            placeholder={stats?.isLive ? "Diga algo no chat..." : "Chat encerrado"}
            disabled={!stats?.isLive}
            className="w-full bg-slate-950 border border-white/10 rounded-2xl pl-4 pr-12 py-3 text-sm text-white focus:border-orange-500 transition-all outline-none"
          />
          <button className="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-orange-500 hover:text-white transition-colors">
            <Send size={18} />
          </button>
        </div>
      </div>

    </div>
  );
}
