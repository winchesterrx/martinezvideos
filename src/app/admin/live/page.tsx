'use client';

import { useState, useEffect } from 'react';
import { Radio, Play, Square, Save, MessageSquare, Globe, ArrowLeft, ExternalLink, Users, Eye, ThumbsUp, Share2, Info, MapPin, Calendar, Smartphone, User, X, Bell, Trash2 } from 'lucide-react';
import Link from 'next/link';

export default function AdminLiveStudio() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [selectedLive, setSelectedLive] = useState<any>(null);
  const [liveDetails, setLiveDetails] = useState<any>(null);
  const [loadingDetails, setLoadingDetails] = useState(false);
  
  const [form, setForm] = useState({
    titulo: '',
    url: '',
    ativo: false,
    descricao: '',
    subtexto: '',
    setor_id: '',
    modulo_id: ''
  });

  const [history, setHistory] = useState<any[]>([]);
  const [setores, setSetores] = useState<any[]>([]);
  const [modulos, setModulos] = useState<any[]>([]);

  useEffect(() => {
    fetchLiveStatus();
    fetchHistory();
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const sRes = await fetch('/api/admin/sectors');
      const sData = await sRes.json();
      setSetores(sData.sectors || []);

      const mRes = await fetch('/api/admin/modules');
      const mData = await mRes.json();
      setModulos(mData.modules || []);
    } catch (e) {
      console.error(e);
    }
  };

  const fetchHistory = async () => {
    try {
      const res = await fetch('/api/admin/live/history');
      const data = await res.json();
      setHistory(data.history || []);
    } catch (e) {
      console.error(e);
    }
  };

  const handleDeleteLive = async (e: React.MouseEvent, id: number) => {
    e.stopPropagation();
    if (!confirm('Deseja realmente remover esta transmissão do histórico?')) return;
    
    try {
      const res = await fetch(`/api/admin/live/delete?id=${id}`, { method: 'DELETE' });
      if (res.ok) {
        setHistory(prev => prev.filter(item => item.id !== id));
      }
    } catch (err) {
      console.error('Erro ao deletar:', err);
    }
  };

  const fetchLiveDetails = async (videoId: string) => {
    setLoadingDetails(true);
    try {
      const res = await fetch(`/api/admin/live/details?videoId=${videoId}`);
      const data = await res.json();
      setLiveDetails(data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoadingDetails(false);
    }
  };

  const fetchLiveStatus = async () => {
    try {
      const res = await fetch('/api/admin/live/data');
      const data = await res.json();
      if (data.live) {
        setForm({
          titulo: data.live.titulo || '',
          url: data.live.url || '',
          ativo: data.live.ativo === 1,
          descricao: data.live.descricao || '',
          subtexto: data.live.subtexto || '',
          setor_id: data.live.setor_id || '',
          modulo_id: data.live.modulo_id || ''
        });
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (isActivating?: boolean) => {
    setSaving(true);
    const newStatus = isActivating !== undefined ? isActivating : form.ativo;
    
    try {
      const res = await fetch('/api/admin/live', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...form, ativo: newStatus }),
      });
      if (res.ok) {
        setForm(prev => ({ ...prev, ativo: newStatus }));
        fetchHistory();
        
        if (isActivating === true) {
          alert('Live Iniciada com Sucesso!');
        } else if (isActivating === false) {
          alert('Live Encerrada!');
        } else {
          alert('Alterações salvas com sucesso!');
        }
      }
    } catch (e) {
      alert('Erro ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const getVideoId = (url: string) => {
    if (!url) return null;
    const match = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    return match ? match[1] : null;
  };

  const videoId = getVideoId(form.url);
  const domain = typeof window !== 'undefined' ? window.location.hostname : 'localhost';

  if (loading) return <div className="p-10 text-white animate-pulse">Carregando Inteligência Martinez...</div>;

  return (
    <div className="p-6 md:p-10 max-w-7xl mx-auto space-y-12">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <Link href="/admin" className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-colors">
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-3xl font-black text-white flex items-center gap-3">
              <Radio className={form.ativo ? "text-red-500 animate-pulse" : "text-slate-700"} />
              Central de Inteligência
            </h1>
            <p className="text-slate-500 font-medium">Controle total e análise geográfica em tempo real.</p>
          </div>
        </div>
        
        {form.ativo && (
          <div className="bg-red-500/10 border border-red-500/20 px-4 py-2 rounded-full flex items-center gap-3">
            <div className="w-2 h-2 bg-red-500 rounded-full animate-ping" />
            <span className="text-red-500 text-xs font-black uppercase tracking-tighter">Sinal Online e Monitorado</span>
          </div>
        )}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Configurações */}
        <div className="lg:col-span-1 space-y-6">
          <div className="bg-slate-900/40 backdrop-blur-xl border border-white/5 rounded-[32px] p-8 space-y-6 shadow-2xl">
            <h2 className="text-lg font-black text-white uppercase tracking-widest flex items-center gap-2 opacity-50">
              Setup Inicial
            </h2>

            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Setor Relacionado</label>
                  <select 
                    value={form.setor_id}
                    onChange={e => setForm({...form, setor_id: e.target.value})}
                    className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none text-sm appearance-none"
                  >
                    <option value="">Nenhum Setor</option>
                    {setores.map(s => <option key={s.id} value={s.id}>{s.nome}</option>)}
                  </select>
                </div>
                <div>
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Módulo Relacionado</label>
                  <select 
                    value={form.modulo_id}
                    onChange={e => setForm({...form, modulo_id: e.target.value})}
                    className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none text-sm appearance-none"
                  >
                    <option value="">Nenhum Módulo</option>
                    {modulos.map(m => <option key={m.id} value={m.id}>{m.nome}</option>)}
                  </select>
                </div>
              </div>

              <div className="group">
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block group-focus-within:text-orange-500 transition-colors">Título da Aula</label>
                <input 
                  type="text" 
                  value={form.titulo}
                  onChange={e => setForm({...form, titulo: e.target.value})}
                  className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none"
                  placeholder="Nome da aula..."
                />
              </div>

              <div>
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Link do YouTube</label>
                <input 
                  type="text" 
                  value={form.url}
                  onChange={e => setForm({...form, url: e.target.value})}
                  className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none"
                  placeholder="Link completo..."
                />
              </div>

              <div>
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Mensagem de Impacto (Descrição)</label>
                <textarea 
                  rows={2}
                  value={form.descricao}
                  onChange={e => setForm({...form, descricao: e.target.value})}
                  className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none text-sm"
                />
              </div>

              <div>
                <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Alerta de Suporte (Subtexto)</label>
                <input 
                  type="text"
                  value={form.subtexto}
                  onChange={e => setForm({...form, subtexto: e.target.value})}
                  className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-4 py-4 text-white focus:border-orange-500 transition-all outline-none text-sm"
                  placeholder="Ex: Em caso de dúvidas, ligue para..."
                />
              </div>
            </div>

            <div className="pt-6 border-t border-white/5 space-y-3">
              {!form.ativo ? (
                <button 
                  onClick={() => handleSave(true)}
                  disabled={saving || !form.url || !form.titulo}
                  className="w-full flex items-center justify-center gap-3 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white font-black py-5 rounded-2xl transition-all shadow-xl shadow-red-600/20"
                >
                  <Play size={20} /> ATIVAR TRANSMISSÃO
                </button>
              ) : (
                <>
                  <button 
                    onClick={() => handleSave()}
                    disabled={saving}
                    className="w-full flex items-center justify-center gap-3 bg-orange-500 hover:bg-orange-400 text-white font-black py-5 rounded-2xl transition-all shadow-xl shadow-orange-500/20"
                  >
                    <Save size={20} /> SALVAR ALTERAÇÕES
                  </button>
                  
                  <button 
                    onClick={() => handleSave(false)}
                    disabled={saving}
                    className="w-full flex items-center justify-center gap-3 bg-slate-800/50 hover:bg-red-600/20 hover:text-red-500 text-slate-400 font-black py-4 rounded-2xl border border-white/5 transition-all"
                  >
                    <Square size={18} /> ENCERRAR SINAL
                  </button>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Live Preview Monitor */}
        <div className="lg:col-span-2">
           <div className="bg-slate-900 border border-white/5 rounded-[32px] overflow-hidden shadow-2xl h-full flex flex-col">
              <div className="p-6 bg-slate-950/50 border-b border-white/5 flex items-center justify-between">
                <span className="flex items-center gap-2 text-xs font-black text-white uppercase tracking-widest">
                  <Eye size={16} className="text-orange-500" /> Monitor de Transmissão
                </span>
                {videoId && (
                  <Link href="/live" target="_blank" className="text-[10px] font-black text-slate-400 hover:text-white uppercase flex items-center gap-1.5 transition-colors">
                    Ver Página do Aluno <ExternalLink size={12} />
                  </Link>
                )}
              </div>
              <div className="flex-1 bg-slate-950 relative group">
                {videoId ? (
                  <iframe className="w-full h-full grayscale-[0.2] group-hover:grayscale-0 transition-all duration-700" src={`https://www.youtube.com/embed/${videoId}?modestbranding=1&rel=0`} />
                ) : (
                  <div className="w-full h-full flex flex-col items-center justify-center text-slate-700 gap-4">
                    <Radio size={48} className="opacity-20 animate-pulse" />
                    <p className="font-black text-xs uppercase tracking-tighter">Aguardando sinal de vídeo...</p>
                  </div>
                )}
              </div>
           </div>
        </div>
      </div>

      {/* Cards de Histórico e Inteligência */}
      <div className="space-y-6">
        <h2 className="text-xl font-black text-white uppercase tracking-widest flex items-center gap-3">
          <Calendar size={24} className="text-orange-500" /> Histórico de Performance
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {history.map((item) => (
            <div 
              key={item.id} 
              onClick={() => { setSelectedLive(item); fetchLiveDetails(getVideoId(item.url) || ''); }}
              className="group relative bg-slate-900 border border-white/5 rounded-[40px] p-8 hover:border-orange-500/50 transition-all cursor-pointer overflow-hidden"
            >
              {/* Background do YouTube com Máscara */}
              {getVideoId(item.url) && (
                <div 
                  className="absolute inset-0 opacity-20 group-hover:opacity-40 transition-opacity duration-500"
                  style={{
                    backgroundImage: `url(https://img.youtube.com/vi/${getVideoId(item.url)}/maxresdefault.jpg)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center'
                  }}
                >
                  <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent" />
                </div>
              )}

              <div className="relative z-10">
                <div className="flex items-start justify-between mb-6">
                  <div className="flex items-center gap-3">
                    <span className={`px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${item.ativo === 1 ? 'bg-green-500/20 text-green-500' : 'bg-slate-800 text-slate-500'}`}>
                      {item.ativo === 1 ? 'Ao Vivo' : 'Finalizado'}
                    </span>
                    <span className="text-[10px] font-bold text-slate-500 uppercase">{new Date(item.created_at).toLocaleDateString()}</span>
                  </div>
                  
                  {/* Botão Apagar */}
                  <button 
                    onClick={(e) => handleDeleteLive(e, item.id)}
                    className="w-8 h-8 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>

                <h3 className="text-xl font-black text-white mb-8 line-clamp-2 leading-tight group-hover:text-orange-500 transition-colors">
                  {item.titulo}
                </h3>

                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-slate-950/50 border border-white/5 p-4 rounded-2xl">
                     <div className="flex items-center gap-2 mb-1 text-orange-500">
                        <Eye size={12} />
                        <span className="text-[10px] font-black uppercase">Views</span>
                     </div>
                     <span className="text-xl font-black text-white">{item.views || 0}</span>
                  </div>
                  <div className="bg-slate-950/50 border border-white/5 p-4 rounded-2xl">
                     <div className="flex items-center gap-2 mb-1 text-indigo-400">
                        <ThumbsUp size={12} />
                        <span className="text-[10px] font-black uppercase">Likes</span>
                     </div>
                     <span className="text-xl font-black text-white">{item.likes || 0}</span>
                  </div>
                </div>

                <div className="mt-6 flex items-center justify-between text-slate-500 group-hover:text-white transition-colors">
                   <span className="text-[10px] font-black uppercase tracking-widest">Clique para ver detalhes</span>
                   <ArrowLeft size={16} className="rotate-180" />
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* MODAL DE DETALHES DA LIVE - REFORMULADO V3 */}
      {selectedLive && (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center p-6 md:p-12 md:pl-[300px] bg-slate-950/98 backdrop-blur-3xl overflow-hidden">
          <div className="bg-slate-900 border border-orange-500/30 w-full max-w-[94%] h-[88vh] rounded-[48px] shadow-[0_0_60px_-15px_rgba(249,115,22,0.3)] flex flex-col overflow-hidden animate-in zoom-in-95 fade-in duration-500">
            
            {/* Modal Header Premium */}
            <div className="p-8 md:p-12 border-b border-white/5 bg-gradient-to-r from-slate-950 to-slate-900 flex items-center justify-between">
              <div className="flex items-center gap-6">
                <div className="w-16 h-16 rounded-3xl bg-orange-500/10 flex items-center justify-center text-orange-500 border border-orange-500/20 shadow-lg shadow-orange-500/10">
                  <Radio size={32} className="animate-pulse" />
                </div>
                <div>
                  <div className="flex items-center gap-3 mb-1">
                    <span className="text-[10px] font-black text-orange-500 uppercase tracking-[0.3em]">Auditoria em Tempo Real</span>
                    <span className="w-1 h-1 bg-slate-700 rounded-full" />
                    <span className="text-[10px] font-bold text-slate-500 uppercase">{new Date(selectedLive.created_at).toLocaleDateString()}</span>
                  </div>
                  <h2 className="text-2xl md:text-4xl font-black text-white leading-tight tracking-tighter">{selectedLive.titulo}</h2>
                </div>
              </div>
              <button 
                onClick={() => setSelectedLive(null)} 
                className="w-14 h-14 rounded-2xl bg-white/5 hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center text-white transition-all group"
              >
                <X size={28} className="group-hover:rotate-90 transition-transform duration-300" />
              </button>
            </div>
            
            {/* Modal Body - Grid de Inteligência */}
            <div className="flex-1 overflow-y-auto p-8 md:p-12 custom-scrollbar bg-slate-900/50">
              {loadingDetails ? (
                <div className="h-full flex flex-col items-center justify-center gap-6">
                   <div className="relative">
                      <div className="w-20 h-20 border-4 border-orange-500/10 rounded-full" />
                      <div className="w-20 h-20 border-4 border-orange-500 border-t-transparent rounded-full animate-spin absolute inset-0" />
                   </div>
                   <span className="font-black text-sm text-slate-400 uppercase tracking-[0.4em] animate-pulse">Sincronizando Cérebro Martinez...</span>
                </div>
              ) : (
                <div className="space-y-12">
                   {/* Resumo Rápido */}
                   <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                      {[
                        { label: 'Espectadores', value: liveDetails?.views?.length || 0, icon: Eye, color: 'text-orange-500', bg: 'bg-orange-500/5', borderColor: 'border-orange-500/20' },
                        { label: 'Curtidas', value: liveDetails?.likes?.length || 0, icon: ThumbsUp, color: 'text-indigo-400', bg: 'bg-indigo-400/5', borderColor: 'border-indigo-400/20' },
                        { label: 'Compartilharam', value: liveDetails?.shares?.length || 0, icon: Share2, color: 'text-sky-400', bg: 'bg-sky-400/5', borderColor: 'border-sky-400/20' },
                        { label: 'Notificações', value: liveDetails?.notifications?.length || 0, icon: Bell, color: 'text-emerald-400', bg: 'bg-emerald-400/5', borderColor: 'border-emerald-400/20' },
                      ].map((stat, i) => (
                        <div key={i} className={`${stat.bg} ${stat.borderColor} border p-6 rounded-[32px] flex items-center gap-4 shadow-inner`}>
                           <stat.icon className={stat.color} size={24} />
                           <div>
                              <p className="text-[10px] font-black text-slate-500 uppercase tracking-widest">{stat.label}</p>
                              <p className="text-2xl font-black text-white">{stat.value}</p>
                           </div>
                        </div>
                      ))}
                   </div>

                   {/* Tabelas de Nomes Reais */}
                   <div className="grid grid-cols-1 xl:grid-cols-2 gap-10">
                      
                      {/* QUEM ASSISTIU (NOMES) */}
                      <div className="bg-slate-950/40 rounded-[40px] border border-white/10 overflow-hidden flex flex-col shadow-2xl">
                         <div className="p-6 bg-white/[0.02] border-b border-white/10 flex items-center justify-between">
                            <h3 className="text-xs font-black text-white uppercase tracking-widest flex items-center gap-3">
                               <div className="w-8 h-8 rounded-xl bg-orange-500/20 flex items-center justify-center text-orange-500">
                                  <Users size={16} />
                               </div>
                               Lista de Espectadores
                            </h3>
                            <button onClick={() => fetchLiveDetails(getVideoId(selectedLive.url) || '')} className="text-[10px] font-black text-slate-500 hover:text-white uppercase transition-colors">Atualizar</button>
                         </div>
                         <div className="max-h-[400px] overflow-y-auto custom-scrollbar">
                            <table className="w-full text-left text-xs">
                               <thead className="sticky top-0 bg-slate-900 border-b border-white/5">
                                  <tr className="text-slate-500">
                                     <th className="p-5 font-black uppercase tracking-tighter">Nome do Aluno</th>
                                     <th className="p-5 font-black uppercase tracking-tighter">Origem</th>
                                     <th className="p-5 text-right font-black uppercase tracking-tighter">Entrada</th>
                                  </tr>
                               </thead>
                               <tbody className="divide-y divide-white/5">
                                  {liveDetails?.views?.length > 0 ? (
                                    liveDetails.views.map((v: any) => (
                                      <tr key={v.id} className="hover:bg-white/[0.02] transition-all group">
                                         <td className="p-5">
                                            <div className="flex items-center gap-3">
                                               <div className="w-10 h-10 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-slate-400 group-hover:border-orange-500/50 transition-colors">
                                                  <User size={18} />
                                               </div>
                                               <div>
                                                  <p className="font-black text-white text-sm">{v.user_name || 'Visitante'}</p>
                                                  <p className="text-[10px] text-slate-600 font-mono">{v.ip_address}</p>
                                               </div>
                                            </div>
                                         </td>
                                         <td className="p-5">
                                            <div className="flex items-center gap-2 text-slate-400">
                                               <MapPin size={12} className="text-orange-500/50" /> {v.localizacao}
                                            </div>
                                         </td>
                                         <td className="p-5 text-right text-slate-500 tabular-nums">{new Date(v.created_at).toLocaleTimeString()}</td>
                                      </tr>
                                    ))
                                  ) : (
                                    <tr><td colSpan={3} className="p-20 text-center text-slate-700 font-black uppercase text-xs tracking-widest">Nenhum rastro detectado</td></tr>
                                  )}
                               </tbody>
                            </table>
                         </div>
                      </div>

                      {/* QUEM CURTIU (NOMES) */}
                      <div className="bg-slate-950/40 rounded-[40px] border border-white/10 overflow-hidden flex flex-col shadow-2xl">
                         <div className="p-6 bg-white/[0.02] border-b border-white/10 flex items-center justify-between">
                            <h3 className="text-xs font-black text-white uppercase tracking-widest flex items-center gap-3">
                               <div className="w-8 h-8 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                                  <ThumbsUp size={16} />
                               </div>
                               Registro de Curtidas
                            </h3>
                         </div>
                         <div className="max-h-[400px] overflow-y-auto custom-scrollbar">
                            <table className="w-full text-left text-xs">
                               <tbody className="divide-y divide-white/5">
                                  {liveDetails?.likes?.length > 0 ? (
                                    liveDetails.likes.map((l: any) => (
                                      <tr key={l.id} className="hover:bg-white/[0.02] transition-all">
                                         <td className="p-5">
                                            <div className="flex items-center gap-3">
                                               <div className="w-10 h-10 rounded-full bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                                                  <User size={18} />
                                               </div>
                                               <span className="font-black text-white text-sm">{l.user_name || 'Visitante'}</span>
                                            </div>
                                         </td>
                                         <td className="p-5 text-slate-400 text-[10px]">{l.localizacao}</td>
                                         <td className="p-5 text-right text-slate-500 tabular-nums">{new Date(l.created_at).toLocaleTimeString()}</td>
                                      </tr>
                                    ))
                                  ) : (
                                    <tr><td className="p-20 text-center text-slate-700 font-black uppercase text-xs tracking-widest">Aguardando corações...</td></tr>
                                  )}
                               </tbody>
                            </table>
                         </div>
                      </div>

                      {/* QUEM COMPARTILHOU (NOMES) */}
                      <div className="bg-slate-950/40 rounded-[40px] border border-white/5 overflow-hidden flex flex-col shadow-2xl">
                         <div className="p-6 bg-white/[0.02] border-b border-white/5">
                            <h3 className="text-xs font-black text-white uppercase tracking-widest flex items-center gap-3">
                               <div className="w-8 h-8 rounded-xl bg-sky-500/20 flex items-center justify-center text-sky-400">
                                  <Share2 size={16} />
                               </div>
                               Cliques em Compartilhar
                            </h3>
                         </div>
                         <div className="max-h-[400px] overflow-y-auto custom-scrollbar">
                            <table className="w-full text-left text-xs">
                               <tbody className="divide-y divide-white/5">
                                  {liveDetails?.shares?.length > 0 ? (
                                    liveDetails.shares.map((s: any) => (
                                      <tr key={s.id} className="hover:bg-white/[0.02] transition-all">
                                         <td className="p-5 flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                                               <User size={18} />
                                            </div>
                                            <span className="font-black text-white text-sm">{s.user_name || 'Visitante'}</span>
                                         </td>
                                         <td className="p-5 text-right text-slate-500 tabular-nums">{new Date(s.created_at).toLocaleTimeString()}</td>
                                      </tr>
                                    ))
                                  ) : (
                                    <tr><td className="p-20 text-center text-slate-700 font-black uppercase text-xs tracking-widest">Ninguém compartilhou ainda</td></tr>
                                  )}
                               </tbody>
                            </table>
                         </div>
                      </div>

                      {/* QUEM ATIVOU SINO (NOMES) */}
                      <div className="bg-slate-950/40 rounded-[40px] border border-white/5 overflow-hidden flex flex-col shadow-2xl">
                         <div className="p-6 bg-white/[0.02] border-b border-white/5">
                            <h3 className="text-xs font-black text-white uppercase tracking-widest flex items-center gap-3">
                               <div className="w-8 h-8 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                                  <Bell size={16} />
                               </div>
                               Ativações de Notificação
                            </h3>
                         </div>
                         <div className="max-h-[400px] overflow-y-auto custom-scrollbar">
                            <table className="w-full text-left text-xs">
                               <tbody className="divide-y divide-white/5">
                                  {liveDetails?.notifications?.length > 0 ? (
                                    liveDetails.notifications.map((n: any) => (
                                      <tr key={n.id} className="hover:bg-white/[0.02] transition-all">
                                         <td className="p-5 flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                               <User size={18} />
                                            </div>
                                            <span className="font-black text-white text-sm">{n.user_name || 'Visitante'}</span>
                                         </td>
                                         <td className="p-5 text-right text-slate-500 tabular-nums">{new Date(n.created_at).toLocaleTimeString()}</td>
                                      </tr>
                                    ))
                                  ) : (
                                    <tr><td className="p-20 text-center text-slate-700 font-black uppercase text-xs tracking-widest">Nenhum aviso solicitado</td></tr>
                                  )}
                               </tbody>
                            </table>
                         </div>
                      </div>

                   </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
