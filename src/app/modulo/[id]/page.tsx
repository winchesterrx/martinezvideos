import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { Metadata } from 'next';
import Link from 'next/link';
import { ArrowLeft, Video, FolderOpen, PlayCircle, Clock } from 'lucide-react';
import { getSession } from '@/lib/auth';
import VideoManagerActions from './VideoManagerActions';

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

  // Buscar todas as trilhas deste módulo
  const [trilhasData] = await pool.query('SELECT * FROM trilhas WHERE modulo_id = ?', [id]);
  const trilhasInfo = trilhasData as any[];

  // Separar em Sequências vs Avulsos
  const sequencias: Record<string, { titulo: string; videos: any[] }> = {};
  const avulsos: any[] = [];

  videos.forEach((v) => {
    if (v.is_sequencia && v.sequencia_id) {
      if (!sequencias[v.sequencia_id]) {
        const info = trilhasInfo.find(t => t.id === v.sequencia_id);
        sequencias[v.sequencia_id] = {
          titulo: info?.nome || `Trilha ${v.sequencia_id}`,
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
  const session = await getSession();
  const data = await getModuloData(id);
  
  if (!data) {
    notFound();
  }

  const { modulo, sequencias, avulsos } = data;
  const temConteudo = Object.keys(sequencias).length > 0 || avulsos.length > 0;

  const getVideoId = (url: string) => {
    if (!url) return null;
    const ytMatch = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    if (ytMatch) return { type: 'youtube', id: ytMatch[1] };
    
    const driveMatch = url.match(/(?:id=|\/d\/)([0-9A-Za-z_-]{25,})/);
    if (driveMatch) return { type: 'drive', id: driveMatch[1] };

    if (url.includes('/uploads/')) return { type: 'local', id: url };
    
    return null;
  };

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
                  {seq.videos.map((video, index) => {
                    const info = getVideoId(video.url_video);
                    let thumbUrl = video.thumbnail;
                    
                    if (!thumbUrl) {
                       if (info?.type === 'youtube') thumbUrl = `https://img.youtube.com/vi/${info.id}/maxresdefault.jpg`;
                       if (info?.type === 'drive') thumbUrl = `https://drive.google.com/thumbnail?id=${info.id}&sz=w800`;
                    }
                    
                    return (
                      <div key={video.id} className="group relative">
                        <VideoManagerActions videoId={video.id} videoTitle={video.titulo} isAdmin={session?.adm === 'S'} />
                        <Link href={`/video/${video.id}`} className="block bg-slate-900/40 rounded-xl border border-white/5 overflow-hidden hover:border-orange-500/30 transition-all shadow-md hover:shadow-orange-500/10">
                          <div className="aspect-video bg-slate-800 relative overflow-hidden flex items-center justify-center">
                            {thumbUrl ? (
                              <img src={thumbUrl} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt={video.titulo} />
                            ) : info?.type === 'local' ? (
                              <video 
                                src={info.id} 
                                muted 
                                loop 
                                autoPlay 
                                playsInline 
                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                              />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center bg-slate-900">
                                 <Video className="w-8 h-8 text-slate-700" />
                              </div>
                            )}
                            <div className="absolute inset-0 bg-black/40 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                               <PlayCircle className="w-12 h-12 text-white/80 group-hover:text-white group-hover:scale-110 transition-all drop-shadow-lg" />
                            </div>
                            <div className="absolute top-2 left-2 bg-slate-950/80 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg border border-white/10">
                              Aula {index + 1}
                            </div>
                          </div>
                          <div className="p-4">
                            <h3 className="text-sm font-bold text-slate-200 line-clamp-2 group-hover:text-orange-400 transition-colors">{video.titulo}</h3>
                            <div className="flex items-center gap-2 mt-3 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                              <Clock className="w-3 h-3" />
                              {video.visualizacoes || 0} visualizações
                            </div>
                          </div>
                        </Link>
                      </div>
                    );
                  })}
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
                  {avulsos.map((video) => {
                    const info = getVideoId(video.url_video);
                    let thumbUrl = video.thumbnail;
                    
                    if (!thumbUrl) {
                       if (info?.type === 'youtube') thumbUrl = `https://img.youtube.com/vi/${info.id}/maxresdefault.jpg`;
                       if (info?.type === 'drive') thumbUrl = `https://drive.google.com/thumbnail?id=${info.id}&sz=w800`;
                    }

                    return (
                      <div key={video.id} className="group relative">
                        <VideoManagerActions videoId={video.id} videoTitle={video.titulo} isAdmin={session?.adm === 'S'} />
                        <Link href={`/video/${video.id}`} className="block bg-slate-900/40 rounded-xl border border-white/5 overflow-hidden hover:border-slate-500/50 transition-all shadow-md">
                          <div className="aspect-video bg-slate-800 relative overflow-hidden flex items-center justify-center">
                            {thumbUrl ? (
                              <img src={thumbUrl} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80 group-hover:opacity-100" alt={video.titulo} />
                            ) : info?.type === 'local' ? (
                              <video 
                                src={info.id} 
                                muted 
                                loop 
                                autoPlay 
                                playsInline 
                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-80 group-hover:opacity-100"
                              />
                            ) : (
                              <div className="w-full h-full flex items-center justify-center bg-slate-900">
                                 <Video className="w-8 h-8 text-slate-700" />
                              </div>
                            )}
                            <div className="absolute inset-0 bg-black/40 group-hover:bg-transparent transition-colors flex items-center justify-center">
                               <PlayCircle className="w-10 h-10 text-white/40 group-hover:text-white transition-all opacity-0 group-hover:opacity-100" />
                            </div>
                          </div>
                          <div className="p-4">
                            <h3 className="text-sm font-semibold text-slate-300 line-clamp-2 group-hover:text-white transition-colors">{video.titulo}</h3>
                          </div>
                        </Link>
                      </div>
                    );
                  })}
                </div>
              </section>
            )}

          </div>
        )}
      </div>
    </div>
  );
}
