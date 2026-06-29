'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  Home, 
  Compass, 
  FolderHeart, 
  Clock, 
  LogOut, 
  User,
  Settings
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export default function Sidebar({ 
  user, 
  setores, 
  isOpen, 
  closeSidebar 
}: { 
  user: any; 
  setores?: any[];
  isOpen: boolean;
  closeSidebar: () => void;
}) {
  const pathname = usePathname();

  const menuItems = [
    { icon: Home, label: 'Início', href: '/' },
    { icon: Compass, label: 'Explorar', href: '/explorar' },
    { icon: FolderHeart, label: 'Favoritos', href: '/favoritos' },
    { icon: Clock, label: 'Histórico', href: '/historico' },
  ];

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST' });
    window.location.href = '/login';
  };

  return (
    <>
      {/* Overlay Mobile */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={closeSidebar}
            className="md:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
          />
        )}
      </AnimatePresence>

      {/* Sidebar Desktop & Mobile */}
      <aside
        className={`fixed md:relative top-0 left-0 h-screen bg-black/20 backdrop-blur-3xl border-r border-white/5 z-50 flex flex-col transition-all duration-300 ease-in-out overflow-hidden ${
          isOpen ? 'w-[280px] opacity-100' : 'w-0 md:w-0 opacity-0 border-none'
        }`}
      >
        <div className="p-6 h-16 flex items-center justify-start border-b border-white/5">
          <img 
            src="/logo.png" 
            alt="Martinez & Carvalho" 
            className="h-full w-auto object-contain max-h-10"
          />
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-4 py-4 space-y-2 overflow-y-auto custom-scrollbar">
          {menuItems.map((item) => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={closeSidebar}
                className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${
                  isActive
                    ? 'bg-gradient-to-r from-orange-500/10 to-transparent text-orange-500 font-medium border border-orange-500/20'
                    : 'text-slate-400 hover:bg-white/5 hover:text-slate-200'
                }`}
              >
                <item.icon size={20} className={isActive ? 'text-orange-500' : ''} />
                {item.label}
              </Link>
            );
          })}

          {setores && setores.length > 0 && (
            <div className="pt-6 mt-4 border-t border-white/5">
              <span className="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-3 block">
                Meus Sistemas
              </span>
              <div className="space-y-1">
                {setores.map((setor) => {
                  const isActive = pathname.startsWith(`/sistema/${setor.id}`) || pathname.startsWith(`/modulo/`) && pathname.includes(setor.nome);
                  return (
                    <Link
                      key={setor.id}
                      href={`/sistema/${setor.id}`}
                      onClick={closeSidebar}
                      className={`group flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all text-sm font-medium ${
                        isActive ? 'text-orange-500 bg-orange-500/10 border border-orange-500/20' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200'
                      }`}
                    >
                      <div className={`w-2 h-2 rounded-full transition-colors ${isActive ? 'bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.6)]' : 'bg-slate-700 group-hover:bg-orange-500'}`} />
                      <span className="truncate">{setor.nome}</span>
                    </Link>
                  );
                })}
              </div>
            </div>
          )}
        </nav>

        {/* Footer Actions */}
        <div className="p-4 border-t border-white/5 space-y-2 bg-slate-900/30">
          {user?.adm === 'S' && (
            <Link
              href="/admin"
              className="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-white/5 hover:text-slate-200 transition-all text-sm font-medium"
            >
              <Settings size={18} />
              Administração
            </Link>
          )}
          {user ? (
            <button
              onClick={handleLogout}
              className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all text-sm font-medium"
            >
              <LogOut size={18} />
              Sair da Plataforma
            </button>
          ) : (
            <Link
              href="/login"
              className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-orange-400 hover:bg-orange-500/10 transition-all text-sm font-medium"
            >
              <User size={18} />
              Entrar na Plataforma
            </Link>
          )}
        </div>
      </aside>
    </>
  );
}
