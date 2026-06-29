'use client';

import { Share2, Bell, Check, ThumbsUp, Eye } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function LiveActions({ videoId, youtubeStats, userId, userName }: { videoId: string, youtubeStats: any, userId?: any, userName?: string }) {
  const [copied, setCopied] = useState(false);
  const [notified, setNotified] = useState(false);
  const [localStats, setLocalStats] = useState({ likes: 0, views: 0 });
  const [isLiked, setIsLiked] = useState(false);

  useEffect(() => {
    // Carrega métricas do banco Martinez
    const fetchMetrics = async () => {
      try {
        const res = await fetch(`/api/live/metrics?videoId=${videoId}&userId=${userId || ''}`, { cache: 'no-store' });
        const data = await res.json();
        setLocalStats(data);
        setIsLiked(data.isLiked);
        setNotified(data.isNotified);
      } catch (e) {
        console.error("Erro ao buscar métricas:", e);
      }
    };
    fetchMetrics();

    // Registra visualização local após 5 segundos de retenção
    const viewTimeout = setTimeout(() => {
      fetch('/api/live/metrics', {
        method: 'POST',
        body: JSON.stringify({ videoId, videoUrl: window.location.href, type: 'view', userId, userName }),
        headers: { 'Content-Type': 'application/json' }
      });
    }, 5000);

    return () => clearTimeout(viewTimeout);
  }, [videoId]);

  const handleLike = async () => {
    if (!videoId) return;
    if (!userId) {
      window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
      return;
    }

    try {
      const origin = window.location.origin;
      const res = await fetch(`${origin}/api/live/metrics`, {
        method: 'POST',
        body: JSON.stringify({ videoId, videoUrl: window.location.href, type: 'like', userId, userName }),
        headers: { 'Content-Type': 'application/json' }
      });

      const data = await res.json();

      if (!res.ok) {
        throw new Error(data.error || 'Erro desconhecido no servidor');
      }

      setLocalStats(prev => ({ ...prev, likes: data.total }));
      setIsLiked(data.liked);

      // Se curtiu agora, oferece para curtir no YouTube também
      if (data.liked) {
        if (confirm('Curtiu no Martinez! Quer abrir a live oficial para curtir no YouTube também?')) {
          window.open(`https://www.youtube.com/watch?v=${videoId}`, '_blank');
        }
      }
    } catch (error: any) {
      console.error('Falha técnica na curtida:', error);
      alert('Erro do Servidor: ' + error.message);
    }
  };

  const handleShare = async () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url);
    setCopied(true);
    
    // Registra compartilhamento no banco
    await fetch('/api/live/metrics', {
      method: 'POST',
      body: JSON.stringify({ videoId, videoUrl: window.location.href, type: 'share', userId, userName }),
      headers: { 'Content-Type': 'application/json' }
    });

    setTimeout(() => setCopied(false), 2000);
  };

  const handleNotify = async () => {
    if (!userId) {
      window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
      return;
    }
    const nextState = !notified;
    setNotified(nextState);

    try {
      // Registra pedido de notificação no banco (Toggles global preference)
      await fetch('/api/live/metrics', {
        method: 'POST',
        body: JSON.stringify({ videoId, videoUrl: window.location.href, type: 'notify', userId, userName }),
        headers: { 'Content-Type': 'application/json' }
      });
      
      if (nextState) {
        alert('Lembrete Ativado! Você será notificado quando Martinez estiver Online.');
      }
    } catch (e) {
      console.error(e);
      setNotified(!nextState);
    }
  };

  return (
    <div className="flex flex-col gap-6 py-6">
      <div className="flex flex-wrap items-center justify-between gap-8 border-b border-white/5 pb-8">
        
        {/* Stats Minimalistas */}
        <div className="flex items-center gap-12">
          <div className="flex flex-col">
            <span className="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-1">Audiência</span>
            <div className="flex items-center gap-2">
              <div className="w-1.5 h-1.5 bg-orange-500 rounded-full" />
              <span className="text-xl font-medium text-white tracking-tight">
                {((youtubeStats?.views || 0) + localStats.views).toLocaleString()}
              </span>
            </div>
          </div>
          
          <div className="flex flex-col">
            <span className="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mb-1">Status</span>
            <span className={`text-xs font-bold ${youtubeStats?.isLive ? 'text-red-500' : 'text-slate-400'}`}>
              {youtubeStats?.isLive ? '● TRANSMITINDO' : 'OFFLINE'}
            </span>
          </div>
        </div>

        {/* Botões Clean */}
        <div className="flex items-center gap-3">
          
          <div className="flex items-center bg-white/[0.03] border border-white/10 rounded-xl overflow-hidden">
            <button 
              onClick={handleLike}
              className={`flex items-center gap-2 px-5 py-2.5 text-sm font-medium transition-all ${
                isLiked 
                ? 'bg-orange-500 text-white' 
                : 'text-slate-300 hover:bg-white/5'
              }`}
            >
              <ThumbsUp size={16} className={isLiked ? 'fill-white' : ''} />
              {localStats.likes}
            </button>
            <div className="w-px h-4 bg-white/10" />
            <div className="px-4 text-[11px] font-medium text-slate-500">
              {Number(youtubeStats?.likes || 0).toLocaleString()} <span className="opacity-50 ml-0.5">YT</span>
            </div>
          </div>

          <button 
            onClick={handleShare}
            className="flex items-center gap-2 px-5 py-2.5 bg-white/[0.03] hover:bg-white/[0.08] border border-white/10 rounded-xl text-slate-300 text-sm font-medium transition-all"
          >
            <Share2 size={16} />
            Compartilhar
          </button>

          <button 
            onClick={handleNotify}
            className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium border transition-all ${
              notified 
              ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' 
              : 'bg-white/[0.03] hover:bg-white/[0.08] border border-white/10 text-slate-300'
            }`}
          >
            <Bell size={16} className={notified ? 'fill-emerald-500' : ''} />
            {notified ? 'Ativado' : 'Avisar'}
          </button>

        </div>
      </div>
    </div>
  );
}
