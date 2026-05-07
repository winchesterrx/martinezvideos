'use client';

import { useState, useEffect } from 'react';
import { 
  Plus, 
  Edit, 
  Trash2, 
  ArrowLeft,
  Loader2,
  Video,
  Box,
  Layout,
  Palette
} from 'lucide-react';
import Link from 'next/link';

export default function ModulesManagement() {
  const [modules, setModules] = useState<any[]>([]);
  const [sectors, setSectors] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingModule, setEditingModule] = useState<any>(null);
  const [formData, setFormData] = useState({
    nome: '',
    setor_id: '',
    descricao: '',
    icone: 'fas fa-cube',
    cor: '#6366f1',
    ativo: 'S'
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      const [modRes, secRes] = await Promise.all([
        fetch('/api/admin/modules'),
        fetch('/api/admin/sectors')
      ]);
      const modData = await modRes.json();
      const secData = await secRes.json();
      setModules(modData.modules || []);
      setSectors(secData.sectors || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    const method = editingModule ? 'PATCH' : 'POST';
    const url = editingModule ? `/api/admin/modules/${editingModule.id}` : '/api/admin/modules';

    try {
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      });

      if (res.ok) {
        setShowModal(false);
        setEditingModule(null);
        setFormData({ nome: '', setor_id: '', descricao: '', icone: 'fas fa-cube', cor: '#6366f1', ativo: 'S' });
        fetchData();
      } else {
        const data = await res.json();
        alert(data.error || 'Erro ao salvar');
      }
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Excluir este módulo? Isso não funcionará se houver vídeos vinculados.')) return;

    try {
      const res = await fetch(`/api/admin/modules/${id}`, { method: 'DELETE' });
      if (res.ok) fetchData();
      else {
        const data = await res.json();
        alert(data.error || 'Erro ao excluir');
      }
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
              <Layout className="text-purple-500" />
              Gestão de Módulos
            </h1>
            <p className="text-slate-500 mt-1">Organize os vídeos em categorias e subcategorias.</p>
          </div>
        </div>
        <button 
          onClick={() => { setEditingModule(null); setFormData({ nome: '', setor_id: '', descricao: '', icone: 'fas fa-cube', cor: '#6366f1', ativo: 'S' }); setShowModal(true); }}
          className="flex items-center gap-2 bg-purple-600 hover:bg-purple-500 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-purple-600/20"
        >
          <Plus size={20} />
          Novo Módulo
        </button>
      </div>

      {/* Modules Table */}
      <div className="bg-slate-900/40 border border-white/5 rounded-3xl overflow-hidden backdrop-blur-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-white/5 bg-white/5">
                <th className="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Módulo</th>
                <th className="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Sistema Pai</th>
                <th className="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest text-center">Cor</th>
                <th className="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest text-center">Status</th>
                <th className="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest text-right">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {loading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-20 text-center text-slate-500">
                     <Loader2 className="animate-spin mx-auto mb-4" size={32} />
                     Sincronizando módulos...
                  </td>
                </tr>
              ) : modules.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-20 text-center text-slate-500">
                     Nenhum módulo encontrado.
                  </td>
                </tr>
              ) : (
                modules.map((m) => (
                  <tr key={m.id} className="hover:bg-white/[0.02] transition-colors group">
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl flex items-center justify-center border border-white/5 bg-slate-800 text-slate-400 group-hover:text-purple-400 transition-colors">
                           <Box size={20} />
                        </div>
                        <div>
                          <p className="text-white font-bold text-sm">{m.nome}</p>
                          <p className="text-slate-500 text-[10px] uppercase font-bold tracking-tight line-clamp-1">{m.descricao || 'Sem descrição'}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                       <span className="text-xs font-bold text-slate-300 bg-slate-800 px-3 py-1 rounded-full border border-white/5">
                         {m.setor_nome}
                       </span>
                    </td>
                    <td className="px-6 py-4">
                       <div className="flex justify-center">
                         <div className="w-6 h-6 rounded-full border-2 border-white/10" style={{ backgroundColor: m.cor }} />
                       </div>
                    </td>
                    <td className="px-6 py-4">
                       <div className="flex justify-center">
                        {m.ativo === 'S' ? (
                          <span className="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]" />
                        ) : (
                          <span className="w-2 h-2 rounded-full bg-slate-700" />
                        )}
                       </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex justify-end gap-2">
                        <button 
                          onClick={() => { setEditingModule(m); setFormData({ nome: m.nome, setor_id: m.setor_id, descricao: m.descricao, icone: m.icone, cor: m.cor, ativo: m.ativo }); setShowModal(true); }}
                          className="p-2 text-slate-400 hover:text-white hover:bg-white/5 rounded-lg transition-all"
                        >
                          <Edit size={18} />
                        </button>
                        <button 
                          onClick={() => handleDelete(m.id)}
                          className="p-2 text-slate-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all"
                        >
                          <Trash2 size={18} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Module */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-slate-900 border border-white/10 rounded-[32px] w-full max-w-2xl overflow-hidden shadow-2xl">
            <div className="p-8 border-b border-white/5 bg-white/5">
              <h2 className="text-2xl font-bold text-white flex items-center gap-3">
                {editingModule ? 'Editar Módulo' : 'Novo Módulo'}
              </h2>
            </div>
            <form onSubmit={handleSave} className="p-8 space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Nome do Módulo</label>
                    <input 
                      required
                      type="text" 
                      value={formData.nome}
                      onChange={e => setFormData({...formData, nome: e.target.value})}
                      className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-purple-500/50"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Sistema Pai</label>
                    <select 
                      required
                      value={formData.setor_id}
                      onChange={e => setFormData({...formData, setor_id: e.target.value})}
                      className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-purple-500/50 appearance-none"
                    >
                      <option value="">Selecione um Sistema</option>
                      {sectors.map(s => (
                        <option key={s.id} value={s.id}>{s.nome}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Descrição Curta</label>
                    <textarea 
                      rows={3}
                      value={formData.descricao}
                      onChange={e => setFormData({...formData, descricao: e.target.value})}
                      className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-purple-500/50 resize-none"
                    />
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Ícone (CSS Class)</label>
                    <input 
                      type="text" 
                      value={formData.icone}
                      onChange={e => setFormData({...formData, icone: e.target.value})}
                      className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-purple-500/50"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2 flex items-center justify-between">
                       Cor do Módulo <Palette size={14} />
                    </label>
                    <input 
                      type="color" 
                      value={formData.cor}
                      onChange={e => setFormData({...formData, cor: e.target.value})}
                      className="w-full h-12 bg-slate-950 border border-white/10 rounded-xl cursor-pointer"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Status</label>
                    <select 
                      value={formData.ativo}
                      onChange={e => setFormData({...formData, ativo: e.target.value})}
                      className="w-full bg-slate-950 border border-white/10 rounded-xl py-3 px-4 text-white focus:outline-none focus:border-purple-500/50 appearance-none"
                    >
                      <option value="S">Ativo</option>
                      <option value="N">Inativo</option>
                    </select>
                  </div>
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
                  className="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold transition-all shadow-lg shadow-purple-600/20"
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
