'use client';

import { useState, useEffect } from 'react';
import { 
  Settings, 
  Plus, 
  Edit, 
  Trash2, 
  ArrowLeft,
  Loader2,
  CheckCircle2,
  XCircle,
  AlertTriangle
} from 'lucide-react';
import Link from 'next/link';

export default function SectorsManagement() {
  const [sectors, setSectors] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingSector, setEditingSector] = useState<any>(null);
  const [formData, setFormData] = useState({
    nome: '',
    ativo: 'S'
  });

  useEffect(() => {
    fetchSectors();
  }, []);

  const fetchSectors = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/sectors');
      const data = await res.json();
      setSectors(data.sectors || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    const method = editingSector ? 'PATCH' : 'POST';
    const url = editingSector ? `/api/admin/sectors/${editingSector.id}` : '/api/admin/sectors';

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      if (res.ok) {
        setShowModal(false);
        setEditingSector(null);
        setFormData({ nome: '', ativo: 'S' });
        fetchSectors();
      } else {
        const data = await res.json();
        alert(data.error || 'Erro ao salvar');
      }
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Excluir este sistema? Isso não funcionará se houver vídeos vinculados.')) return;

    try {
      const res = await fetch(`/api/admin/sectors/${id}`, { method: 'DELETE' });
      if (res.ok) fetchSectors();
      else {
        const data = await res.json();
        alert(data.error || 'Erro ao excluir');
      }
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  return (
    <div className="p-6 md:p-10 max-w-5xl mx-auto space-y-8">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="flex items-center gap-4">
          <Link href="/admin" className="p-2 bg-slate-900 rounded-xl text-slate-400 hover:text-white transition-colors border border-white/5">
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
              <Settings className="text-orange-500" />
              Gestão de Sistemas
            </h1>
            <p className="text-slate-500 mt-1">Gerencie as categorias principais (Saúde, Licitação, etc).</p>
          </div>
        </div>
        <button 
          onClick={() => { setEditingSector(null); setFormData({ nome: '', ativo: 'S' }); setShowModal(true); }}
          className="flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-orange-600/20"
        >
          <Plus size={20} />
          Novo Sistema
        </button>
      </div>

      {/* Warning */}
      <div className="bg-orange-500/10 border border-orange-500/20 rounded-2xl p-4 flex gap-4 text-orange-400">
        <AlertTriangle className="shrink-0" size={20} />
        <p className="text-xs font-medium leading-relaxed">
          Os sistemas aparecem diretamente no menu lateral da plataforma. Desativar um sistema fará com que ele suma do menu para todos os usuários.
        </p>
      </div>

      {/* Sectors Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {loading ? (
          <div className="md:col-span-2 py-20 text-center text-slate-500 bg-slate-900/40 rounded-3xl border border-white/5">
            <Loader2 className="animate-spin mx-auto mb-4" size={32} />
            Carregando sistemas...
          </div>
        ) : sectors.length === 0 ? (
          <div className="md:col-span-2 py-20 text-center text-slate-500 bg-slate-900/40 rounded-3xl border border-white/5">
            Nenhum sistema cadastrado.
          </div>
        ) : (
          sectors.map((s) => (
            <div key={s.id} className="bg-slate-900/40 border border-white/5 rounded-2xl p-6 flex items-center justify-between group hover:border-white/10 transition-all">
              <div className="flex items-center gap-4">
                <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-black ${s.ativo === 'S' ? 'bg-orange-500/10 text-orange-500' : 'bg-slate-800 text-slate-500'}`}>
                  {s.nome.charAt(0).toUpperCase()}
                </div>
                <div>
                  <h3 className="text-white font-bold">{s.nome}</h3>
                  <div className="flex items-center gap-2 mt-1">
                    {s.ativo === 'S' ? (
                      <span className="text-[9px] font-black text-emerald-500 uppercase tracking-widest flex items-center gap-1">
                        <CheckCircle2 size={10} /> Ativo no Menu
                      </span>
                    ) : (
                      <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1">
                        <XCircle size={10} /> Oculto
                      </span>
                    )}
                  </div>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button 
                  onClick={() => { setEditingSector(s); setFormData({ nome: s.nome, ativo: s.ativo }); setShowModal(true); }}
                  className="p-2 text-slate-400 hover:text-white hover:bg-white/5 rounded-lg transition-all"
                >
                  <Edit size={18} />
                </button>
                <button 
                  onClick={() => handleDelete(s.id)}
                  className="p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                >
                  <Trash2 size={18} />
                </button>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Modal Sector */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-slate-900 border border-white/10 rounded-[32px] w-full max-w-md overflow-hidden shadow-2xl">
            <div className="p-8 border-b border-white/5 bg-white/5">
              <h2 className="text-2xl font-bold text-white flex items-center gap-3">
                {editingSector ? 'Editar Sistema' : 'Novo Sistema'}
              </h2>
            </div>
            <form onSubmit={handleSave} className="p-8 space-y-6">
              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Nome do Sistema</label>
                  <input 
                    required
                    type="text" 
                    value={formData.nome}
                    onChange={e => setFormData({...formData, nome: e.target.value})}
                    placeholder="Ex: Saúde, Licitação..."
                    className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-orange-500/50"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Status de Exibição</label>
                  <select 
                    value={formData.ativo}
                    onChange={e => setFormData({...formData, ativo: e.target.value})}
                    className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-orange-500/50 appearance-none"
                  >
                    <option value="S">Ativo (Aparece na Sidebar)</option>
                    <option value="N">Inativo (Fica oculto)</option>
                  </select>
                </div>
              </div>

              <div className="flex gap-4 pt-4">
                <button 
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="flex-1 py-3 border border-white/10 rounded-xl text-slate-400 font-bold hover:bg-white/5 transition-all"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  className="flex-1 py-3 bg-orange-600 hover:bg-orange-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-orange-600/20"
                >
                  Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
