'use client';

import { useState, useEffect } from 'react';
import { 
  Bell, 
  Plus, 
  Edit, 
  Trash2, 
  ArrowLeft,
  Loader2,
  Image as ImageIcon,
  Link as LinkIcon,
  Video,
  Eye,
  Type,
  FileText
} from 'lucide-react';
import Link from 'next/link';

export default function NoticesManagement() {
  const [notices, setNotices] = useState<any[]>([]);
  const [videos, setVideos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingNotice, setEditingNotice] = useState<any>(null);
  const [formData, setFormData] = useState({
    tipo: 'Aviso',
    titulo: '',
    mensagem: '',
    link: '',
    video_id: '',
    imagem_fundo: ''
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [notRes, vidRes] = await Promise.all([
        fetch('/api/admin/notices'),
        fetch('/api/videos/list') // Supondo que exista ou vou criar
      ]);
      const notData = await notRes.json();
      const vidData = await vidRes.json();
      setNotices(notData.notices || []);
      setVideos(vidData.videos || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    const method = editingNotice ? 'PATCH' : 'POST';
    const url = editingNotice ? `/api/admin/notices/${editingNotice.id}` : '/api/admin/notices';

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      if (res.ok) {
        setShowModal(false);
        setEditingNotice(null);
        setFormData({ tipo: 'Aviso', titulo: '', mensagem: '', link: '', video_id: '', imagem_fundo: '' });
        fetchData();
      } else {
        alert('Erro ao salvar');
      }
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Deseja excluir este aviso?')) return;
    try {
      const res = await fetch(`/api/admin/notices/${id}`, { method: 'DELETE' });
      if (res.ok) fetchData();
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  return (
    <div className="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <Link href="/admin" className="p-2 bg-slate-900 rounded-xl text-slate-400 hover:text-white transition-colors border border-white/5">
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
              <Bell className="text-orange-500" />
              Mural de Avisos
            </h1>
            <p className="text-slate-500 mt-1">Gerencie os banners informativos da página inicial.</p>
          </div>
        </div>
        <button 
          onClick={() => { setEditingNotice(null); setFormData({ tipo: 'Aviso', titulo: '', mensagem: '', link: '', video_id: '', imagem_fundo: '' }); setShowModal(true); }}
          className="flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-orange-600/20"
        >
          <Plus size={20} />
          Novo Aviso
        </button>
      </div>

      {/* Notices Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <div className="col-span-full py-20 text-center text-slate-500">
            <Loader2 className="animate-spin mx-auto mb-4" size={32} />
            Carregando mural...
          </div>
        ) : notices.length === 0 ? (
          <div className="col-span-full py-20 text-center text-slate-500">
            Nenhum aviso cadastrado.
          </div>
        ) : (
          notices.map((n) => (
            <div key={n.id} className="relative group bg-slate-900 border border-white/5 rounded-[32px] overflow-hidden hover:border-orange-500/50 transition-all h-[240px]">
              {n.imagem_fundo && (
                <img src={n.imagem_fundo} className="absolute inset-0 w-full h-full object-cover opacity-30 group-hover:scale-105 transition-transform duration-500" alt="" />
              )}
              <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-transparent z-10" />
              
              <div className="relative z-20 p-6 h-full flex flex-col justify-between">
                <div>
                  <span className="text-[10px] font-black uppercase tracking-widest text-orange-500 bg-orange-500/10 px-2.5 py-1 rounded-md">
                    {n.tipo}
                  </span>
                  <h3 className="text-lg font-bold text-white mt-3 line-clamp-2">{n.titulo}</h3>
                </div>

                <div className="flex items-center justify-between">
                   <div className="flex gap-2">
                     {n.video_id && <Video size={16} className="text-emerald-500" />}
                     {n.link && <LinkIcon size={16} className="text-blue-500" />}
                   </div>
                   <div className="flex gap-2">
                    <button 
                      onClick={() => { setEditingNotice(n); setFormData({ tipo: n.tipo, titulo: n.titulo, mensagem: n.mensagem || '', link: n.link || '', video_id: n.video_id || '', imagem_fundo: n.imagem_fundo || '' }); setShowModal(true); }}
                      className="p-2 bg-white/5 hover:bg-white/10 rounded-lg text-slate-400 hover:text-white transition-all"
                    >
                      <Edit size={16} />
                    </button>
                    <button 
                      onClick={() => handleDelete(n.id)}
                      className="p-2 bg-red-500/5 hover:bg-red-500/10 rounded-lg text-red-500 transition-all"
                    >
                      <Trash2 size={16} />
                    </button>
                   </div>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Modal Notice */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-slate-900 border border-white/10 rounded-[40px] w-full max-w-3xl overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="p-8 border-b border-white/5 bg-white/5">
              <h2 className="text-2xl font-bold text-white flex items-center gap-3">
                {editingNotice ? 'Editar Aviso' : 'Novo Aviso Cinematográfico'}
              </h2>
            </div>
            <form onSubmit={handleSave} className="p-8 space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Título do Aviso</label>
                    <div className="relative">
                      <Type className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={16} />
                      <input required type="text" value={formData.titulo} onChange={e => setFormData({...formData, titulo: e.target.value})} className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white focus:border-orange-500/50" />
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo</label>
                    <select value={formData.tipo} onChange={e => setFormData({...formData, tipo: e.target.value})} className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white appearance-none">
                      <option value="Aviso">Aviso</option>
                      <option value="Notícia">Notícia</option>
                      <option value="Novidade">Novidade</option>
                      <option value="Importante">Importante</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Mensagem (Conteúdo)</label>
                    <div className="relative">
                      <FileText className="absolute left-4 top-4 text-slate-500" size={16} />
                      <textarea rows={4} value={formData.mensagem} onChange={e => setFormData({...formData, mensagem: e.target.value})} className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white focus:border-orange-500/50 resize-none" />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Imagem de Fundo (URL)</label>
                    <div className="relative">
                      <ImageIcon className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={16} />
                      <input type="text" value={formData.imagem_fundo} onChange={e => setFormData({...formData, imagem_fundo: e.target.value})} placeholder="https://..." className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white focus:border-orange-500/50" />
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Vincular a um Vídeo (Opcional)</label>
                    <div className="relative">
                      <Video className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={16} />
                      <select value={formData.video_id} onChange={e => setFormData({...formData, video_id: e.target.value})} className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white appearance-none">
                        <option value="">Nenhum Vídeo</option>
                        {videos.map(v => <option key={v.id} value={v.id}>{v.titulo}</option>)}
                      </select>
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Link Externo (Opcional)</label>
                    <div className="relative">
                      <LinkIcon className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={16} />
                      <input type="text" value={formData.link} onChange={e => setFormData({...formData, link: e.target.value})} placeholder="https://..." className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white focus:border-orange-500/50" />
                    </div>
                  </div>
                  {formData.imagem_fundo && (
                    <div className="aspect-video rounded-xl border border-white/10 overflow-hidden relative">
                      <img src={formData.imagem_fundo} className="w-full h-full object-cover" alt="Preview" />
                      <div className="absolute inset-0 bg-black/40 flex items-center justify-center text-[10px] font-bold text-white uppercase tracking-widest">Preview da Mascara</div>
                    </div>
                  )}
                </div>
              </div>

              <div className="flex gap-4 pt-4">
                <button type="button" onClick={() => setShowModal(false)} className="flex-1 py-4 border border-white/10 rounded-2xl text-slate-400 font-bold hover:bg-white/5 transition-all">Cancelar</button>
                <button type="submit" className="flex-1 py-4 bg-orange-600 hover:bg-orange-500 text-white rounded-2xl font-bold transition-all shadow-lg shadow-orange-600/20">Salvar Aviso</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
