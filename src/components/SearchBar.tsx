import { Search } from 'lucide-react';

export default function SearchBar() {
  return (
    <div className="py-10">
      <div className="relative group">
        {/* Animated Glow Backdrop */}
        <div className="absolute inset-0 bg-orange-500/20 blur-[100px] group-focus-within:bg-orange-500/40 transition-all duration-700 opacity-50" />
        
        <div className="relative bg-slate-950/40 backdrop-blur-2xl border border-white/10 rounded-[32px] flex items-center px-8 py-6 gap-6 focus-within:border-orange-500/50 focus-within:ring-4 focus-within:ring-orange-500/10 transition-all shadow-2xl overflow-hidden">
          {/* Subtle noise pattern overlay */}
          <div className="absolute inset-0 opacity-[0.03] pointer-events-none bg-[url('https://grainy-gradients.vercel.app/noise.svg')]" />
          
          <Search className="text-slate-500 group-focus-within:text-orange-500 transition-colors shrink-0" size={28} />
          <input
            type="text"
            placeholder="O que você deseja aprender hoje?"
            className="bg-transparent border-none outline-none w-full text-white text-xl placeholder:text-slate-600 font-black tracking-tight"
            style={{ fontFamily: "'Outfit', sans-serif" }}
          />
          
          <div className="hidden md:flex items-center gap-2 px-3 py-1.5 bg-white/5 rounded-xl border border-white/10 text-[10px] font-black text-slate-500 uppercase tracking-widest">
            <span className="text-slate-400">Pressione</span> /
          </div>
        </div>
      </div>
    </div>
  );
}
