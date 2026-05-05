import { getDbConnection } from '@/lib/db';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { Layers, ChevronRight, Play, Eye, Route } from 'lucide-react';

async function getModuloData(id: string) {
  const pool = await getDbConnection();
  
  const [modulos] = await pool.query(`
    SELECT m.*, s.nome as setor_nome, s.id as setor_id 
    FROM modulos m 
    LEFT JOIN setores s ON m.setor_id = s.id 
    WHERE m.id = ?
  `, [id]);
  const modulo = (modulos as any[])[0];
  
  if (!modulo) return null;

  const [videos] = await pool.query(`
    SELECT v.*, seq.titulo as sequencia_titulo
    FROM videos v
    LEFT JOIN sequencias seq ON v.sequencia_id = seq.id
    WHERE v.modulo_id = ?
    ORDER BY v.is_sequencia DESC, v.sequencia_id ASC, v.sequencia_ordem ASC, v.data_upload DESC
  `, [id]);

  // Group videos by sequencia_id, and standalone videos
  const sequencias: Record<string, any> = {};
  const avulsos: any[] = [];

  for (const v of videos as any[]) {
    if (v.is_sequencia && v.sequencia_id) {
      if (!sequencias[v.sequencia_id]) {
        sequencias[v.sequencia_id] = {
          titulo: v.sequencia_titulo || `Trilha ${v.sequencia_id}`,
          videos: []
        };
      }
      sequencias[v.sequencia_id].videos.push(v);
    } else {
      avulsos.push(v);
    }
  }

  return { 
    modulo, 
    sequencias,
    avulsos
  };
}

export default async function ModuloPage({ params }: { params: { id: string } }) {
  const data = await getModuloData(params.id);
  
  if (!data) {
    notFound();
  }

  const { modulo, sequencias, avulsos } = data;

  const renderVideoCard = (video: any, index?: number) => (
    <Link href={`/video/${video.id}`} key={video.id} className="group flex flex-col bg-slate-900/40 border border-white/5 rounded-2xl overflow-hidden hover:bg-slate-800/60 hover:border-orange-500/30 transition-all duration-300 shadow-lg">
      <div className="relative aspect-video bg-slate-800 w-full overflow-hidden">
        {video.poster_url ? (
          <img src={video.poster_url} alt={video.titulo} className="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" />
        ) : (
          <div className="w-full h-full flex items-center justify-center bg-slate-800 text-slate-500">
            <Play size={48} className="opacity-30 group-hover:scale-110 group-hover:opacity-60 transition-all" />
          </div>
        )}
        
        {index !== undefined && (
          <div className="absolute top-3 left-3 bg-indigo-600 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-white shadow-lg">
            Aula {index + 1}
          </div>
        )}
        
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
        <div className="flex items-center justify-between mt-4 text-xs text-slate-500">
          <span className="flex items-center gap-1"><Eye size={14} /> {video.visualizacoes || 0}</span>
          <span>{new Date(video.data_upload).toLocaleDateString('pt-BR')}</span>
        </div>
      </div>
    </Link>
  );

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-12">
      {/* Breadcrumb & Header */}
      <div className="flex flex-col gap-4">
        <div className="flex items-center gap-2 text-sm text-slate-400 font-medium">
          <Link href="/" className="hover:text-white transition-colors">Início</Link>
          <ChevronRight size={16} />
          <Link href={`/sistema/${modulo.setor_id}`} className="hover:text-white transition-colors">{modulo.setor_nome}</Link>
          <ChevronRight size={16} />
          <span className="text-orange-500">{modulo.nome}</span>
        </div>
        
        <div className="flex items-center gap-4">
          <div 
            className="w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/20 border border-white/10"
            style={{ backgroundColor: modulo.cor ? `${modulo.cor}20` : '#4f46e520', color: modulo.cor || '#818cf8' }}
          >
            <Layers size={32} />
          </div>
          <div>
            <h1 className="text-3xl font-extrabold text-white">
              {modulo.nome}
            </h1>
            <p className="text-slate-400 mt-1">{modulo.descricao || 'Módulo de treinamento'}</p>
          </div>
        </div>
      </div>

      {/* Sequências (Trilhas) */}
      {Object.values(sequencias).length > 0 && (
        <div className="space-y-10">
          {Object.values(sequencias).map((seq: any, i) => (
            <div key={i} className="bg-slate-900/30 border border-white/5 rounded-3xl p-6 md:p-8">
              <div className="flex items-center gap-3 mb-6">
                <Route className="text-indigo-500" size={24} />
                <h2 className="text-2xl font-bold text-white">
                  Trilha: {seq.titulo}
                </h2>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 relative">
                {/* Linha de conexão visual da trilha (apenas Desktop) */}
                <div className="hidden lg:block absolute top-[40%] left-0 w-full h-0.5 bg-indigo-500/20 -z-10" />
                
                {seq.videos.map((video: any, index: number) => renderVideoCard(video, index))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Vídeos Avulsos */}
      {avulsos.length > 0 && (
        <div>
          <h2 className="text-2xl font-bold text-white mb-6">Outros Vídeos do Módulo</h2>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {avulsos.map((video: any) => renderVideoCard(video))}
          </div>
        </div>
      )}

      {Object.values(sequencias).length === 0 && avulsos.length === 0 && (
        <div className="bg-slate-900/50 border border-white/5 rounded-2xl p-12 text-center text-slate-400">
          Nenhum vídeo publicado neste módulo ainda.
        </div>
      )}
    </div>
  );
}
