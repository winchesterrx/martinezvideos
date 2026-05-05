import { getDbConnection } from '@/lib/db';
import Link from 'next/link';
import { Play, Star, Layers, ArrowRight } from 'lucide-react';
import { getSession } from '@/lib/auth';

async function getDashboardData() {
  const pool = await getDbConnection();
  
  // Get active sectors (Sistemas)
  const [setores] = await pool.query(`
    SELECT id, nome FROM setores WHERE ativo = 'S' ORDER BY nome ASC
  `);
  
  // Get recommended/highlighted videos
  const [destaques] = await pool.query(`
    SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome 
    FROM videos v
    LEFT JOIN setores s ON v.setor_id = s.id
    LEFT JOIN modulos m ON v.modulo_id = m.id
    WHERE v.recomendado = 1
    ORDER BY v.data_upload DESC
    LIMIT 4
  `);

  return { 
    setores: setores as any[], 
    destaques: destaques as any[] 
  };
}

export default async function Home() {
  const session = await getSession();
  const { setores, destaques } = await getDashboardData();

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-12">
      
      {/* Hero Header with Texture and Mask */}
      <div className="relative rounded-3xl overflow-hidden shadow-2xl border border-white/10 min-h-[300px] flex items-center">
        {/* Background Image/Texture */}
        <div 
          className="absolute inset-0 bg-cover bg-center"
          style={{ backgroundImage: "url('https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=2000&auto=format&fit=crop')" }}
        />
        {/* Dark Overlay Mask */}
        <div className="absolute inset-0 bg-slate-950/70 backdrop-blur-[2px]" />
        
        {/* Content */}
        <div className="relative z-10 p-8 sm:p-12 max-w-3xl">
          <h1 className="text-4xl sm:text-5xl font-extrabold text-white mb-4 drop-shadow-lg">
            Bem-vindo ao Conhecimento, <span className="text-orange-400">{session?.nome?.split(' ')[0]}</span>.
          </h1>
          <p className="text-lg text-slate-200/90 mb-8 font-medium leading-relaxed drop-shadow-md">
            Acesse seus sistemas, explore os módulos disponíveis e continue a sua trilha de aprendizado de forma organizada e focada.
          </p>
        </div>
      </div>

      {/* Meus Sistemas (Setores) */}
      <div>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-white flex items-center gap-3">
            <Layers className="text-orange-500" /> Nossos Sistemas
          </h2>
        </div>

        {setores.length === 0 ? (
          <div className="bg-slate-900/50 border border-white/5 rounded-2xl p-12 text-center text-slate-400">
            Nenhum sistema disponível no momento.
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {setores.map((setor, index) => (
              <Link 
                href={`/sistema/${setor.id}`} 
                key={setor.id} 
                className="group relative rounded-2xl overflow-hidden border border-white/10 hover:border-orange-500/50 transition-all duration-300 h-40 flex items-end shadow-xl"
              >
                {/* Abstract CSS Pattern based on index to give variety */}
                <div className={`absolute inset-0 opacity-40 group-hover:opacity-60 transition-opacity bg-gradient-to-br ${
                  index % 3 === 0 ? 'from-blue-600 to-indigo-900' :
                  index % 3 === 1 ? 'from-orange-600 to-red-900' :
                  'from-emerald-600 to-teal-900'
                }`} />
                <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent" />
                
                <div className="relative z-10 p-5 w-full flex justify-between items-end">
                  <div>
                    <span className="text-xs font-bold uppercase tracking-wider text-white/50 mb-1 block">Sistema</span>
                    <h3 className="text-xl font-bold text-white group-hover:text-orange-300 transition-colors">
                      {setor.nome}
                    </h3>
                  </div>
                  <div className="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center group-hover:bg-orange-500 transition-colors">
                    <ArrowRight className="text-white" size={20} />
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>

      {/* Vídeos em Destaque */}
      {destaques.length > 0 && (
        <div>
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-white flex items-center gap-3">
              <Star className="text-orange-500" fill="currentColor" /> Vídeos em Destaque
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {destaques.map((video) => (
              <Link href={`/video/${video.id}`} key={video.id} className="group flex flex-col bg-slate-900/40 border border-white/5 rounded-2xl overflow-hidden hover:bg-slate-800/60 hover:border-orange-500/30 transition-all duration-300 shadow-lg">
                <div className="relative aspect-video bg-slate-800 w-full overflow-hidden">
                  {video.poster_url ? (
                    <img src={video.poster_url} alt={video.titulo} className="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center bg-slate-800 text-slate-500">
                      <Play size={48} className="opacity-30 group-hover:scale-110 group-hover:opacity-60 transition-all" />
                    </div>
                  )}
                  {/* Badge Setor/Modulo */}
                  <div className="absolute top-3 left-3 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full text-xs font-medium text-white border border-white/10">
                    {video.setor_nome || 'Destaque'}
                  </div>
                  {/* Play Button Overlay */}
                  <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                    <div className="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center shadow-lg shadow-orange-500/50">
                      <Play className="text-white ml-1" fill="currentColor" size={20} />
                    </div>
                  </div>
                </div>
                <div className="p-4 flex-1 flex flex-col justify-between">
                  <h3 className="text-white font-semibold line-clamp-2 leading-tight group-hover:text-orange-400 transition-colors">
                    {video.titulo}
                  </h3>
                  {video.modulo_nome && (
                    <p className="text-xs text-slate-400 mt-3 font-medium">
                      Módulo: {video.modulo_nome}
                    </p>
                  )}
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
