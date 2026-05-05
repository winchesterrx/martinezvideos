import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, Clock, Eye, Sparkles, Route, PlayCircle } from 'lucide-react';
import Link from 'next/link';
import AIChatClient from './AIChatClient';
import VideoSidebarTabs from './VideoSidebarTabs'; // We will create this

async function getVideoData(id: string) {
  const pool = await getDbConnection();
  
  // Get current video
  const [rows]: any = await pool.query(
    `SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome 
     FROM videos v
     LEFT JOIN setores s ON v.setor_id = s.id
     LEFT JOIN modulos m ON v.modulo_id = m.id
     WHERE v.id = ?`,
    [id]
  );
  const video = rows[0] || null;

  if (!video) return null;

  // Se for sequência, pega a trilha completa
  let trilha = [];
  let sequencia_titulo = '';
  if (video.is_sequencia && video.sequencia_id) {
    const [trilhaRows]: any = await pool.query(
      `SELECT id, titulo, url_video, sequencia_ordem, poster_url 
       FROM videos 
       WHERE sequencia_id = ? 
       ORDER BY sequencia_ordem ASC`,
      [video.sequencia_id]
    );
    trilha = trilhaRows;

    const [seqTitleRows]: any = await pool.query(
      'SELECT titulo FROM sequencias WHERE id = ?', [video.sequencia_id]
    );
    if (seqTitleRows[0]) sequencia_titulo = seqTitleRows[0].titulo;
  }

  // Sugestões fora da sequência (do mesmo módulo, ou destaques)
  const [sugestoes]: any = await pool.query(
    `SELECT id, titulo, poster_url, visualizacoes 
     FROM videos 
     WHERE modulo_id = ? AND id != ? ${video.sequencia_id ? 'AND (sequencia_id != ? OR sequencia_id IS NULL)' : ''}
     ORDER BY recomendado DESC, data_upload DESC 
     LIMIT 4`,
    video.sequencia_id ? [video.modulo_id, id, video.sequencia_id] : [video.modulo_id, id]
  );

  return { video, trilha, sequencia_titulo, sugestoes };
}

export default async function VideoPage({ params }: { params: { id: string } }) {
  const data = await getVideoData(params.id);

  if (!data) {
    notFound();
  }

  const { video, trilha, sequencia_titulo, sugestoes } = data;

  // Increment view (in background)
  const pool = await getDbConnection();
  pool.query('UPDATE videos SET visualizacoes = visualizacoes + 1 WHERE id = ?', [params.id]).catch(() => {});

  return (
    <div className="flex flex-col lg:flex-row min-h-screen bg-slate-950">
      {/* Coluna Principal (Player e Detalhes) */}
      <div className="flex-1 p-4 lg:p-6 lg:overflow-y-auto">
        <Link href={video.modulo_id ? `/modulo/${video.modulo_id}` : '/'} className="inline-flex items-center gap-2 text-slate-400 hover:text-orange-500 mb-6 transition-colors">
          <ArrowLeft size={18} /> Voltar para o Módulo
        </Link>
        
        <div className="w-full bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10 aspect-video relative">
          {video.url_video.includes('youtube') || video.url_video.includes('vimeo') ? (
            <iframe 
              src={video.url_video} 
              className="w-full h-full" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowFullScreen
            />
          ) : (
            <video 
              src={`/${video.url_video}`} 
              controls 
              className="w-full h-full"
              poster={video.poster_url || ''}
            />
          )}
        </div>

        <div className="mt-6 space-y-4">
          <div className="flex flex-wrap items-center gap-3">
            <span className="bg-orange-500/20 border border-orange-500/30 text-orange-400 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
              {video.setor_nome || 'Geral'}
            </span>
            {video.modulo_nome && (
              <span className="bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                Módulo: {video.modulo_nome}
              </span>
            )}
            {video.is_sequencia && (
              <span className="bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider flex items-center gap-1">
                <Route size={14} /> Trilha: {sequencia_titulo || 'Sequência Lógica'}
              </span>
            )}
          </div>
          
          <h1 className="text-2xl md:text-4xl font-extrabold text-white leading-tight">{video.titulo}</h1>
          
          <div className="flex items-center gap-6 text-sm text-slate-400 border-b border-white/5 pb-6">
            <span className="flex items-center gap-2"><Eye size={16} /> {video.visualizacoes + 1} views</span>
            <span className="flex items-center gap-2"><Clock size={16} /> {new Date(video.data_upload).toLocaleDateString('pt-BR')}</span>
          </div>

          <div className="pt-4 text-slate-300 leading-relaxed whitespace-pre-wrap text-lg">
            {video.descricao || 'Nenhuma descrição fornecida para esta aula.'}
          </div>
        </div>

        {/* Sugestões Relacionadas */}
        {sugestoes.length > 0 && (
          <div className="mt-12 pt-8 border-t border-white/5">
            <h3 className="text-xl font-bold text-white mb-6">Sugestões do Módulo</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
              {sugestoes.map((sug: any) => (
                <Link href={`/video/${sug.id}`} key={sug.id} className="group flex flex-col bg-slate-900 border border-white/5 rounded-xl overflow-hidden hover:border-orange-500/50 transition-colors">
                  <div className="aspect-video bg-slate-800 relative">
                    {sug.poster_url ? (
                      <img src={sug.poster_url} className="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity" />
                    ) : (
                      <div className="flex items-center justify-center w-full h-full text-slate-600">
                        <PlayCircle size={32} />
                      </div>
                    )}
                  </div>
                  <div className="p-3">
                    <h4 className="text-sm font-semibold text-white line-clamp-2 group-hover:text-orange-400">{sug.titulo}</h4>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Coluna Lateral (Trilha & Tutor IA) */}
      <div className="w-full lg:w-[400px] bg-slate-900 border-l border-white/5 flex flex-col h-[600px] lg:h-screen lg:sticky lg:top-0">
        <VideoSidebarTabs 
          video={video} 
          trilha={trilha} 
          sequencia_titulo={sequencia_titulo} 
        />
      </div>
    </div>
  );
}
