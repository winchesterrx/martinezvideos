import Link from 'next/link';
import { Folder } from 'lucide-react';

interface SystemCardProps {
  sistema: any;
}

export default function SystemCard({ sistema }: SystemCardProps) {
  return (
    <Link
      key={sistema.id}
      href={`/sistema/${sistema.id}`}
      className="flex items-center gap-3 p-4 rounded-2xl bg-slate-900/40 backdrop-blur-md border border-white/5 hover:bg-orange-500/10 hover:border-orange-500/30 transition-all group shadow-xl"
    >
      <div className="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center group-hover:bg-orange-500 group-hover:scale-110 transition-all duration-300 shadow-inner">
        <Folder className="w-5 h-5 text-slate-400 group-hover:text-white" />
      </div>
      <div className="flex flex-col min-w-0">
        <span className="text-sm font-black text-slate-200 group-hover:text-white truncate">
          {sistema.nome}
        </span>
        <span className="text-[9px] font-bold text-slate-500 uppercase tracking-widest group-hover:text-orange-500/70 transition-colors">
          Sistema Ativo
        </span>
      </div>
    </Link>
  );
}
