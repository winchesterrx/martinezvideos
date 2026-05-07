import Link from 'next/link';
import { PlayCircle } from 'lucide-react';

interface VideoCardProps {
  video: any;
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

export default function VideoCard({ video }: VideoCardProps) {
  const preview = getPreview(video.url_video, video.thumbnail);
  return (
    <Link
      href={`/video/${video.id}`}
      className="group bg-slate-900/20 backdrop-blur-md rounded-2xl border border-white/5 overflow-hidden hover:border-orange-500/30 transition-all duration-500 shadow-xl"
    >
      <div className="aspect-video bg-slate-800 relative overflow-hidden">
        {preview?.type === 'image' && (
          <img src={preview.src} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt={video.titulo} />
        )}
        {preview?.type === 'video' && (
          <video src={preview.src} muted loop autoPlay className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent opacity-60" />
      </div>
      <div className="p-4 space-y-2">
        <h3 className="text-sm font-bold text-slate-200 line-clamp-2 group-hover:text-orange-400 transition-colors">
          {video.titulo}
        </h3>
        <div className="flex items-center justify-between">
          <span className="text-[9px] font-black text-slate-500 uppercase tracking-widest">
            {video.setor || 'Geral'}
          </span>
          <div className="w-6 h-6 rounded-full bg-white/5 flex items-center justify-center group-hover:bg-orange-500 transition-colors">
            <PlayCircle size={12} className="text-slate-400 group-hover:text-white" />
          </div>
        </div>
      </div>
    </Link>
  );
}
