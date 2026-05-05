import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { getSession } from '@/lib/auth';
import VideoSidebarTabs from './VideoSidebarTabs';
import VideoInteractions from './VideoInteractions';
import { ArrowLeft } from 'lucide-react';
import Link from 'next/link';

async function getVideoData(id: string, userId: string) {
  const pool = await getDbConnection();
  
  const [videos] = await pool.query(`
    SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome 
    FROM videos v
    LEFT JOIN setores s ON v.setor_id = s.id
    LEFT JOIN modulos m ON v.modulo_id = m.id
    WHERE v.id = ?
  `, [id]);
  const video = (videos as any[])[0];
  if (!video) return null;

  // Busca se o user já curtiu
  let hasLiked = false;
  if (userId) {
    const [likes] = await pool.query('SELECT 1 FROM curtidas WHERE usuario_id = ? AND video_id = ?', [userId, id]);
    hasLiked = (likes as any[]).length > 0;
  }

  // Busca comentários
  const [comentarios] = await pool.query(`
    SELECT c.*, u.nome as usuario_nome 
    FROM comentarios c 
    LEFT JOIN usuarios u ON c.usuario_id = u.id 
    WHERE c.video_id = ? 
    ORDER BY c.data DESC
  `, [id]);

  let trilha = [];
  let sequencia_titulo = '';

  if (video.is_sequencia && video.sequencia_id) {
    const [t] = await pool.query('SELECT * FROM videos WHERE sequencia_id = ? ORDER BY sequencia_ordem ASC', [video.sequencia_id]);
    trilha = t as any[];
    sequencia_titulo = trilha.find(v => v.sequencia_titulo)?.sequencia_titulo || `Trilha ${video.sequencia_id}`;
  }

  // Sugestões
  const [sugestoes] = await pool.query('SELECT id, titulo, url_video FROM videos WHERE modulo_id = ? AND id != ? LIMIT 5', [video.modulo_id, id]);

  return { 
    video, 
    trilha, 
    sequencia_titulo, 
    sugestoes: sugestoes as any[],
    comentarios: comentarios as any[],
    hasLiked
  };
}

export default async function VideoPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const session = await getSession();
  const data = await getVideoData(id, session?.id);

  if (!data) {
    notFound();
  }

  const { video, trilha, sequencia_titulo, sugestoes, comentarios, hasLiked } = data;

  // Increment view (in background)
  const pool = await getDbConnection();
  pool.query('UPDATE videos SET visualizacoes = visualizacoes + 1 WHERE id = ?', [id]).catch(() => {});

  return (
    <div className="flex flex-col lg:flex-row min-h-screen bg-slate-950">
      
      {/* Coluna Principal (Player) */}
      <div className="flex-1 lg:max-w-[75%] flex flex-col h-full overflow-y-auto custom-scrollbar relative">
        {/* Header de Navegação (Flutuante) */}
        <div className="absolute top-0 left-0 w-full p-4 md:p-6 z-20 flex items-center gap-4 bg-gradient-to-b from-slate-950/80 to-transparent pointer-events-none">
          <Link href={`/modulo/${video.modulo_id}`} className="pointer-events-auto w-10 h-10 rounded-full bg-black/40 backdrop-blur-md flex items-center justify-center text-white hover:bg-orange-500 transition-colors border border-white/10">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div className="flex items-center gap-2 text-sm font-medium text-slate-300 drop-shadow-md">
            <span className="text-orange-400">{video.setor_nome || 'Sistema'}</span>
            <span className="text-slate-500">/</span>
            <span>{video.modulo_nome || 'Módulo'}</span>
          </div>
        </div>

        {/* Player Container */}
        <div className="w-full aspect-video bg-black relative">
          {video.url_video && video.url_video.includes('youtube') ? (
            <iframe 
              className="w-full h-full absolute inset-0"
              src={`https://www.youtube.com/embed/${video.url_video.split('v=')[1]}?autoplay=1&rel=0&modestbranding=1`}
              title="YouTube video player" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
              allowFullScreen
            ></iframe>
          ) : (
            <div className="w-full h-full flex items-center justify-center text-slate-500">
              Vídeo não disponível
            </div>
          )}
        </div>

        {/* Informações do Vídeo */}
        <div className="p-6 md:p-8 max-w-5xl">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h1 className="text-2xl md:text-3xl font-bold text-white mb-2">{video.titulo}</h1>
              <div className="flex items-center gap-4 text-sm text-slate-400 mb-6 font-medium">
                <span className="bg-slate-800 px-3 py-1 rounded-md">{video.visualizacoes || 0} visualizações</span>
                <span>•</span>
                <span>{new Date(video.data_upload).toLocaleDateString('pt-BR')}</span>
              </div>
            </div>
          </div>
          
          <div className="bg-slate-900/50 border border-white/5 rounded-2xl p-6 text-slate-300 whitespace-pre-wrap leading-relaxed">
            {video.descricao || 'Nenhuma descrição fornecida.'}
          </div>

          {/* Interações: Curtidas e Comentários */}
          <VideoInteractions 
            videoId={id} 
            initialLikes={video.curtidas || 0}
            hasLikedInitially={hasLiked}
            initialComments={comentarios}
          />
        </div>
      </div>

      {/* Sidebar Lateral (Abas de Trilha e IA) */}
      <VideoSidebarTabs 
        videoId={id}
        trilha={trilha}
        sequencia_titulo={sequencia_titulo}
        sugestoes={sugestoes}
      />
    </div>
  );
}
