import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import { ArrowLeft, Clock, Eye, Sparkles } from 'lucide-react';
import Link from 'next/link';
import AIChatClient from './AIChatClient';

async function getVideo(id: string) {
  const pool = await getDbConnection();
  const [rows]: any = await pool.query(
    `SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome 
     FROM videos v
     LEFT JOIN setores s ON v.setor_id = s.id
     LEFT JOIN modulos m ON v.modulo_id = m.id
     WHERE v.id = ?`,
    [id]
  );
  return rows[0] || null;
}

export default async function VideoPage({ params }: { params: { id: string } }) {
  const video = await getVideo(params.id);

  if (!video) {
    notFound();
  }

  // Increment view (in background)
  const pool = await getDbConnection();
  pool.query('UPDATE videos SET visualizacoes = visualizacoes + 1 WHERE id = ?', [params.id]).catch(() => {});

  return (
    <div className="flex flex-col lg:flex-row min-h-screen bg-slate-950">
      {/* Video Section */}
      <div className="flex-1 p-4 lg:p-6 lg:overflow-y-auto">
        <Link href="/" className="inline-flex items-center gap-2 text-slate-400 hover:text-orange-500 mb-6 transition-colors">
          <ArrowLeft size={18} /> Voltar
        </Link>
        
        <div className="w-full bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/5 aspect-video relative">
          {/* Legacy PHP stored URLs in url_video. If it's relative, we prepend /uploads or similar, but since we moved things, we might just assume it's an iframe or standard HTML5 video. Let's use standard video tag. */}
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
            <span className="bg-orange-500/20 text-orange-500 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
              {video.setor_nome || 'Geral'}
            </span>
            {video.modulo_nome && (
              <span className="bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                Módulo: {video.modulo_nome}
              </span>
            )}
          </div>
          
          <h1 className="text-2xl md:text-3xl font-bold text-white">{video.titulo}</h1>
          
          <div className="flex items-center gap-6 text-sm text-slate-400 border-b border-white/5 pb-6">
            <span className="flex items-center gap-2"><Eye size={16} /> {video.visualizacoes + 1} views</span>
            <span className="flex items-center gap-2"><Clock size={16} /> {new Date(video.data_upload).toLocaleDateString('pt-BR')}</span>
          </div>

          <div className="pt-4 text-slate-300 leading-relaxed whitespace-pre-wrap">
            {video.descricao || 'Nenhuma descrição fornecida para esta aula.'}
          </div>
        </div>
      </div>

      {/* AI Tutor Sidebar Section */}
      <div className="w-full lg:w-[400px] bg-slate-900 border-l border-white/5 flex flex-col h-[600px] lg:h-screen lg:sticky lg:top-0">
        <div className="p-4 border-b border-white/5 flex items-center gap-3 bg-gradient-to-r from-slate-900 to-indigo-950/30">
          <div className="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
            <Sparkles className="text-white" size={20} />
          </div>
          <div>
            <h3 className="font-semibold text-white">Tutor de IA</h3>
            <p className="text-xs text-indigo-300">Responde dúvidas sobre esta aula</p>
          </div>
        </div>
        
        <AIChatClient videoContext={video} />
      </div>
    </div>
  );
}
