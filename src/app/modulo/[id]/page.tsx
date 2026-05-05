import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import Link from 'next/link';
import { ArrowLeft, Video, FolderOpen, PlayCircle, Clock } from 'lucide-react';

async function getModuloData(id: string) {
  const pool = await getDbConnection();
  
  // Buscar o módulo atual
  const [modulos] = await pool.query(`
    SELECT m.*, s.nome as setor_nome, s.id as setor_id 
    FROM modulos m
    JOIN setores s ON m.setor_id = s.id
    WHERE m.id = ? AND m.ativo = 'S'
  `, [id]);
  
  const modulo = (modulos as any[])[0];
  if (!modulo) return null;

  // Buscar todos os vídeos deste módulo
  const [videosData] = await pool.query('SELECT * FROM videos WHERE modulo_id = ? ORDER BY id DESC', [id]);
  const videos = videosData as any[];

  // Separar em Sequências vs Avulsos
  const sequencias: Record<string, { titulo: string; videos: any[] }> = {};
  const avulsos: any[] = [];

  videos.forEach((v) => {
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
  });

  // Ordenar vídeos dentro das sequências pela coluna sequencia_ordem
  Object.values(sequencias).forEach(seq => {
    seq.videos.sort((a, b) => (a.sequencia_ordem || 0) - (b.sequencia_ordem || 0));
  });

  return { modulo, sequencias, avulsos };
}

export default async function ModuloPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const data = await getModuloData(id);
  
  if (!data) {
    notFound();
  }

  const { modulo, sequencias, avulsos } = data;
  const temConteudo = Object.keys(sequencias).length > 0 || avulsos.length > 0;

  return (
    <div className="min-h-screen bg-slate-950 text-white relative">
      {/* Background Mask */}
      <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-fixed bg-center opacity-5 pointer-events-none z-0" />
      <div className="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-950 to-slate-950 pointer-events-none z-0" />

      <div className="relative z-10 px-4 md:px-8 py-8 max-w-7xl mx-auto">
        
        {/* Breadcrumb / Header */}
        <div className="mb-10">
          <Link href={`/sistema/${modulo.setor_id}`} className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-orange-500 transition-colors mb-6">
            <ArrowLeft className="w-4 h-4" />
            Voltar para {modulo.setor_nome}
          </Link>
          
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-2xl bg-slate-800 border border-orange-500/30 flex items-center justify-center shadow-lg">
              <FolderOpen className="w-8 h-8 text-orange-500" />
            </div>
            <div>
              <h4 className="text-orange-500 font-bold tracking-widest text-sm uppercase mb-1">Módulo</h4>
              <h1 className="text-3xl md:text-4xl font-extrabold text-white">{modulo.nome}</h1>
            </div>
          </div>
        </div>

        {!temConteudo ? (
          <div className="bg-slate-900/50 backdrop-blur-sm border border-white/5 rounded-2xl p-12 text-center">
            <Video className="w-12 h-12 text-slate-600 mx-auto mb-4" />
            <h3 className="text-xl font-bold text-slate-300 mb-2">Nenhum conteúdo encontrado</h3>
            <p className="text-slate-500">Este módulo ainda não possui trilhas ou vídeos disponíveis.</p>
          </div>
        ) : (
          <div className="space-y-12">
            {/* TRILHAS / SEQUÊNCIAS */}
            {Object.entries(sequencias).map(([seqId, seq]) => (
              <section key={seqId}>
                <div className="flex items-center gap-3 mb-6 border-b border-white/5 pb-4">
                  <PlayCircle className="w-6 h-6 text-orange-500" />
                  <h2 className="text-2xl font-bold text-white">{seq.titulo}</h2>
                  <span className="ml-auto text-xs font-semibold bg-orange-500/20 text-orange-400 px-3 py-1 rounded-full border border-orange-500/20">
                    Trilha
                  </span>
                </div>
                
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                  {seq.videos.map((video, index) => (
                    <Link key={video.id} href={`/video/${video.id}`} className="group relative bg-slate-900/40 rounded-xl border border-white/5 overflow-hidden hover:border-orange-500/30 transition-all shadow-md hover:shadow-orange-500/10 block">
                      <div className="aspect-video bg-slate-800 relative overflow-hidden">
                        {video.url_video && video.url_video.includes('youtube') ? (
                          <img src={`https://img.youtube.com/vi/${video.url_video.split('v=')[1]}/mqdefault.jpg`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={video.titulo} />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center bg-slate-800 group-hover:scale-105 transition-transform duration-500">
                            <Video className="w-8 h-8 text-slate-600" />
                          </div>
                        )}
                        <div className="absolute inset-0 bg-black/40 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                           <PlayCircle className="w-12 h-12 text-white/80 group-hover:text-white group-hover:scale-110 transition-all drop-shadow-lg" />
                        </div>
                        {/* Indicador de Ordem na Trilha */}
                        <div className="absolute top-2 left-2 bg-slate-950/80 backdrop-blur-md text-white text-xs font-bold px-2 py-1 rounded-md border border-white/10">
                          Aula {index + 1}
                        </div>
                      </div>
                      <div className="p-4">
                        <h3 className="text-sm font-bold text-slate-200 line-clamp-2 group-hover:text-orange-400 transition-colors">{video.titulo}</h3>
                        <div className="flex items-center gap-2 mt-3 text-xs text-slate-500">
                          <Clock className="w-3 h-3" />
                          {video.visualizacoes || 0} visualizações
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              </section>
            ))}

            {/* VÍDEOS AVULSOS */}
            {avulsos.length > 0 && (
              <section>
                <div className="flex items-center gap-3 mb-6 border-b border-white/5 pb-4">
                  <Video className="w-6 h-6 text-slate-400" />
                  <h2 className="text-2xl font-bold text-white">Conteúdos Extras</h2>
                </div>
                
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                  {avulsos.map((video) => (
                    <Link key={video.id} href={`/video/${video.id}`} className="group relative bg-slate-900/40 rounded-xl border border-white/5 overflow-hidden hover:border-slate-500/50 transition-all shadow-md block">
                      <div className="aspect-video bg-slate-800 relative overflow-hidden">
                        {video.url_video && video.url_video.includes('youtube') ? (
                          <img src={`https://img.youtube.com/vi/${video.url_video.split('v=')[1]}/mqdefault.jpg`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80 group-hover:opacity-100" alt={video.titulo} />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center bg-slate-800 group-hover:scale-105 transition-transform duration-500">
                            <Video className="w-8 h-8 text-slate-600" />
                          </div>
                        )}
                        <div className="absolute inset-0 bg-black/40 group-hover:bg-transparent transition-colors" />
                      </div>
                      <div className="p-4">
                        <h3 className="text-sm font-semibold text-slate-300 line-clamp-2 group-hover:text-white transition-colors">{video.titulo}</h3>
                      </div>
                    </Link>
                  ))}
                </div>
              </section>
            )}

          </div>
        )}
      </div>
    </div>
  );
}
