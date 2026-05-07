import Link from 'next/link';
import { PlayCircle, Compass } from 'lucide-react';

interface HeroProps {
  live?: any;
  config: { [key: string]: string };
}

export default function Hero({ live, config }: HeroProps) {
  return (
    <section className="mt-8 animate-in fade-in slide-in-from-top-4 duration-1000">
      <div className="relative rounded-[32px] overflow-hidden bg-slate-950 border border-white/5 group shadow-[0_0_50px_rgba(0,0,0,0.5)]">
        {/* Background Layer with Panoramic Effect */}
        <div className="absolute inset-0 z-0">
          {live && live.url && live.url.includes('youtube') ? (
            <img
              src={`https://img.youtube.com/vi/${live.url.split('v=')[1]}/maxresdefault.jpg`}
              className="w-full h-full object-cover opacity-40 group-hover:scale-105 transition-transform duration-[3000ms]"
              alt=""
            />
          ) : config.home_hero_video_id ? (
            <img 
              src={`https://img.youtube.com/vi/${config.home_hero_video_id.includes('v=') ? config.home_hero_video_id.split('v=')[1].split('&')[0] : config.home_hero_video_id}/maxresdefault.jpg`} 
              className="w-full h-full object-cover opacity-30 group-hover:scale-105 transition-transform duration-[3000ms]" 
              alt="" 
            />
          ) : (
            <div className="w-full h-full bg-gradient-to-br from-slate-900 via-orange-950/20 to-slate-950" />
          )}
          <div className="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/40 to-transparent" />
        </div>

        {/* Content Layer - More Compact/Rectangular */}
        <div className="relative z-10 p-8 md:p-16 flex flex-col md:flex-row items-center justify-between gap-12">
          <div className="flex flex-col items-start gap-6 max-w-2xl">
            {live ? (
              <div className="flex items-center gap-3 px-4 py-1.5 bg-red-500/10 border border-red-500/20 rounded-full">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75" />
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500" />
                </span>
                <span className="text-red-500 font-black uppercase tracking-[0.2em] text-[9px]">Transmissão Ativa</span>
              </div>
            ) : (
              <div className="px-4 py-1.5 bg-orange-500/10 border border-orange-500/20 rounded-full text-orange-500 font-black uppercase tracking-[0.2em] text-[9px]">
                Conteúdo de Elite
              </div>
            )}

            <div className="space-y-4">
              <h1 className="text-4xl md:text-6xl font-black leading-tight tracking-tight text-transparent bg-clip-text bg-gradient-to-br from-white via-white to-orange-200/50" style={{ fontFamily: "'Outfit', sans-serif" }}>
                {live ? live.titulo : (config.home_hero_titulo || 'Conhecimento sem limites.')}
              </h1>
              <p className="text-base md:text-lg text-slate-400 font-medium max-w-lg leading-relaxed">
                {live ? (live.descricao || 'Acompanhe nossa transmissão exclusiva.') : (config.home_hero_subtitulo || 'Explore nossa biblioteca premium e acelere sua carreira.')}
              </p>
            </div>

            <div className="flex items-center gap-6 pt-2">
              <Link
                href={live ? "/live" : "#recentes"}
                className="group/btn relative px-8 py-4 bg-orange-500 text-white font-black rounded-xl transition-all hover:scale-105 active:scale-95 shadow-lg shadow-orange-500/20 overflow-hidden"
              >
                <span className="relative z-10 flex items-center gap-3 text-sm uppercase tracking-widest">
                  {live ? <PlayCircle className="w-5 h-5" /> : <Compass className="w-5 h-5" />}
                  {live ? "Acessar Agora" : "Ver Conteúdos"}
                </span>
              </Link>
            </div>
          </div>

          {/* Right Side - Dynamic Preview Player */}
          <div className="hidden lg:block relative w-[400px] aspect-video shrink-0 animate-in fade-in slide-in-from-right-8 duration-1000 delay-300">
             <div className="absolute inset-0 bg-orange-500/20 blur-[120px] rounded-full animate-pulse" />
             <div className="relative w-full h-full border border-white/10 rounded-2xl overflow-hidden shadow-2xl backdrop-blur-xl bg-slate-900/40 group/preview">
                {live && live.url ? (
                  <iframe 
                    src={`https://www.youtube.com/embed/${live.url.split('v=')[1]}?autoplay=1&mute=1&controls=0&loop=1&playlist=${live.url.split('v=')[1]}`}
                    className="w-full h-full border-0 pointer-events-none opacity-80 group-hover/preview:opacity-100 transition-opacity"
                    allow="autoplay"
                  />
                ) : config.home_hero_video_id ? (
                  <iframe 
                    src={`https://www.youtube.com/embed/${config.home_hero_video_id.includes('v=') ? config.home_hero_video_id.split('v=')[1].split('&')[0] : config.home_hero_video_id}?autoplay=1&mute=1&controls=0&loop=1&playlist=${config.home_hero_video_id.includes('v=') ? config.home_hero_video_id.split('v=')[1].split('&')[0] : config.home_hero_video_id}`}
                    className="w-full h-full border-0 pointer-events-none opacity-60 group-hover/preview:opacity-100 transition-opacity"
                    allow="autoplay"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center bg-slate-800/50">
                    <PlayCircle className="w-16 h-16 text-orange-500/20" />
                  </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-tr from-slate-950/40 to-transparent pointer-events-none" />
                
                {/* Badge de 'AO VIVO' no preview */}
                {live && (
                  <div className="absolute top-3 right-3 px-2 py-1 bg-red-600 text-[8px] font-black text-white rounded uppercase tracking-widest animate-pulse">
                    Live Preview
                  </div>
                )}
             </div>
          </div>
        </div>
      </div>
    </section>
  );
}
