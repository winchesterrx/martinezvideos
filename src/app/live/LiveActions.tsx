'use client';

import { Share2, Bell, Check } from 'lucide-react';
import { useState } from 'react';

export default function LiveActions({ liveTitle }: { liveTitle: string }) {
  const [copied, setCopied] = useState(false);
  const [notified, setNotified] = useState(false);

  const handleShare = () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleNotify = async () => {
    // Aqui simulamos a ativação de lembrete
    setNotified(true);
    setTimeout(() => setNotified(false), 3000);
    
    // Futuramente aqui chamamos a API /api/notifications/subscribe
    alert('Você será notificado assim que as próximas aulas começarem!');
  };

  return (
    <div className="hidden md:flex items-center gap-3">
      <button 
        onClick={handleShare}
        className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 rounded-xl text-slate-300 text-sm font-bold border border-white/5 transition-all backdrop-blur-md"
      >
        {copied ? <Check size={16} className="text-emerald-500" /> : <Share2 size={16} />}
        {copied ? 'Link Copiado!' : 'Compartilhar'}
      </button>
      
      <button 
        onClick={handleNotify}
        className={`flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold border transition-all backdrop-blur-md ${
          notified 
          ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-500' 
          : 'bg-orange-500/10 hover:bg-orange-500/20 border-orange-500/20 text-orange-500'
        }`}
      >
        <Bell size={16} className={notified ? 'animate-bounce' : ''} />
        {notified ? 'Lembrete Ativo!' : 'Notificar'}
      </button>
    </div>
  );
}
