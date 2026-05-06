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
  const [messages, setMessages] = useState<Message[]>([
    {
      id: 'welcome-1',
      author: 'Equipe Martinez',
      avatar: 'https://ui-avatars.com/api/?name=Martinez&background=f97316&color=fff',
      text: 'Seja bem-vindo à nossa Masterclass! Sintonize e aproveite o conteúdo.',
      timestamp: new Date().toISOString()
    },
    {
      id: 'welcome-2',
      author: 'Suporte Técnico',
      avatar: 'https://ui-avatars.com/api/?name=Suporte&background=0f172a&color=fff',
      text: 'Dúvidas sobre a plataforma? Ligue para o nosso time pelo telefone (17) 3411-1444.',
      timestamp: new Date().toISOString()
    }
  ]);
  const [stats, setStats] = useState<any>(null);
  const [chatId, setChatId] = useState<string | null>(null);
  const [quotaExceeded, setQuotaExceeded] = useState(false);
  const scrollRef = useRef<HTMLDivElement>(null);
  const chatEndRef = useRef<HTMLDivElement>(null);

  // 1. Busca inicial do ChatId
  useEffect(() => {
    const fetchInitial = async () => {
      try {
        const res = await fetch(`/api/youtube/stats?videoId=${videoId}`);
        const data = await res.json();
        
        if (data.quotaExceeded) {
          setQuotaExceeded(true);
          return;
        }

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
              })).reverse();
              setMessages(prev => {
                const existingIds = new Set(prev.map(msg => msg.id));
                const newMsgs = formatted.filter((m: any) => !existingIds.has(m.id));
                return [...prev, ...newMsgs];
              });
            }
          }
        }
      } catch (e) {
        console.error("Erro inicial chat:", e);
      }
    };
    fetchInitial();
  }, [videoId]);

  // 2. Polling de Mensagens (Independente)
  useEffect(() => {
    if (quotaExceeded) return;
    let interval: any;
    
    const pollMessages = async () => {
      try {
        if (chatId) {
          const res = await fetch(`/api/youtube/stats?chatId=${chatId}`);
          const data = await res.json();
          if (data.messages && data.messages.length > 0) {
            const formatted = data.messages.map((m: any) => ({
              id: m.id,
              author: m.authorDetails.displayName,
              avatar: m.authorDetails.profileImageUrl,
              text: m.snippet.displayMessage,
              timestamp: m.snippet.publishedAt
            }));
            setMessages(prev => {
              const existingIds = new Set(prev.map(msg => msg.id));
              const newMsgs = formatted.filter((m: any) => !existingIds.has(m.id));
              return [...prev, ...newMsgs];
            });
          }
        } else {
          // Se não houver chat, atualiza comentários ocasionalmente
          const cRes = await fetch(`/api/youtube/stats?type=comments&videoId=${videoId}`);
          const cData = await cRes.json();
          if (cData.comments) {
            const formatted = cData.comments.map((c: any) => ({
              id: c.id,
              author: c.snippet.topLevelComment.snippet.authorDisplayName,
              avatar: c.snippet.topLevelComment.snippet.authorProfileImageUrl,
              text: c.snippet.topLevelComment.snippet.textDisplay,
              timestamp: c.snippet.topLevelComment.snippet.publishedAt
            })).reverse();
            setMessages(prev => {
              const existingIds = new Set(prev.map(msg => msg.id));
              const newMsgs = formatted.filter((m: any) => !existingIds.has(m.id));
              return [...prev, ...newMsgs];
            });
          }
        }
      } catch (e) {
        console.error("Erro polling chat:", e);
      }
    };

    // Intervalo de 5 segundos para suavizar o consumo
    interval = setInterval(pollMessages, 5000);
    return () => clearInterval(interval);
  }, [videoId, chatId, quotaExceeded]);

  useEffect(() => {
    if (scrollRef.current) {
      const { scrollTop, scrollHeight, clientHeight } = scrollRef.current;
      const isAtBottom = scrollHeight - scrollTop <= clientHeight + 100;
      
      if (isAtBottom) {
        scrollRef.current.scrollTo({
          top: scrollHeight,
          behavior: 'smooth'
        });
      }
    }
  }, [messages]);

  const domain = typeof window !== 'undefined' ? window.location.hostname : 'localhost';

  return (
    <div className="flex flex-col h-full bg-slate-900/10">

      {/* Header Compacto */}
      <div className="p-4 border-b border-white/5 bg-slate-900/60 backdrop-blur-md shrink-0">
        <div className="flex items-center gap-3">
          <div className="p-1.5 bg-orange-500 rounded-lg shadow-lg shadow-orange-500/20">
            <MessageSquare size={16} className="text-white" />
          </div>
          <div>
            <h2 className="text-[11px] font-black text-white uppercase tracking-wider">Chat da Comunidade</h2>
            <div className="flex items-center gap-1.5 text-[9px] text-emerald-400 font-bold uppercase">
              <div className="w-1 h-1 bg-emerald-400 rounded-full animate-pulse" />
              Sinal Ativo {quotaExceeded && <span className="text-slate-500">(Modo Fallback)</span>}
            </div>
          </div>
        </div>
      </div>

      {/* Área de Mensagens / Fallback */}
      <div
        ref={scrollRef}
        className="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-3 custom-scrollbar"
        style={{ height: '0px' }}
      >
        {quotaExceeded ? (
          <iframe
            src={`https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${domain}`}
            className="w-full h-full border-0"
            allowFullScreen
          ></iframe>
        ) : (
          <>
            {messages.length > 0 ? (
              messages.map((m) => (
                <div key={m.id} className="flex gap-3 group animate-in slide-in-from-bottom-3 fade-in duration-500 fill-mode-both">
                  <img src={m.avatar} className="w-8 h-8 rounded-full border border-white/10 shadow-lg shrink-0" alt="" />
                  <div className="flex-1 min-w-0">
                    <div className="flex items-baseline gap-2 mb-0.5">
                      <span className="text-[11px] font-black text-orange-400 uppercase truncate">{m.author}</span>
                      <span className="text-[9px] text-slate-600 shrink-0">{new Date(m.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                    <p className="text-sm text-slate-300 leading-snug break-words" dangerouslySetInnerHTML={{ __html: m.text }}></p>
                  </div>
                </div>
              ))
            ) : (
              <div className="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div className="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                  <MessageSquare size={32} className="text-slate-700 animate-pulse" />
                </div>
                <h3 className="text-white font-bold mb-2">Conectando...</h3>
              </div>
            )}
            <div ref={chatEndRef} />
          </>
        )}
      </div>

      {/* Input de Chat (Apenas se não for quotaExceeded, pois o iframe já tem o seu) */}
      {!quotaExceeded && (
        <div className="p-4 bg-slate-950/80 border-t border-white/10 backdrop-blur-xl">
          <div className="relative group">
            <input
              type="text"
              placeholder="Diga algo no chat..."
              className="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 pr-12 text-sm text-white focus:border-orange-500 transition-all outline-none"
            />
            <button className="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-slate-500 hover:text-orange-500 transition-colors">
              <Send size={18} />
            </button>
          </div>
        </div>
      )}

    </div>
  );
}
