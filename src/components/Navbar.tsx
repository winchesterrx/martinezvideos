'use client';

import { Search, Menu, UserCircle } from 'lucide-react';
import Link from 'next/link';
import { useState } from 'react';
import NotificationCenter from './NotificationCenter';

export default function Navbar({ user, toggleSidebar }: { user: any, toggleSidebar: () => void }) {
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      window.location.href = `/busca?q=${encodeURIComponent(searchQuery)}`;
    }
  };

  return (
    <header className="h-16 bg-slate-950/40 backdrop-blur-md border-b border-white/5 flex items-center justify-between px-4 md:px-8 sticky top-0 z-40">
      
      {/* Esquerda: Botão Menu Mobile */}
      <div className="flex items-center gap-4">
        <button 
          onClick={toggleSidebar}
          className="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-colors"
        >
          <Menu className="w-6 h-6" />
        </button>
      </div>

      {/* Centro: Barra de Busca (Oculta em telas muito pequenas) */}
      <div className="hidden md:flex flex-1 max-w-xl mx-8">
        <form onSubmit={handleSearch} className="w-full relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
          <input 
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Pesquisar vídeos, módulos ou trilhas..."
            className="w-full bg-slate-900/50 border border-white/10 rounded-full py-2 pl-10 pr-4 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-orange-500/50 focus:ring-1 focus:ring-orange-500/50 transition-all"
          />
        </form>
      </div>

      {/* Direita: Perfil e Notificações */}
      <div className="flex items-center gap-3">
        <NotificationCenter />
        
        <div className="flex items-center gap-2 pl-2 border-l border-white/10 ml-2">
          <div className="hidden md:block text-right">
            <p className="text-sm font-medium text-slate-200 leading-none">{user.nome}</p>
            <p className="text-xs text-slate-500 mt-1">{user.cargo || 'Aluno'}</p>
          </div>
          <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-orange-500 to-orange-700 flex items-center justify-center text-white shadow-lg">
            <UserCircle className="w-6 h-6" />
          </div>
        </div>
      </div>
    </header>
  );
}
