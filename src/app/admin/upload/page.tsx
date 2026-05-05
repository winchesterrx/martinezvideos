'use client';

import { useState, useEffect } from 'react';
import { Upload, Link as LinkIcon, FileVideo, Save, ArrowLeft, Loader2, Sparkles, Youtube } from 'lucide-react';
import Link from 'next/link';

export default function AdminUploadPage() {
  const [loading, setLoading] = useState(false);
  const [setores, setSetores] = useState<any[]>([]);
  const [modulos, setModulos] = useState<any[]>([]);
  
  const [form, setForm] = useState({
    titulo: '',
    descricao: '',
    tipo_fonte: 'youtube' as 'upload' | 'youtube' | 'drive',
    url: '',
    setor_id: '',
    modulo_id: '',
    is_sequencia: false,
    sequencia_id: '',
    sequencia_ordem: '1'
  });

  const [file, setFile] = useState<File | null>(null);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    const res = await fetch('/api/admin/setup-data'); // Buscar setores/módulos
    const data = await res.json();
    setSetores(data.setores || []);
    setModulos(data.modulos || []);
  };

  const handleUpload = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const formData = new FormData();
      formData.append('titulo', form.titulo);
      formData.append('descricao', form.descricao);
      formData.append('tipo_fonte', form.tipo_fonte);
      formData.append('setor_id', form.setor_id);
      formData.append('modulo_id', form.modulo_id);
      formData.append('is_sequencia', form.is_sequencia ? '1' : '0');
      formData.append('sequencia_id', form.sequencia_id);
      formData.append('sequencia_ordem', form.sequencia_ordem);

      if (form.tipo_fonte === 'upload' && file) {
        formData.append('video_file', file);
      } else {
        formData.append('url', form.url);
      }

      const res = await fetch('/api/admin/videos/upload', {
        method: 'POST',
        body: formData,
      });

      if (res.ok) {
        alert('Vídeo cadastrado com sucesso!');
        window.location.href = '/admin';
      } else {
        const err = await res.json();
        alert('Erro: ' + err.error);
      }
    } catch (e) {
      alert('Erro fatal no upload');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 md:p-10 max-w-4xl mx-auto space-y-8">
      
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link href="/admin" className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-colors">
          <ArrowLeft size={20} />
        </Link>
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            <Upload className="text-orange-500" />
            Cadastrar Novo Vídeo
          </h1>
          <p className="text-slate-500 mt-1">Adicione conteúdos locais, do YouTube ou Google Drive.</p>
        </div>
      </div>

      <form onSubmit={handleUpload} className="space-y-8">
        
        {/* Escolha de Fonte */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button 
            type="button"
            onClick={() => setForm({...form, tipo_fonte: 'youtube'})}
            className={`p-6 rounded-2xl border flex flex-col items-center gap-3 transition-all ${
              form.tipo_fonte === 'youtube' ? 'bg-orange-500/10 border-orange-500 text-orange-500' : 'bg-slate-900/50 border-white/5 text-slate-400 hover:bg-slate-800'
            }`}
          >
            <Youtube size={32} />
            <span className="font-bold text-sm">YouTube Link</span>
          </button>
          
          <button 
            type="button"
            onClick={() => setForm({...form, tipo_fonte: 'drive'})}
            className={`p-6 rounded-2xl border flex flex-col items-center gap-3 transition-all ${
              form.tipo_fonte === 'drive' ? 'bg-indigo-500/10 border-indigo-500 text-indigo-500' : 'bg-slate-900/50 border-white/5 text-slate-400 hover:bg-slate-800'
            }`}
          >
            <LinkIcon size={32} />
            <span className="font-bold text-sm">Google Drive</span>
          </button>

          <button 
            type="button"
            onClick={() => setForm({...form, tipo_fonte: 'upload'})}
            className={`p-6 rounded-2xl border flex flex-col items-center gap-3 transition-all ${
              form.tipo_fonte === 'upload' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-500' : 'bg-slate-900/50 border-white/5 text-slate-400 hover:bg-slate-800'
            }`}
          >
            <FileVideo size={32} />
            <span className="font-bold text-sm">Upload MP4</span>
          </button>
        </div>

        {/* Campos Principais */}
        <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 space-y-6">
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Título da Aula</label>
              <input 
                required
                type="text" 
                value={form.titulo}
                onChange={e => setForm({...form, titulo: e.target.value})}
                className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
                placeholder="Ex: Introdução ao Módulo de Saúde"
              />
            </div>

            <div>
              <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Sistema (Setor)</label>
              <select 
                required
                value={form.setor_id}
                onChange={e => setForm({...form, setor_id: e.target.value})}
                className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
              >
                <option value="">Selecione...</option>
                {setores.map(s => <option key={s.id} value={s.id}>{s.nome}</option>)}
              </select>
            </div>

            <div>
              <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Módulo</label>
              <select 
                required
                value={form.modulo_id}
                onChange={e => setForm({...form, modulo_id: e.target.value})}
                className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
              >
                <option value="">Selecione...</option>
                {modulos.filter(m => m.setor_id == form.setor_id).map(m => <option key={m.id} value={m.id}>{m.nome}</option>)}
              </select>
            </div>
          </div>

          <div>
            <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Descrição</label>
            <textarea 
              rows={3}
              value={form.descricao}
              onChange={e => setForm({...form, descricao: e.target.value})}
              className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
              placeholder="Descreva o conteúdo desta aula..."
            />
          </div>

          {/* Campo de Mídia Dinâmico */}
          <div className="pt-6 border-t border-white/5">
            {form.tipo_fonte === 'upload' ? (
              <div className="space-y-4">
                <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Arquivo de Vídeo (.mp4)</label>
                <input 
                  type="file" 
                  accept="video/mp4"
                  onChange={e => setFile(e.target.files?.[0] || null)}
                  className="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-500/10 file:text-orange-500 hover:file:bg-orange-500/20 cursor-pointer"
                />
              </div>
            ) : (
              <div className="space-y-4">
                <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">
                  Link do {form.tipo_fonte === 'youtube' ? 'YouTube' : 'Google Drive'}
                </label>
                <input 
                  required
                  type="text" 
                  value={form.url}
                  onChange={e => setForm({...form, url: e.target.value})}
                  className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
                  placeholder={form.tipo_fonte === 'youtube' ? 'https://www.youtube.com/watch?v=...' : 'https://drive.google.com/file/d/...'}
                />
              </div>
            )}
          </div>
        </div>

        {/* Configurações de Sequência */}
        <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 space-y-6">
           <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Sparkles className="text-orange-500 w-5 h-5" />
                <h3 className="text-lg font-bold text-white">Configurar como Sequência (Trilha)</h3>
              </div>
              <input 
                type="checkbox" 
                checked={form.is_sequencia}
                onChange={e => setForm({...form, is_sequencia: e.target.checked})}
                className="w-6 h-6 rounded bg-slate-950 border-white/10 text-orange-500 focus:ring-orange-500"
              />
           </div>

           {form.is_sequencia && (
             <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-white/5 animate-in fade-in slide-in-from-top-2">
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">ID da Sequência (Agrupador)</label>
                  <input 
                    type="number" 
                    value={form.sequencia_id}
                    onChange={e => setForm({...form, sequencia_id: e.target.value})}
                    className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
                    placeholder="Ex: 101"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Ordem na Trilha</label>
                  <input 
                    type="number" 
                    value={form.sequencia_ordem}
                    onChange={e => setForm({...form, sequencia_ordem: e.target.value})}
                    className="w-full bg-slate-950 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none"
                    placeholder="Ex: 1"
                  />
                </div>
             </div>
           )}
        </div>

        <button 
          type="submit"
          disabled={loading}
          className="w-full flex items-center justify-center gap-3 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-bold py-5 rounded-2xl shadow-lg shadow-orange-500/20 transition-all text-lg"
        >
          {loading ? (
            <>
              <Loader2 className="animate-spin" />
              PROCESSANDO...
            </>
          ) : (
            <>
              <Save size={24} />
              FINALIZAR E PUBLICAR
            </>
          )}
        </button>

      </form>
    </div>
  );
}
