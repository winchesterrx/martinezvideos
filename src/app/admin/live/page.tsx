'use client';

import { useState, useEffect } from 'react';
import { Radio, Play, Square, Save, MessageSquare, Globe, ArrowLeft, ExternalLink } from 'lucide-react';
import Link from 'next/link';

export default function AdminLiveStudio() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  
  const [form, setForm] = useState({
    titulo: '',
    url: '',
    ativo: false,
    descricao: ''
  });

  useEffect(() => {
    fetchLiveStatus();
  }, []);

  const fetchLiveStatus = async () => {
    try {
      const res = await fetch('/api/admin/live/data'); // Vou criar essa rota
      const data = await res.json();
      if (data.live) {
        setForm({
          titulo: data.live.titulo || '',
          url: data.live.url || '',
          ativo: data.live.ativo === 1,
          descricao: data.live.descricao || ''
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
        alert(newStatus ? 'Live Iniciada com Sucesso!' : 'Live Encerrada!');
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

  if (loading) return <div className="p-10 text-white">Carregando Estúdio...</div>;

  return (
    <div className="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
      
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link href="/admin" className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-colors">
          <ArrowLeft size={20} />
        </Link>
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            <Radio className="text-red-500" />
            Estúdio de Transmissão
          </h1>
          <p className="text-slate-500 mt-1">Configure sua live do YouTube e ative para todos os alunos.</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Configurações */}
        <div className="lg:col-span-1 space-y-6">
          <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 space-y-6">
            <h2 className="text-xl font-bold text-white flex items-center gap-2">
              <Settings size={20} className="text-slate-400" />
              Configurações
            </h2>

            <div className="space-y-4">
              <div>
                <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Título da Live</label>
                <input 
                  type="text" 
                  value={form.titulo}
                  onChange={e => setForm({...form, titulo: e.target.value})}
                  className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 transition-all"
                  placeholder="Ex: Treinamento Martinez - Aula 01"
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Link do YouTube</label>
                <input 
                  type="text" 
                  value={form.url}
                  onChange={e => setForm({...form, url: e.target.value})}
                  className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 transition-all"
                  placeholder="https://www.youtube.com/watch?v=..."
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Descrição (Opcional)</label>
                <textarea 
                  rows={3}
                  value={form.descricao}
                  onChange={e => setForm({...form, descricao: e.target.value})}
                  className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 transition-all"
                  placeholder="Diga aos alunos sobre o que é a live..."
                />
              </div>
            </div>

            <div className="pt-6 border-t border-white/5 space-y-3">
              {!form.ativo ? (
                <button 
                  onClick={() => handleSave(true)}
                  disabled={saving || !form.url || !form.titulo}
                  className="w-full flex items-center justify-center gap-3 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-600/20 transition-all"
                >
                  <Play size={20} />
                  INICIAR TRANSMISSÃO
                </button>
              ) : (
                <button 
                  onClick={() => handleSave(false)}
                  disabled={saving}
                  className="w-full flex items-center justify-center gap-3 bg-slate-800 hover:bg-slate-700 text-white font-bold py-4 rounded-2xl border border-white/10 transition-all"
                >
                  <Square size={20} />
                  ENCERRAR LIVE
                </button>
              )}
              
              <button 
                onClick={() => handleSave()}
                disabled={saving}
                className="w-full flex items-center justify-center gap-3 bg-white/5 hover:bg-white/10 text-slate-300 font-medium py-3 rounded-xl transition-all"
              >
                <Save size={18} />
                Salvar Alterações
              </button>
            </div>
          </div>
        </div>

        {/* Preview & Chat */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-slate-900 border border-white/5 rounded-3xl overflow-hidden flex flex-col h-[700px]">
            <div className="p-6 border-b border-white/5 flex items-center justify-between bg-slate-950/50">
              <h2 className="text-white font-bold flex items-center gap-2">
                <Globe size={18} className="text-indigo-400" />
                Pré-visualização e Chat
              </h2>
              {videoId && (
                <a href={form.url} target="_blank" className="text-xs text-slate-400 hover:text-white flex items-center gap-1">
                  Ver no YouTube <ExternalLink size={12} />
                </a>
              )}
            </div>
            
            <div className="flex-1 flex flex-col md:flex-row">
              {/* Vídeo */}
              <div className="flex-[2] bg-black relative">
                {videoId ? (
                  <iframe 
                    className="w-full h-full"
                    src={`https://www.youtube.com/embed/${videoId}?autoplay=0&rel=0`}
                    title="YouTube video player" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowFullScreen
                  ></iframe>
                ) : (
                  <div className="w-full h-full flex flex-col items-center justify-center text-slate-600 p-8 text-center">
                    <Play size={48} className="mb-4 opacity-20" />
                    <p>Insira um link do YouTube para ver a prévia.</p>
                  </div>
                )}
              </div>
              
              {/* Chat */}
              <div className="flex-1 border-l border-white/5 bg-slate-950">
                {videoId ? (
                  <iframe 
                    className="w-full h-full"
                    src={`https://www.youtube.com/live_chat?v=${videoId}&embed_domain=${domain}`}
                    title="YouTube Live Chat"
                  ></iframe>
                ) : (
                  <div className="w-full h-full flex flex-col items-center justify-center p-6 text-center text-slate-700">
                    <MessageSquare size={32} className="mb-2 opacity-20" />
                    <p className="text-xs">O chat aparecerá aqui quando a live estiver configurada.</p>
                  </div>
                )}
              </div>
            </div>
          </div>
          <p className="text-xs text-slate-600 italic px-4">
            * Nota: O chat do YouTube exige que o domínio (ex: {domain}) esteja configurado nas permissões da live. Em localhost pode não aparecer por segurança do Google.
          </p>
        </div>

      </div>
    </div>
  );
}

function Settings(props: any) {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
  );
}
