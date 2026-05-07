'use client';

import { useState, useEffect } from 'react';
import { 
  MessageSquare, 
  Trash2, 
  User, 
  Video, 
  Calendar,
  ExternalLink,
  ArrowLeft,
  Loader2,
  Search
} from 'lucide-react';
import Link from 'next/link';

export default function CommentsManagement() {
  const [comments, setComments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchComments();
  }, []);

  const fetchComments = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/admin/comments');
      const data = await res.json();
      setComments(data.comments || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Deseja realmente excluir este comentário?')) return;

    try {
      const res = await fetch(`/api/admin/comments/${id}`, { method: 'DELETE' });
      if (res.ok) fetchComments();
      else alert('Erro ao excluir');
    } catch (e) {
      alert('Erro de conexão');
    }
  };

  const filteredComments = comments.filter(c => 
    c.conteudo.toLowerCase().includes(search.toLowerCase()) || 
    c.usuario_nome.toLowerCase().includes(search.toLowerCase()) ||
    c.video_titulo.toLowerCase().includes(search.toLowerCase())
  );

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
              <MessageSquare className="text-indigo-500" />
              Moderação de Comentários
            </h1>
            <p className="text-slate-500 mt-1">Veja e gerencie o que os alunos estão falando.</p>
          </div>
        </div>
      </div>

      {/* Search Bar */}
      <div className="relative">
        <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={20} />
        <input 
          type="text" 
          placeholder="Buscar por conteúdo, autor ou vídeo..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full bg-slate-900/50 border border-white/10 rounded-2xl py-4 pl-12 pr-4 text-white focus:outline-none focus:border-indigo-500/50 transition-all"
        />
      </div>

      {/* Comments List */}
      <div className="grid gap-4">
        {loading ? (
          <div className="py-20 text-center text-slate-500 bg-slate-900/40 rounded-3xl border border-white/5">
            <Loader2 className="animate-spin mx-auto mb-4" size={32} />
            Carregando comentários...
          </div>
        ) : filteredComments.length === 0 ? (
          <div className="py-20 text-center text-slate-500 bg-slate-900/40 rounded-3xl border border-white/5">
            Nenhum comentário encontrado.
          </div>
        ) : (
          filteredComments.map((comment) => (
            <div key={comment.id} className="bg-slate-900/40 border border-white/5 rounded-3xl p-6 hover:border-white/10 transition-all group">
              <div className="flex flex-col md:flex-row gap-6">
                {/* Meta Info */}
                <div className="md:w-64 space-y-3 shrink-0">
                  <div className="flex items-center gap-3 text-slate-300">
                    <div className="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                      <User size={14} />
                    </div>
                    <div className="min-w-0">
                      <p className="text-xs font-bold truncate">{comment.usuario_nome}</p>
                      <p className="text-[10px] text-slate-500 truncate">{comment.usuario_email}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3 text-slate-300">
                    <div className="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                      <Video size={14} />
                    </div>
                    <Link href={`/video/${comment.video_id}`} className="min-w-0 hover:text-emerald-400 transition-colors">
                      <p className="text-xs font-bold truncate">{comment.video_titulo}</p>
                    </Link>
                  </div>
                  <div className="flex items-center gap-3 text-slate-500">
                    <div className="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center shrink-0">
                      <Calendar size={14} />
                    </div>
                    <p className="text-[10px] font-medium">
                      {new Date(comment.data).toLocaleString('pt-BR')}
                    </p>
                  </div>
                </div>

                {/* Content */}
                <div className="flex-1 flex flex-col justify-between">
                  <p className="text-slate-200 text-sm leading-relaxed italic">
                    "{comment.conteudo}"
                  </p>
                  <div className="flex justify-end gap-3 mt-4 md:mt-0">
                    <Link 
                      href={`/video/${comment.video_id}`}
                      className="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition-all text-xs font-bold"
                    >
                      <ExternalLink size={14} /> Ir para Vídeo
                    </Link>
                    <button 
                      onClick={() => handleDelete(comment.id)}
                      className="flex items-center gap-2 px-4 py-2 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all text-xs font-bold"
                    >
                      <Trash2 size={14} /> Excluir
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
