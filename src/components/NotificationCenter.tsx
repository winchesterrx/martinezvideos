'use client';

import { useState, useEffect } from 'react';
import { Bell, X, Radio, ArrowRight, Check, Trash2 } from 'lucide-react';
import Link from 'next/link';

export default function NotificationCenter() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [showToast, setShowToast] = useState<any>(null);
  const [isOpen, setIsOpen] = useState(false);

  const [liveInfo, setLiveInfo] = useState<any>(null);

  useEffect(() => {
    fetchNotifications();
    const interval = setInterval(fetchNotifications, 30000); // Check every 30s
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    if (showToast && showToast.tipo === 'LIVE') {
       fetchLiveInfo();
    }
  }, [showToast]);

  const fetchLiveInfo = async () => {
    try {
      const res = await fetch('/api/admin/live/data');
      const data = await res.json();
      setLiveInfo(data.live);
    } catch (e) {
      console.error(e);
    }
  };

  const fetchNotifications = async () => {
    try {
      const res = await fetch('/api/user/notifications');
      const data = await res.json();
      
      const newNotifications = data.notifications || [];
      
      // Se tiver notificação nova (não lida) e for do tipo LIVE, mostra o Toast flutuante
      const unreadLive = newNotifications.find((n: any) => n.lida === 'N' && n.tipo === 'LIVE');
      
      // Evita mostrar o mesmo toast repetidamente
      if (unreadLive && (!showToast || showToast.id !== unreadLive.id)) {
        setShowToast(unreadLive);
      }

      setNotifications(newNotifications);
    } catch (e) {
      console.error(e);
    }
  };

  const markAsRead = async (id: string | 'all') => {
    try {
      await fetch('/api/user/notifications', {
        method: 'PATCH',
        body: JSON.stringify({ id }),
        headers: { 'Content-Type': 'application/json' }
      });
      if (id === 'all' || (showToast && showToast.id === id)) {
        setShowToast(null);
        setLiveInfo(null);
      }
      fetchNotifications();
    } catch (e) {
      console.error(e);
    }
  };

  const deleteNotification = async (e: React.MouseEvent, id: string | 'all') => {
    e.stopPropagation();
    e.preventDefault();
    if (id === 'all' && !confirm('Deseja apagar todas as notificações?')) return;
    
    try {
      await fetch('/api/user/notifications', {
        method: 'DELETE',
        body: JSON.stringify({ id }),
        headers: { 'Content-Type': 'application/json' }
      });
      fetchNotifications();
    } catch (e) {
      console.error(e);
    }
  };

  const unreadCount = notifications.filter(n => n.lida === 'N').length;

  const getVideoId = (url: string) => {
    if (!url) return null;
    const match = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    return match ? match[1] : null;
  };

  return (
    <div className="relative flex items-center">
      {/* Botão do Sininho no Header */}
      <button 
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2.5 rounded-xl bg-white/5 hover:bg-white/10 transition-all border border-white/5 group"
      >
        <Bell size={20} className={`text-slate-400 group-hover:text-white transition-colors ${unreadCount > 0 ? 'animate-bounce' : ''}`} />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 w-5 h-5 bg-orange-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-slate-950">
            {unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown de Notificações */}
      {isOpen && (
        <>
          <div className="fixed inset-0 z-[100]" onClick={() => setIsOpen(false)} />
          <div className="absolute right-0 top-14 w-80 md:w-96 bg-slate-900 border border-white/10 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5)] z-[101] overflow-hidden animate-in fade-in slide-in-from-top-5 duration-300">
            <div className="p-5 border-b border-white/5 flex items-center justify-between bg-slate-950/50">
              <h3 className="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                <Bell size={14} className="text-orange-500" /> Notificações
              </h3>
              <div className="flex items-center gap-4">
                {unreadCount > 0 && (
                  <button 
                    onClick={() => markAsRead('all')}
                    className="text-[10px] font-bold text-orange-500 hover:text-orange-400 transition-colors uppercase"
                  >
                    Marcar lidas
                  </button>
                )}
                <button 
                  onClick={(e) => deleteNotification(e, 'all')}
                  className="text-slate-500 hover:text-red-500 transition-colors"
                  title="Limpar tudo"
                >
                  <Trash2 size={14} />
                </button>
              </div>
            </div>
            
            <div className="max-h-[400px] overflow-y-auto custom-scrollbar">
              {notifications.length > 0 ? (
                notifications.map((n) => {
                  const itemVideoId = n.tipo === 'LIVE' ? getVideoId(n.link) : null;
                  const date = new Date(n.created_at);
                  
                  return (
                    <div 
                      key={n.id} 
                      className={`p-5 border-b border-white/5 hover:bg-white/[0.02] transition-colors relative group overflow-hidden ${n.lida === 'N' ? 'bg-orange-500/[0.03]' : ''}`}
                    >
                      {/* Background Banner para Lives */}
                      {n.tipo === 'LIVE' && itemVideoId && (
                        <div className="absolute inset-0 z-0">
                           <img 
                             src={`https://img.youtube.com/vi/${itemVideoId}/maxresdefault.jpg`} 
                             className="w-full h-full object-cover opacity-30 group-hover:opacity-40 transition-opacity" 
                             alt="" 
                           />
                           <div className="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/95 to-slate-900/70" />
                        </div>
                      )}

                      <div className="relative z-10 flex gap-4">
                        <div className={`w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-white/5 shadow-lg ${n.tipo === 'LIVE' ? 'bg-red-500/10 text-red-500 border-red-500/20' : 'bg-blue-500/10 text-blue-500'}`}>
                          {n.tipo === 'LIVE' ? <Radio size={22} className="animate-pulse" /> : <Bell size={22} />}
                        </div>
                        <div className="flex-1">
                          <div className="flex items-center justify-between mb-1.5">
                            <span className="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                              {date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })} • {date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                            </span>
                            <div className="flex items-center gap-2">
                              {n.lida === 'N' && <div className="w-2 h-2 bg-orange-500 rounded-full border-2 border-slate-900" />}
                              <button 
                                onClick={(e) => deleteNotification(e, n.id)}
                                className="p-1 text-slate-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
                              >
                                <Trash2 size={12} />
                              </button>
                            </div>
                          </div>
                          <h4 className="text-sm font-black text-white mb-1.5 leading-tight group-hover:text-orange-500 transition-colors">{n.titulo}</h4>
                          <p className="text-xs text-slate-400 leading-relaxed line-clamp-2 mb-4 opacity-80">{n.mensagem}</p>
                          
                          {n.link && (
                            <Link 
                              href={n.link} 
                              onClick={() => { markAsRead(n.id); setIsOpen(false); }}
                              className="inline-flex items-center gap-2 text-[10px] font-black text-orange-500 hover:text-white transition-colors uppercase tracking-widest"
                            >
                              Acessar Transmissão <ArrowRight size={12} />
                            </Link>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })
              ) : (
                <div className="p-12 text-center text-slate-600">
                  <Bell size={32} className="mx-auto mb-3 opacity-20" />
                  <p className="text-xs font-bold uppercase tracking-widest">Nenhuma notificação</p>
                </div>
              )}
            </div>
          </div>
        </>
      )}

      {/* TOAST FLUTUANTE (DESIGN CINEMATOGRÁFICO COMPACTO) */}
      {showToast && (
        <div className="fixed top-20 right-6 z-[9999] w-[340px] animate-in slide-in-from-right-full duration-700">
          <div className="relative bg-slate-900 border border-orange-500/30 rounded-[32px] overflow-hidden shadow-[0_20px_80px_-15px_rgba(249,115,22,0.5)] group">
             
             {/* Background Live com Máscara (Igual ao Home Hero) */}
             <div className="absolute inset-0 z-0">
                {liveInfo && getVideoId(liveInfo.url) ? (
                   <img 
                     src={`https://img.youtube.com/vi/${getVideoId(liveInfo.url)}/maxresdefault.jpg`} 
                     className="w-full h-full object-cover opacity-70 group-hover:scale-110 transition-transform duration-1000" 
                     alt="" 
                   />
                ) : (
                   <div className="w-full h-full bg-slate-950" />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/90 to-slate-950/40" />
             </div>

             {/* Conteúdo Compacto */}
             <div className="relative z-10 p-6">
                <div className="flex items-center justify-between mb-4">
                   <div className="flex items-center gap-2">
                      <span className="relative flex h-2 w-2">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                      </span>
                      <span className="text-red-500 text-[9px] font-black uppercase tracking-widest">Ao Vivo Agora</span>
                   </div>
                   <button 
                     onClick={() => setShowToast(null)}
                     className="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-white transition-colors"
                   >
                     <X size={14} />
                   </button>
                </div>

                <h4 className="text-white font-black text-lg mb-2 leading-tight drop-shadow-lg">{showToast.titulo}</h4>
                <p className="text-slate-300 text-xs mb-6 leading-relaxed opacity-80 line-clamp-2">Acompanhe nossa transmissão exclusiva agora mesmo na plataforma Martinez Master.</p>
                
                <div className="flex items-center gap-2">
                   <Link 
                     href={showToast.link || '/live'} 
                     onClick={() => markAsRead(showToast.id)}
                     className="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-3 rounded-2xl font-black text-[11px] text-center transition-all shadow-lg shadow-orange-500/20 uppercase tracking-wider"
                   >
                     ACESSAR TRANSMISSÃO
                   </Link>
                   <button 
                     onClick={() => markAsRead(showToast.id)}
                     className="w-11 h-11 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl flex items-center justify-center text-slate-400 hover:text-white transition-colors"
                   >
                     <Check size={18} />
                   </button>
                </div>
             </div>
          </div>
        </div>
      )}
    </div>
  );
}
