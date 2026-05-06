'use client';

import { useState } from 'react';
import { MoreVertical, Edit2, Trash2, Share2, Check, Copy } from 'lucide-react';
import { useRouter } from 'next/navigation';

interface VideoManagerActionsProps {
  videoId: number;
  videoTitle: string;
  isAdmin: boolean;
}

export default function VideoManagerActions({ videoId, videoTitle, isAdmin }: VideoManagerActionsProps) {
  const [showMenu, setShowMenu] = useState(false);
  const [copied, setCopied] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const router = useRouter();

  const handleShare = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    const url = `${window.location.origin}/video/${videoId}`;
    navigator.clipboard.writeText(url);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDelete = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (!confirm(`Tem certeza que deseja excluir o vídeo "${videoTitle}"?`)) return;

    setDeleting(true);
    try {
      const res = await fetch(`/api/admin/videos/${videoId}`, { method: 'DELETE' });
      if (res.ok) {
        router.refresh();
      } else {
        alert('Erro ao excluir vídeo');
      }
    } catch (error) {
      alert('Erro de conexão');
    } finally {
      setDeleting(false);
    }
  };

  const handleEdit = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    // Por enquanto redireciona para o admin, no futuro podemos ter uma página de edição específica
    // router.push(`/admin/edit/${videoId}`);
    alert('Função de edição em desenvolvimento. Por enquanto, utilize o painel de upload para novos vídeos.');
  };

  return (
    <div className="absolute top-2 right-2 z-20 flex gap-2">
      {/* Botão Compartilhar sempre visível ou no hover */}
      <button 
        onClick={handleShare}
        className="w-8 h-8 rounded-lg bg-slate-950/80 backdrop-blur-md border border-white/10 flex items-center justify-center text-white hover:bg-orange-500 transition-all shadow-lg"
        title="Compartilhar Link"
      >
        {copied ? <Check size={14} className="text-emerald-400" /> : <Share2 size={14} />}
      </button>

      {isAdmin && (
        <div className="relative">
          <button 
            onClick={(e) => { e.preventDefault(); e.stopPropagation(); setShowMenu(!showMenu); }}
            className="w-8 h-8 rounded-lg bg-slate-950/80 backdrop-blur-md border border-white/10 flex items-center justify-center text-white hover:bg-white/20 transition-all shadow-lg"
          >
            <MoreVertical size={14} />
          </button>

          {showMenu && (
            <>
              <div className="fixed inset-0 z-30" onClick={() => setShowMenu(false)} />
              <div className="absolute right-0 mt-2 w-36 bg-slate-900 border border-white/10 rounded-xl shadow-2xl z-40 overflow-hidden animate-in fade-in zoom-in-95">
                <button 
                  onClick={handleEdit}
                  className="w-full flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-slate-300 hover:bg-white/5 transition-colors"
                >
                  <Edit2 size={14} /> Editar
                </button>
                <button 
                  onClick={handleDelete}
                  disabled={deleting}
                  className="w-full flex items-center gap-3 px-4 py-3 text-[11px] font-bold uppercase tracking-widest text-red-400 hover:bg-red-500/10 transition-colors"
                >
                  <Trash2 size={14} /> {deleting ? 'Apagando...' : 'Excluir'}
                </button>
              </div>
            </>
          )}
        </div>
      )}
    </div>
  );
}
