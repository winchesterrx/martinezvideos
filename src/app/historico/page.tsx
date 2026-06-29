import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import { redirect } from 'next/navigation';
import { Clock, History, PlayCircle, Calendar, Trash2 } from 'lucide-react';
import Link from 'next/link';

// Componente para formatar data de forma amigável
function formatTimeAgo(dateString: string) {
  const date = new Date(dateString);
  const now = new Date();
  const diffInMs = now.getTime() - date.getTime();
  const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

  if (diffInDays === 0) return 'Hoje';
  if (diffInDays === 1) return 'Ontem';
  if (diffInDays < 7) return `${diffInDays} dias atrás`;
  return date.toLocaleDateString('pt-BR');
}

export default async function HistoryPage() {
  const session = await getSession();
  
  if (!session) redirect('/login?redirect=/historico');

  const pool = await getDbConnection();
  
  const [historyRows] = await pool.query(`
    SELECT v.*, h.visualizado_em, s.nome as setor_nome
    FROM usuario_historico h
    JOIN videos v ON h.video_id = v.id
    LEFT JOIN setores s ON v.setor_id = s.id
    WHERE h.usuario_id = ?
    ORDER BY h.visualizado_em DESC
    LIMIT 40
  `, [session.id]);

  const historico = historyRows as any[];

  return (
    <div className="min-h-screen bg-slate-950 text-white pb-32">
      {/* Header Minimalista e Elegante com Profundidade */}
      <header className="relative px-8 pt-20 pb-16 max-w-6xl mx-auto">
         {/* Elemento de Brilho Etéreo */}
         <div className="absolute top-0 left-1/4 w-96 h-96 bg-orange-500/5 blur-[120px] rounded-full pointer-events-none" />
         
         <div className="relative z-10 flex flex-col gap-4">
            <div className="flex items-center gap-4">
               <div className="h-[1px] w-12 bg-gradient-to-r from-orange-500 to-transparent" />
               <span className="text-[10px] font-black uppercase tracking-[0.4em] text-orange-500/80">Recents</span>
            </div>
            
            <h1 className="text-4xl md:text-6xl font-black tracking-tighter leading-none" style={{ fontFamily: "'Outfit', sans-serif" }}>
              Histórico de <br className="md:hidden" />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-slate-200 via-slate-400 to-slate-500">
                Aprendizado
              </span>
            </h1>
            
            <p className="text-slate-500 text-sm font-medium max-w-md leading-relaxed border-l border-white/5 pl-6 mt-2">
              Sua jornada de conhecimento organizada de forma cronológica e técnica.
            </p>
         </div>
      </header>

      <div className="max-w-6xl mx-auto px-4 md:px-8">
        {historico.length > 0 ? (
          <div className="grid grid-cols-1 gap-4">
            {historico.map((item, index) => (
              <Link 
                key={`${item.id}-${index}`} 
                href={`/video/${item.id}`}
                className="group flex items-center gap-6 p-3 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:bg-white/[0.05] hover:border-orange-500/20 transition-all duration-500"
              >
                {/* Compact Thumbnail */}
                <div className="relative w-24 md:w-40 aspect-video rounded-xl overflow-hidden bg-slate-900 shrink-0">
                  {item.thumbnail ? (
                    <img src={item.thumbnail} className="w-full h-full object-cover grayscale-[0.5] group-hover:grayscale-0 transition-all duration-700" alt="" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center bg-slate-800">
                       <PlayCircle className="w-6 h-6 text-slate-600" />
                    </div>
                  )}
                </div>

                {/* Content - Ultra Minimalist */}
                <div className="flex-1 min-w-0 flex items-center justify-between gap-4">
                  <div className="flex flex-col gap-1">
                    <span className="text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                      {item.setor_nome || 'Sistema'}
                    </span>
                    <h2 className="text-lg font-bold text-slate-200 group-hover:text-white transition-colors truncate">
                      {item.titulo}
                    </h2>
                  </div>
                  
                  <div className="hidden md:flex flex-col items-end gap-1 shrink-0">
                     <span className="text-[10px] font-bold text-slate-500 uppercase">
                       {formatTimeAgo(item.visualizado_em)}
                     </span>
                     <span className="text-[9px] text-slate-700 font-medium italic">
                        {new Date(item.visualizado_em).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                     </span>
                  </div>
                </div>

                {/* Small Play Indicator */}
                <div className="w-10 h-10 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 group-hover:bg-orange-500 text-white transition-all duration-500 shrink-0">
                   <PlayCircle size={20} />
                </div>
              </Link>
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-48 text-center animate-in fade-in duration-1000">
             <div className="relative mb-8">
                <History className="w-20 h-20 text-slate-900" />
                <div className="absolute inset-0 bg-orange-500/10 blur-3xl rounded-full" />
             </div>
             <h3 className="text-xl font-bold text-slate-400 mb-2">Nada por aqui ainda</h3>
             <p className="text-sm text-slate-600 max-w-xs mb-8">
               Seu histórico é como uma página em branco pronta para ser preenchida.
             </p>
             <Link href="/" className="text-xs font-black uppercase tracking-[0.3em] text-orange-500 hover:text-orange-400 transition-colors border-b border-orange-500/20 pb-1">
                Explorar Biblioteca
             </Link>
          </div>
        )}
      </div>
    </div>
  );
}
