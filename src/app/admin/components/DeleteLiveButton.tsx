'use client';

import { Trash2 } from 'lucide-react';
import { useRouter } from 'next/navigation';

export default function DeleteLiveButton({ id }: { id: number }) {
  const router = useRouter();

  const handleDelete = async (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (!confirm('Deseja realmente remover esta transmissão do histórico?')) return;

    try {
      const res = await fetch(`/api/admin/live/delete?id=${id}`, {
        method: 'DELETE',
      });

      if (res.ok) {
        // Recarrega a página para atualizar a lista
        router.refresh();
      }
    } catch (err) {
      console.error('Erro ao deletar live:', err);
    }
  };

  return (
    <button 
      onClick={handleDelete}
      className="w-10 h-10 rounded-2xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover:opacity-100 shadow-lg shadow-red-500/20"
      title="Excluir Transmissão"
    >
      <Trash2 size={18} />
    </button>
  );
}
