import Link from 'next/link';
import { Sparkles, PlayCircle } from 'lucide-react';

interface ContinueCardProps {
  video: any; // should contain id, titulo, url_video, thumbnail
}

function getPreview(url: string, thumbnail?: string) {
  if (thumbnail) return { type: 'image', src: thumbnail };
  if (!url) return null;
  if (url.includes('youtube')) {
    const id = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11})/)?.[1];
    return { type: 'image', src: `https://img.youtube.com/vi/${id}/mqdefault.jpg` };
  }
  if (url.includes('drive.google.com')) {
    const id = url.match(/(?:id=|\/d\/)([0-9A-Za-z_-]{25,})/)?.[1];
    return { type: 'image', src: `https://drive.google.com/thumbnail?id=${id}&sz=w800` };
  }
  if (url.includes('/uploads/')) return { type: 'video', src: url };
  return null;
}

export default function ContinueCard({ video }: ContinueCardProps) {
  const preview = getPreview(video.url_video, video.thumbnail);
  return (
    <section className="mt-12 animate-in slide-in-from-bottom-8 duration-1000">
      <div className="group relative bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-[32px] p-8 flex flex-col md:flex-row items-center justify-between gap-8 hover:border-orange-500/40 transition-all duration-500 shadow-2xl">
        <div className="flex items-center gap-8 w-full md:w-auto">
          <div className="relative w-32 md:w-48 aspect-video rounded-2xl overflow-hidden bg-slate-800 shadow-2xl shrink-0 group-hover:scale-105 transition-transform duration-500">
            {preview?.type === 'image' && (
              <img src={preview.src} className="w-full h-full object-cover" alt="preview" />
            )}
            {preview?.type === 'video' && (
              <video src={preview.src} muted loop autoPlay className="w-full h-full object-cover" />
            )}
            <div className="absolute inset-0 bg-black/20" />
            <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
               <PlayCircle className="text-white w-10 h-10" />
            </div>
          </div>
          <div className="flex flex-col gap-2 min-w-0">
            <div className="flex items-center gap-2">
              <div className="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse" />
              <h3 className="text-orange-500 text-[10px] font-black uppercase tracking-[0.2em]">
                Continuar Assistindo
              </h3>
            </div>
            <p className="text-white font-black text-xl md:text-2xl line-clamp-1 tracking-tight">
              {video.titulo}
            </p>
            <div className="w-full h-1 bg-white/5 rounded-full overflow-hidden mt-2">
               <div className="w-2/3 h-full bg-gradient-to-r from-orange-500 to-orange-400 rounded-full" />
            </div>
          </div>
        </div>
        <Link
          href={`/video/${video.id}`}
          className="w-full md:w-auto px-10 py-5 bg-white text-slate-950 font-black rounded-2xl hover:bg-orange-500 hover:text-white transition-all text-sm uppercase tracking-widest shadow-xl active:scale-95"
        >
          Retomar Aula
        </Link>
      </div>
    </section>
  );
}
