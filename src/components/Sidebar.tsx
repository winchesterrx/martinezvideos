'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  Home, 
  Compass, 
  FolderHeart, 
  Clock, 
  LogOut, 
  Menu, 
  X, 
  User,
  Settings
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export default function Sidebar({ user, setores }: { user: any, setores?: any[] }) {
  const [isOpen, setIsOpen] = useState(false);
  const pathname = usePathname();

  const toggleSidebar = () => setIsOpen(!isOpen);

  const menuItems = [
    { icon: Home, label: 'Início', href: '/' },
    { icon: Compass, label: 'Explorar', href: '/explorar' },
    { icon: FolderHeart, label: 'Favoritos', href: '/favoritos' },
    { icon: Clock, label: 'Histórico', href: '/historico' },
  ];

  const sidebarVariants = {
    open: { x: 0, transition: { type: 'spring', stiffness: 300, damping: 30 } },
    closed: { x: '-100%', transition: { type: 'spring', stiffness: 300, damping: 30 } },
  };

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST' });
    window.location.href = '/login';
  };

  return (
    <>
      {/* Mobile Header */}
      <div className="md:hidden fixed top-0 left-0 w-full h-16 bg-slate-950/80 backdrop-blur-md border-b border-white/5 z-40 flex items-center justify-between px-4">
        <div className="flex items-center gap-2 text-orange-500 font-bold text-xl">
          <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white">
            <span className="text-sm">MV</span>
          </div>
          Martinez
        </div>
        <button onClick={toggleSidebar} className="text-slate-300 p-2">
          {isOpen ? <X size={24} /> : <Menu size={24} />}
        </button>
      </div>

      {/* Overlay Mobile */}
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={toggleSidebar}
            className="md:hidden fixed inset-0 bg-black/60 z-40"
          />
        )}
      </AnimatePresence>

      {/* Sidebar Desktop & Mobile */}
      <motion.aside
        initial={false}
        animate={isOpen ? 'open' : 'closed'}
        variants={sidebarVariants}
        className="fixed md:static top-0 left-0 h-screen w-[280px] bg-slate-950 border-r border-white/5 z-50 flex flex-col md:translate-x-0"
        style={{ x: 0 }} // Override for desktop
      >
        <div className="p-6 flex items-center gap-3 text-orange-500 font-bold text-2xl hidden md:flex">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
            <span className="text-lg">MV</span>
          </div>
          Martinez
        </div>

        {/* User Profile Summary */}
        {user && (
          <div className="px-6 py-4 mb-4 border-b border-white/5 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300 overflow-hidden relative">
              <User size={20} />
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-semibold text-white">{user.nome}</span>
              <span className="text-xs text-slate-500 truncate max-w-[150px]">{user.email}</span>
            </div>
          </div>
        )}

        {/* Navigation */}
        <nav className="flex-1 px-4 space-y-2 overflow-y-auto">
          {menuItems.map((item) => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setIsOpen(false)}
                className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-all ${
                  isActive
                    ? 'bg-gradient-to-r from-orange-500/10 to-transparent text-orange-500 font-medium'
                    : 'text-slate-400 hover:bg-white/5 hover:text-slate-200'
                }`}
              >
                <item.icon size={20} className={isActive ? 'text-orange-500' : ''} />
                {item.label}
              </Link>
            );
          })}

          {setores && setores.length > 0 && (
            <div className="pt-4 mt-4 border-t border-white/5">
              <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 mb-2 block">
                Meus Sistemas
              </span>
              {setores.map((setor) => (
                <Link
                  key={setor.id}
                  href={`/sistema/${setor.id}`}
                  onClick={() => setIsOpen(false)}
                  className={`flex items-center gap-3 px-4 py-2 rounded-xl transition-all text-slate-400 hover:bg-white/5 hover:text-slate-200 ${
                    pathname.startsWith(`/sistema/${setor.id}`) ? 'text-orange-500 bg-white/5' : ''
                  }`}
                >
                  <div className="w-1.5 h-1.5 rounded-full bg-slate-600 group-hover:bg-orange-500 transition-colors" />
                  <span className="text-sm truncate">{setor.nome}</span>
                </Link>
              ))}
            </div>
          )}
        </nav>

        {/* Footer Actions */}
        <div className="p-4 border-t border-white/5 space-y-2">
          {user?.adm === 'S' && (
            <Link
              href="/admin"
              className="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-white/5 hover:text-slate-200 transition-all"
            >
              <Settings size={20} />
              Administração
            </Link>
          )}
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all"
          >
            <LogOut size={20} />
            Sair
          </button>
        </div>
      </motion.aside>
    </>
  );
}
