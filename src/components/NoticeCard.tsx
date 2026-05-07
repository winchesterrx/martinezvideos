import Link from 'next/link';
import { Sparkles, PlayCircle, Compass } from 'lucide-react';

interface NoticeCardProps {
  noticia: any; // id, titulo, mensagem, imagem_fundo, tipo, created_at, video_id, link
  formatDate: (date: string) => string;
}

export default function NoticeCard({ noticia, formatDate }: NoticeCardProps) {
  return (
    <Link
      href={noticia.video_id ? `/video/${noticia.video_id}` : noticia.link || '#'}
      className="group relative h-[420px] rounded-[32px] overflow-hidden border border-white/10 hover:border-orange-500/50 transition-all duration-500 flex flex-col justify-end p-8 bg-slate-950/20 backdrop-blur-md shadow-2xl hover:shadow-orange-500/10"
    >
      {/* Background Image with Zoom Effect */}
      <div className="absolute inset-0 z-0">
        {noticia.imagem_fundo ? (
          <img
            src={noticia.imagem_fundo}
            className="w-full h-full object-cover opacity-40 group-hover:opacity-60 group-hover:scale-110 transition-all duration-1000 ease-out"
            alt=""
          />
        ) : (
          <div className="w-full h-full bg-gradient-to-br from-slate-900 to-slate-950 opacity-40" />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent" />
      </div>

      {/* Content */}
      <div className="relative z-10 space-y-4">
        <div className="flex items-center gap-3">
          <span className="px-3 py-1 rounded-full bg-orange-500 text-[10px] font-black text-white uppercase tracking-wider shadow-lg shadow-orange-500/40">
            {noticia.tipo || 'Aviso'}
          </span>
          <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            {formatDate(noticia.created_at)}
          </span>
        </div>

        <h3 className="text-2xl font-black text-white leading-tight group-hover:text-orange-400 transition-colors duration-300">
          {noticia.titulo}
        </h3>
        
        <p className="text-slate-400 text-sm line-clamp-2 leading-relaxed group-hover:text-slate-200 transition-colors duration-300">
          {noticia.mensagem}
        </p>

        <div className="pt-4">
          <div className="inline-flex items-center gap-2 text-[11px] font-black text-white uppercase tracking-[0.2em] group-hover:text-orange-500 transition-colors">
            {noticia.video_id ? (
              <><PlayCircle size={16} className="animate-pulse" /> Assistir Aula</>
            ) : (
              <><Compass size={16} /> Ver Detalhes</>
            )}
          </div>
        </div>
      </div>

      {/* Hover Light Effect */}
      <div className="absolute -bottom-24 -right-24 w-64 h-64 bg-orange-500/10 blur-[100px] rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-700" />
    </Link>
  );
}
