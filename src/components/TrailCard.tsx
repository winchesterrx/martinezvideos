import Link from 'next/link';

interface TrailCardProps {
  trilha: any;
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

export default function TrailCard({ trilha }: TrailCardProps) {
  const preview = getPreview(trilha.url_video, trilha.thumbnail);
  return (
    <Link
      href={`/video/${trilha.id}`}
      className="group block relative rounded-2xl overflow-hidden aspect-video bg-slate-900 border border-white/10 shadow-2xl transition-all duration-500 hover:border-orange-500/40"
    >
      <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent z-10" />
      {preview?.type === 'image' && (
        <img src={preview.src} className="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-[2000ms] ease-out" alt="" />
      )}
      {preview?.type === 'video' && (
        <video src={preview.src} muted loop autoPlay className="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-[2000ms] ease-out" />
      )}
      <div className="absolute inset-0 z-20 p-6 flex flex-col justify-end gap-2">
        <div className="flex items-center gap-2">
          <div className="w-1 h-4 bg-orange-500 rounded-full" />
          <span className="text-[10px] font-black text-orange-400 uppercase tracking-widest">
            TRILHA DE APRENDIZADO
          </span>
        </div>
        <h3 className="text-lg font-black text-white line-clamp-2 leading-tight group-hover:text-orange-400 transition-colors">
          {trilha.titulo}
        </h3>
      </div>
    </Link>
  );
}
