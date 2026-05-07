'use client';

import { useState, useEffect } from 'react';
import { Upload, Link as LinkIcon, FileVideo, Save, ArrowLeft, Loader2, Sparkles, Video, Plus } from 'lucide-react';
import Link from 'next/link';

export default function AdminUploadPage() {
  const [loading, setLoading] = useState(false);
  const [setores, setSetores] = useState<any[]>([]);
  const [modulos, setModulos] = useState<any[]>([]);
  const [trilhas, setTrilhas] = useState<any[]>([]);
  
  const [form, setForm] = useState({
    titulo: '',
    descricao: '',
    tipo_fonte: 'youtube' as 'upload' | 'youtube' | 'drive',
    url: '',
    setor_id: '',
    modulo_id: '',
    is_sequencia: false,
    sequencia_id: '',
    nova_trilha_nome: '',
    sequencia_ordem: '1'
  });

  const [file, setFile] = useState<File | null>(null);
  const [thumbFile, setThumbFile] = useState<File | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      const res = await fetch('/api/admin/setup-data');
      const data = await res.json();
      setSetores(data.setores || []);
      setModulos(data.modulos || []);
      setTrilhas(data.trilhas || []);
    } catch (e) {
      console.error("Erro ao carregar dados:", e);
    }
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
      formData.append('nova_trilha_nome', form.nova_trilha_nome);
      formData.append('sequencia_ordem', form.sequencia_ordem);

      if (form.tipo_fonte === 'upload') {
        if (!file) throw new Error("Selecione um arquivo MP4");
        formData.append('video_file', file);
      } else {
        if (!form.url) throw new Error("Informe a URL do vídeo");
        formData.append('url', form.url);
      }

      if (thumbFile) {
        formData.append('thumbnail_file', thumbFile);
      }

      setUploadProgress(0);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/api/admin/videos/upload');

      xhr.upload.onprogress = (event) => {
        if (event.lengthComputable) {
          const percentComplete = Math.round((event.loaded / event.total) * 100);
          setUploadProgress(percentComplete);
        }
      };

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          alert('Vídeo cadastrado com sucesso!');
          setForm({
            titulo: '',
            descricao: '',
            tipo_fonte: 'youtube',
            url: '',
            setor_id: '',
            modulo_id: '',
            is_sequencia: false,
            sequencia_id: '',
            nova_trilha_nome: '',
            sequencia_ordem: '1'
          });
          setFile(null);
          setThumbFile(null);
          setUploadProgress(0);
        } else {
          alert('Erro ao realizar upload: ' + xhr.responseText);
        }
        setLoading(false);
      };

      xhr.onerror = () => {
        alert('Erro de conexão ao realizar upload.');
        setLoading(false);
      };

      xhr.send(formData);
    } catch (error) {
      console.error(error);
      alert('Erro ao processar formulário');
      setLoading(false);
    }
  };

  return (
    <div className="p-6 md:p-12 max-w-5xl mx-auto space-y-12">
      
      {/* Header Minimalista */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-6">
          <Link href="/admin" className="w-12 h-12 rounded-2xl bg-white/[0.03] border border-white/10 flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-3xl font-medium text-white tracking-tight">Novo Conteúdo</h1>
            <p className="text-slate-500 text-sm mt-1">Expanda a biblioteca de inteligência Martinez.</p>
          </div>
        </div>
      </div>

      <form onSubmit={handleUpload} className="grid grid-cols-1 lg:grid-cols-3 gap-12">
        
        {/* Coluna de Configuração */}
        <div className="lg:col-span-2 space-y-10">
          
          {/* Escolha de Fonte Minimalista */}
          <div className="space-y-4">
            <label className="text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] ml-2">Fonte do Conteúdo</label>
            <div className="grid grid-cols-3 gap-3">
              {[
                { id: 'youtube', label: 'YouTube', icon: Video },
                { id: 'drive', label: 'Drive', icon: LinkIcon },
                { id: 'upload', label: 'Local', icon: FileVideo },
              ].map((item) => (
                <button 
                  key={item.id}
                  type="button"
                  onClick={() => setForm(prev => ({...prev, tipo_fonte: item.id as any}))}
                  className={`flex flex-col items-center gap-3 p-6 rounded-2xl border transition-all ${
                    form.tipo_fonte === item.id 
                    ? 'bg-white/[0.05] border-orange-500/50 text-orange-500 shadow-lg' 
                    : 'bg-transparent border-white/5 text-slate-500 hover:border-white/10'
                  }`}
                >
                  <item.icon size={24} />
                  <span className="text-[10px] font-bold uppercase tracking-widest">{item.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Dados Principais */}
          <div className="space-y-8 bg-white/[0.02] border border-white/5 p-10 rounded-[32px]">
            <div className="space-y-6">
               <div className="space-y-2">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Título da Aula</label>
                  <input 
                    required
                    type="text" 
                    value={form.titulo ?? ''}
                    onChange={e => setForm(prev => ({...prev, titulo: e.target.value}))}
                    className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-orange-500 outline-none transition-all"
                    placeholder="Defina um título claro e direto..."
                  />
               </div>

               <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Setor (Sistema)</label>
                    <select 
                      required
                      value={form.setor_id ?? ''}
                      onChange={e => setForm(prev => ({...prev, setor_id: e.target.value, modulo_id: ''}))}
                      className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-orange-500 outline-none appearance-none"
                    >
                      <option value="">Selecionar...</option>
                      {setores.map(s => <option key={s.id} value={s.id}>{s.nome}</option>)}
                    </select>
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Módulo</label>
                    <select 
                      required
                      value={form.modulo_id ?? ''}
                      onChange={e => setForm(prev => ({...prev, modulo_id: e.target.value}))}
                      className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-orange-500 outline-none appearance-none disabled:opacity-30"
                      disabled={!form.setor_id}
                    >
                      <option value="">Selecionar...</option>
                      {modulos.filter(m => String(m.setor_id) === String(form.setor_id)).map(m => (
                        <option key={m.id} value={m.id}>{m.nome}</option>
                      ))}
                    </select>
                  </div>
               </div>

               <div className="space-y-2">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Descrição</label>
                  <textarea 
                    rows={4}
                    value={form.descricao ?? ''}
                    onChange={e => setForm(prev => ({...prev, descricao: e.target.value}))}
                    className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-orange-500 outline-none transition-all resize-none text-sm"
                    placeholder="O que o aluno vai aprender nesta aula?"
                  />
               </div>
            </div>

            {/* Mídia */}
            <div className="pt-8 border-t border-white/5 space-y-8">
              
              {/* Upload de Banner (Sempre disponível) */}
              <div className="space-y-3">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Banner Personalizado (Thumbnail)</label>
                  <div className="relative group cursor-pointer">
                    <input 
                      type="file" 
                      accept="image/*"
                      onChange={e => setThumbFile(e.target.files?.[0] || null)}
                      className="absolute inset-0 opacity-0 cursor-pointer z-10"
                    />
                    <div className="w-full bg-slate-950/50 border border-dashed border-white/10 group-hover:border-orange-500/50 rounded-2xl p-6 flex items-center gap-4 transition-all">
                       <div className="w-12 h-12 rounded-xl bg-slate-900 flex items-center justify-center text-slate-500 group-hover:text-orange-500">
                          <Plus size={20} />
                       </div>
                       <span className="text-sm text-slate-500 font-medium">
                         {thumbFile ? thumbFile.name : "Clique para subir um banner customizado"}
                       </span>
                    </div>
                  </div>
              </div>

              {form.tipo_fonte === 'upload' ? (
                <div className="space-y-3">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Arquivo MP4</label>
                  <div className="relative group cursor-pointer">
                    <input 
                      type="file" 
                      accept="video/mp4"
                      onChange={e => setFile(e.target.files?.[0] || null)}
                      className="absolute inset-0 opacity-0 cursor-pointer z-10"
                    />
                    <div className="w-full bg-slate-950/50 border-2 border-dashed border-white/10 group-hover:border-orange-500/50 rounded-2xl p-12 flex flex-col items-center justify-center transition-all">
                       <Upload className="text-slate-600 group-hover:text-orange-500 mb-4 transition-colors" size={40} />
                       <span className="text-sm text-slate-500 font-medium">
                         {file ? file.name : "Arraste ou clique para selecionar o vídeo"}
                       </span>
                    </div>
                  </div>
                </div>
              ) : (
                 <div className="space-y-2">
                  <label className="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Link Direto</label>
                  <div className="relative">
                    <input 
                      key={`url-${form.tipo_fonte}`} // Evita conflito entre YouTube/Drive
                      required
                      type="text" 
                      value={form.url ?? ''}
                      onChange={e => setForm(prev => ({...prev, url: e.target.value}))}
                      className="w-full bg-slate-950/50 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-orange-500 outline-none transition-all pl-12"
                      placeholder={form.tipo_fonte === 'youtube' ? 'URL do YouTube...' : 'Link do Google Drive...'}
                    />
                    <div className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-600">
                       {form.tipo_fonte === 'youtube' ? <Video size={20} /> : <LinkIcon size={20} />}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Coluna Lateral / Ações */}
        <div className="space-y-8">
           
           {/* Sequência / Trilha */}
           <div className="bg-white/[0.02] border border-white/5 p-8 rounded-[32px] space-y-6">
              <div className="flex items-center justify-between">
                 <h3 className="text-xs font-black text-white uppercase tracking-widest">Configurar Sequência</h3>
                 <input 
                    type="checkbox" 
                    checked={form.is_sequencia}
                    onChange={e => setForm(prev => ({...prev, is_sequencia: e.target.checked}))}
                    className="w-5 h-5 rounded bg-slate-950 border-white/10 text-orange-500 focus:ring-orange-500"
                 />
              </div>

              {form.is_sequencia && (
                <div className="space-y-4 pt-4 border-t border-white/5 animate-in fade-in slide-in-from-top-2">
                   <div className="space-y-2">
                      <label className="text-[10px] text-slate-600 font-black uppercase">Selecionar Sequência Existente</label>
                      <select 
                        value={form.sequencia_id ?? ''}
                        onChange={e => setForm(prev => ({...prev, sequencia_id: e.target.value, nova_trilha_nome: ''}))}
                        className="w-full bg-slate-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none appearance-none"
                      >
                        <option value="">-- Nova Sequência --</option>
                        {trilhas.filter(t => String(t.modulo_id) === String(form.modulo_id)).map(t => (
                          <option key={t.id} value={t.id}>{t.nome}</option>
                        ))}
                      </select>
                   </div>

                   {(!form.sequencia_id || form.sequencia_id === '') && (
                     <div className="space-y-2">
                        <label className="text-[10px] text-slate-600 font-black uppercase">Nome da Nova Sequência</label>
                        <input 
                          key="nova_trilha"
                          type="text" 
                          value={form.nova_trilha_nome ?? ''}
                          onChange={e => setForm(prev => ({...prev, nova_trilha_nome: e.target.value}))}
                          className="w-full bg-slate-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none"
                          placeholder="Ex: Fluxo de Atendimento"
                        />
                     </div>
                   )}

                   <div className="space-y-2">
                      <label className="text-[10px] text-slate-600 font-black uppercase">Ordem de Exibição</label>
                      <input 
                        type="number" 
                        value={form.sequencia_ordem ?? ''}
                        onChange={e => setForm(prev => ({...prev, sequencia_ordem: e.target.value}))}
                        className="w-full bg-slate-950/50 border border-white/10 rounded-xl px-4 py-3 text-white outline-none"
                      />
                   </div>
                </div>
              )}
           </div>

        <div className="space-y-6">
          <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 sticky top-8">
            <h3 className="text-sm font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2">
               <Sparkles className="text-orange-500" size={16} /> Ações de Publicação
            </h3>
            
            <div className="space-y-4">
               {loading && uploadProgress > 0 && (
                 <div className="space-y-2">
                    <div className="flex justify-between text-[10px] font-black text-slate-500 uppercase">
                       <span>Progresso</span>
                       <span>{uploadProgress}%</span>
                    </div>
                    <div className="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                       <div 
                         className="h-full bg-orange-500 transition-all duration-300 shadow-[0_0_10px_rgba(249,115,22,0.5)]" 
                         style={{ width: `${uploadProgress}%` }}
                       />
                    </div>
                 </div>
               )}

               <button 
                 type="submit"
                 disabled={loading}
                 className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-slate-800 text-white py-4 rounded-2xl font-bold transition-all shadow-xl shadow-orange-500/20 flex items-center justify-center gap-3"
               >
                 {loading ? (
                   <>
                     <Loader2 className="animate-spin" size={20} />
                     {uploadProgress > 0 ? `Subindo ${uploadProgress}%...` : 'Publicando...'}
                   </>
                 ) : (
                   <>
                     <Save size={20} />
                     Publicar Conteúdo
                   </>
                 )}
               </button>
               
               <p className="text-[10px] text-slate-500 text-center px-4 leading-relaxed uppercase font-bold tracking-widest">
                 Ao publicar, o vídeo ficará disponível imediatamente para os alunos do módulo selecionado.
               </p>
            </div>
          </div>
        </div>

        </div>

      </form>
    </div>
  );
}
