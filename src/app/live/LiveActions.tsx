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
    <div className="flex flex-col gap-4 py-6 border-b border-white/5">
      <div className="flex flex-wrap items-center justify-between gap-6">
        
        {/* Stats de Visualização */}
        <div className="flex items-center gap-6">
          <div className="flex items-center gap-2 text-slate-400">
            <Eye size={18} className="text-orange-500" />
            <span className="text-sm font-bold text-white">
              {((youtubeStats?.views || 0) + localStats.views).toLocaleString()} visualizações
            </span>
          </div>
          <div className="text-slate-600 font-bold">•</div>
          <div className="text-sm font-bold text-slate-400">
            {youtubeStats?.isLive ? 'Ao vivo agora' : 'Transmitido em 05/05/2026'}
          </div>
        </div>

        {/* Botões de Ação Estilo YouTube */}
        <div className="flex items-center gap-3">
          
          <button 
            onClick={handleLike}
            className={`flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-black transition-all border ${
              isLiked 
              ? 'bg-orange-500 border-orange-500 text-white shadow-lg shadow-orange-500/20' 
              : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10'
            }`}
          >
            <ThumbsUp size={18} className={isLiked ? 'fill-white' : ''} />
            {((youtubeStats?.likes || 0) + localStats.likes).toLocaleString()}
          </button>

          <button 
            onClick={handleShare}
            className="flex items-center gap-2 px-6 py-2.5 bg-white/5 hover:bg-white/10 rounded-full text-slate-300 text-sm font-black border border-white/10 transition-all"
          >
            {copied ? <Check size={18} className="text-emerald-500" /> : <Share2 size={18} />}
            {copied ? 'Copiado' : 'Compartilhar'}
          </button>

          <button 
            onClick={() => setNotified(!notified)}
            className={`flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-black border transition-all ${
              notified 
              ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' 
              : 'bg-white/5 hover:bg-white/10 border-white/10 text-slate-300'
            }`}
          >
            <Bell size={18} className={notified ? 'fill-emerald-400' : ''} />
            {notified ? 'Notificado' : 'Notificar'}
          </button>

        </div>
      </div>
    </div>
  );
}
