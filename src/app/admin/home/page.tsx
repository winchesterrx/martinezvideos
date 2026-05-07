'use client';

import { useState, useEffect } from 'react';
import { 
  LayoutDashboard, 
  Save, 
  ArrowLeft,
  Loader2,
  Tv,
  Type,
  FileText,
  AlertCircle
} from 'lucide-react';
import Link from 'next/link';

export default function HomePersonalization() {
  const [config, setConfig] = useState<any>({
    home_hero_video_id: '',
    home_hero_titulo: '',
    home_hero_subtitulo: ''
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchConfig();
  }, []);

  const fetchConfig = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/config');
      const data = await res.json();
      if (data.config) {
        setConfig(data.config);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      const res = await fetch('/api/admin/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ config }),
      });

      if (res.ok) {
        alert('Configurações salvas com sucesso!');
      } else {
        alert('Erro ao salvar');
      }
    } catch (e) {
      alert('Erro de conexão');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="p-6 md:p-10 max-w-4xl mx-auto space-y-8">
      {/* Header */}
      <div className="flex items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <Link href="/admin" className="p-2 bg-slate-900 rounded-xl text-slate-400 hover:text-white transition-colors border border-white/5">
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
              <LayoutDashboard className="text-emerald-500" />
              Personalizar Home
            </h1>
            <p className="text-slate-500 mt-1">Configure o visual e o conteúdo de destaque da página inicial.</p>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="py-20 text-center text-slate-500 bg-slate-900/40 rounded-3xl border border-white/5">
          <Loader2 className="animate-spin mx-auto mb-4" size={32} />
          Carregando configurações...
        </div>
      ) : (
        <form onSubmit={handleSave} className="space-y-6">
          
          {/* Hero Section Card */}
          <div className="bg-slate-900/40 border border-white/5 rounded-[40px] p-8 md:p-12 space-y-8 backdrop-blur-sm relative overflow-hidden">
            <div className="absolute top-0 right-0 p-8 opacity-5">
              <Tv size={120} />
            </div>

            <div className="relative z-10">
              <h2 className="text-xl font-bold text-white mb-8 flex items-center gap-3">
                <div className="w-2 h-8 bg-emerald-500 rounded-full" />
                Seção de Destaque (Hero)
              </h2>

              <div className="grid gap-8">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <label className="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                      <Tv size={14} /> ID do Vídeo em Destaque (YouTube)
                    </label>
                    <div className="flex items-center gap-1.5 text-slate-500 text-[10px] font-bold">
                      <AlertCircle size={10} /> Opcional
                    </div>
                  </div>
                  <input 
                    type="text" 
                    value={config.home_hero_video_id || ''}
                    onChange={e => setConfig({...config, home_hero_video_id: e.target.value})}
                    placeholder="Ex: dQw4w9WgXcQ"
                    className="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:border-emerald-500/50 transition-all font-mono"
                  />
                  <p className="text-[10px] text-slate-600 italic">Deixe vazio para usar a imagem de fundo padrão da plataforma.</p>
                </div>

                <div className="space-y-4">
                  <label className="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                    <Type size={14} /> Título Principal
                  </label>
                  <input 
                    type="text" 
                    value={config.home_hero_titulo || ''}
                    onChange={e => setConfig({...config, home_hero_titulo: e.target.value})}
                    className="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:border-emerald-500/50 transition-all font-bold text-lg"
                  />
                </div>

                <div className="space-y-4">
                  <label className="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-2">
                    <FileText size={14} /> Subtítulo / Descrição
                  </label>
                  <textarea 
                    rows={4}
                    value={config.home_hero_subtitulo || ''}
                    onChange={e => setConfig({...config, home_hero_subtitulo: e.target.value})}
                    className="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-6 text-white focus:outline-none focus:border-emerald-500/50 transition-all resize-none leading-relaxed"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Submit Button */}
          <div className="flex justify-end">
            <button 
              type="submit"
              disabled={saving}
              className="flex items-center gap-3 bg-emerald-600 hover:bg-emerald-500 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest transition-all shadow-xl shadow-emerald-600/20 disabled:opacity-50"
            >
              {saving ? <Loader2 className="animate-spin" size={20} /> : <Save size={20} />}
              Salvar Alterações
            </button>
          </div>

        </form>
      )}
    </div>
  );
}
