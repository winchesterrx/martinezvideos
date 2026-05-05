'use client';

import { Share2, Bell, Check, ThumbsUp, Eye } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function LiveActions({ videoId, youtubeStats }: { videoId: string, youtubeStats: any }) {
  const [copied, setCopied] = useState(false);
  const [notified, setNotified] = useState(false);
  const [localStats, setLocalStats] = useState({ likes: 0, views: 0 });
  const [isLiked, setIsLiked] = useState(false);

  useEffect(() => {
    // Carrega métricas do banco Martinez
    const fetchMetrics = async () => {
      const res = await fetch(`/api/live/metrics?videoId=${videoId}`);
      const data = await res.json();
      setLocalStats(data);
    };
    fetchMetrics();

    // Registra visualização local ao carregar
    fetch('/api/live/metrics', {
      method: 'POST',
      body: JSON.stringify({ videoId, type: 'view' }),
      headers: { 'Content-Type': 'application/json' }
    });
  }, [videoId]);

  const handleLike = async () => {
    if (isLiked) return;
    const res = await fetch('/api/live/metrics', {
      method: 'POST',
      body: JSON.stringify({ videoId, type: 'like' }),
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    setLocalStats(prev => ({ ...prev, likes: data.total }));
    setIsLiked(true);
  };

  const handleShare = () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="flex flex-col gap-4 py-6">
      <div className="flex flex-wrap items-center justify-between gap-6 bg-slate-900/40 p-6 rounded-2xl border border-white/5 backdrop-blur-xl">
        
        {/* Stats de Visualização Combinada */}
        <div className="flex items-center gap-6">
          <div className="flex flex-col">
            <div className="flex items-center gap-2 text-slate-400 mb-1">
              <Eye size={16} className="text-orange-500" />
              <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Total de Views</span>
            </div>
            <div className="text-2xl font-black text-white">
              {((youtubeStats?.views || 0) + localStats.views).toLocaleString()}
            </div>
          </div>
          <div className="w-px h-10 bg-white/10" />
          <div className="flex flex-col">
            <span className="text-[10px] font-bold text-slate-500 uppercase mb-1">Status</span>
            <div className="flex items-center gap-2 text-sm font-black text-emerald-400">
               <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
               {youtubeStats?.isLive ? 'AO VIVO' : 'GRAVADO'}
            </div>
          </div>
        </div>

        {/* Botões de Ação Martinez */}
        <div className="flex items-center gap-4">
          
          <div className="flex items-center bg-white/5 rounded-full p-1 border border-white/10">
            <button 
              onClick={handleLike}
              className={`flex items-center gap-2 px-5 py-2 rounded-full text-sm font-black transition-all ${
                isLiked 
                ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20' 
                : 'text-slate-300 hover:bg-white/10'
              }`}
            >
              <ThumbsUp size={18} className={isLiked ? 'fill-white' : ''} />
              {localStats.likes} <span className="text-[10px] opacity-60">no site</span>
            </button>
            <div className="w-px h-6 bg-white/10 mx-1" />
            <div className="px-4 text-xs font-bold text-slate-500">
              {Number(youtubeStats?.likes || 0).toLocaleString()} <span className="text-[9px]">no YT</span>
            </div>
          </div>

          <button 
            onClick={handleShare}
            className="flex items-center gap-2 px-6 py-3 bg-white/5 hover:bg-white/10 rounded-full text-slate-300 text-sm font-black border border-white/10 transition-all"
          >
            {copied ? <Check size={18} className="text-emerald-500" /> : <Share2 size={18} />}
            {copied ? 'Copiado' : 'Compartilhar'}
          </button>

          <button 
            onClick={() => setNotified(!notified)}
            className={`flex items-center gap-2 px-6 py-3 rounded-full text-sm font-black border transition-all ${
              notified 
              ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400' 
              : 'bg-white/5 hover:bg-white/10 border-white/10 text-slate-300'
            }`}
          >
            <Bell size={18} className={notified ? 'fill-emerald-400' : ''} />
            {notified ? 'Lembrete' : 'Me Avisar'}
          </button>

        </div>
      </div>
    </div>
  );
}
