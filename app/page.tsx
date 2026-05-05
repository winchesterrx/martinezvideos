import { getDbConnection } from '@/lib/db';
import Link from 'next/link';
import { Play, Clock, Eye } from 'lucide-react';
import { getSession } from '@/lib/auth';

async function getVideos(searchParams: any) {
  const pool = await getDbConnection();
  let query = `
    SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome 
    FROM videos v
    LEFT JOIN setores s ON v.setor_id = s.id
    LEFT JOIN modulos m ON v.modulo_id = m.id
    ORDER BY v.data_upload DESC
    LIMIT 12
  `;
  const [rows] = await pool.query(query);
  return rows as any[];
}

export default async function Home({ searchParams }: { searchParams: any }) {
  const session = await getSession();
  const videos = await getVideos(searchParams);

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-8">
      {/* Hero Header */}
      <div className="relative rounded-3xl overflow-hidden bg-gradient-to-br from-orange-600 to-indigo-900 p-8 sm:p-12 shadow-2xl border border-white/10">
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-orange-500/20 rounded-full blur-[100px] pointer-events-none" />
        <div className="relative z-10 max-w-2xl">
          <h1 className="text-4xl sm:text-5xl font-extrabold text-white mb-4">
            Bem-vindo de volta, {session?.nome?.split(' ')[0]}!
          </h1>
          <p className="text-lg text-orange-100/80 mb-8">
            Continue seu aprendizado ou explore novos módulos na plataforma premium.
          </p>
        </div>
      </div>

      {/* Seção de Vídeos Recentes */}
      <div>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-white flex items-center gap-2">
            <Clock className="text-orange-500" /> Adicionados Recentemente
          </h2>
        </div>

        {videos.length === 0 ? (
          <div className="bg-slate-900/50 border border-white/5 rounded-2xl p-12 text-center text-slate-400">
            Nenhum vídeo encontrado.
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {videos.map((video) => (
              <Link href={`/video/${video.id}`} key={video.id} className="group flex flex-col bg-slate-900/40 border border-white/5 rounded-2xl overflow-hidden hover:bg-slate-800/60 hover:border-orange-500/30 transition-all duration-300">
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
                    {video.setor_nome || 'Geral'}
                  </div>
                  {/* Play Button Overlay */}
                  <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
                    <div className="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center shadow-lg shadow-orange-500/50">
                      <Play className="text-white ml-1" fill="currentColor" size={20} />
                    </div>
                  </div>
                </div>
                <div className="p-4 flex-1 flex flex-col justify-between">
                  <div>
                    <h3 className="text-white font-semibold line-clamp-2 leading-tight group-hover:text-orange-400 transition-colors">
                      {video.titulo}
                    </h3>
                    {video.modulo_nome && (
                      <p className="text-xs text-slate-400 mt-2 font-medium">
                        Módulo: {video.modulo_nome}
                      </p>
                    )}
                  </div>
                  <div className="flex items-center justify-between mt-4 text-xs text-slate-500">
                    <span className="flex items-center gap-1"><Eye size={14} /> {video.visualizacoes || 0}</span>
                    <span>{new Date(video.data_upload).toLocaleDateString('pt-BR')}</span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
