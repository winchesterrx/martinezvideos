import { getDbConnection } from '@/lib/db';
import { 
  Users, 
  Video, 
  Radio, 
  Eye, 
  MessageSquare, 
  Plus, 
  LayoutDashboard,
  Settings,
  TrendingUp,
  ArrowUpRight,
  Calendar,
  ThumbsUp,
  ArrowLeft
} from 'lucide-react';
import Link from 'next/link';
import DeleteLiveButton from './components/DeleteLiveButton';

export default async function AdminDashboard() {
  const pool = await getDbConnection();

  // 1. Estatísticas Rápidas
  const [userCount] = await pool.query('SELECT COUNT(*) as total FROM usuarios');
  const [videoCount] = await pool.query('SELECT COUNT(*) as total FROM videos');
  const [viewCount] = await pool.query('SELECT SUM(visualizacoes) as total FROM videos');
  const [commentCount] = await pool.query('SELECT COUNT(*) as total FROM comentarios');
  
  const stats = [
    { label: 'Usuários Totais', value: (userCount as any)[0].total, icon: Users, color: 'text-blue-400' },
    { label: 'Vídeos na Base', value: (videoCount as any)[0].total, icon: Video, color: 'text-orange-400' },
    { label: 'Visualizações', value: (viewCount as any)[0].total || 0, icon: Eye, color: 'text-emerald-400' },
    { label: 'Comentários', value: (commentCount as any)[0].total, icon: MessageSquare, color: 'text-indigo-400' },
  ];

  // 2. Últimos Vídeos Adicionados
  const [recentVideos] = await pool.query('SELECT id, titulo, visualizacoes, recomendado FROM videos ORDER BY id DESC LIMIT 5');
  const ultimosVideos = recentVideos as any[];

  // 3. Status da Live Atual
  const [lives] = await pool.query('SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 LIMIT 1');
  const liveAtiva = (lives as any[])[0] || null;

  // 4. Histórico de Lives
  const [historyResults] = await pool.query(`
    SELECT t.*, 
    (SELECT COUNT(*) FROM martinez_logs_master WHERE video_id = SUBSTRING_INDEX(t.url, 'v=', -1) AND tipo_acao = 'VIEW') as views,
    (SELECT COUNT(*) FROM martinez_logs_master WHERE video_id = SUBSTRING_INDEX(t.url, 'v=', -1) AND tipo_acao = 'LIKE') as likes
    FROM transmissao_ao_vivo t 
    ORDER BY t.created_at DESC 
    LIMIT 6
  `);
  const history = historyResults as any[];

  const getVideoId = (url: string) => {
    if (!url) return null;
    const match = url.match(/(?:v=|\/)([0-9A-Za-z_-]{11}).*/);
    return match ? match[1] : null;
  };

  return (
    <div className="p-6 md:p-10 max-w-7xl mx-auto space-y-10">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            <LayoutDashboard className="text-orange-500" />
            Centro de Comando Admin
          </h1>
          <p className="text-slate-500 mt-1">Gerencie a plataforma, vídeos e lives em tempo real.</p>
        </div>
        <div className="flex items-center gap-3">
          <Link href="/admin/upload" className="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-orange-500/20">
            <Plus size={18} />
            Subir Novo Vídeo
          </Link>
          <Link href="/admin/live" className="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all border border-white/5">
            <Radio size={18} />
            Estúdio de Live
          </Link>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, i) => (
          <div key={i} className="bg-slate-900/50 backdrop-blur-md border border-white/5 rounded-2xl p-6 hover:border-white/10 transition-colors group">
            <div className="flex items-center justify-between mb-4">
              <div className={`p-3 rounded-xl bg-slate-800 ${stat.color}`}>
                <stat.icon size={24} />
              </div>
              <TrendingUp size={16} className="text-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity" />
            </div>
            <p className="text-slate-400 text-sm font-medium uppercase tracking-wider">{stat.label}</p>
            <h3 className="text-3xl font-bold text-white mt-1">{stat.value}</h3>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Controle de Live Atual */}
        <div className="lg:col-span-1 space-y-6">
          <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 h-full">
            <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-2">
              <Radio size={20} className="text-red-500" />
              Status do Estúdio
            </h2>
            
            {liveAtiva ? (
              <div className="space-y-6">
                <div className="p-4 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center gap-4">
                  <div className="relative flex h-3 w-3">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                  </div>
                  <span className="text-red-400 font-bold text-sm uppercase">Ao Vivo Agora</span>
                </div>
                <div className="aspect-video rounded-xl bg-slate-800 overflow-hidden relative border border-white/5">
                   <img src={`https://img.youtube.com/vi/${liveAtiva.url.split('v=')[1]}/mqdefault.jpg`} className="w-full h-full object-cover opacity-60" alt="" />
                   <div className="absolute inset-0 flex items-center justify-center">
                     <PlayCircle className="text-white w-12 h-12" />
                   </div>
                </div>
                <h3 className="text-white font-bold">{liveAtiva.titulo}</h3>
                <Link href="/admin/live" className="w-full block py-3 text-center bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold transition-all border border-white/5">
                  Ir para Estúdio
                </Link>
              </div>
            ) : (
              <div className="flex flex-col items-center justify-center py-12 text-center">
                <div className="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center text-slate-500 mb-4">
                  <Radio size={32} />
                </div>
                <p className="text-slate-400 font-medium">Nenhuma live ativa</p>
                <Link href="/admin/live" className="mt-6 text-orange-500 font-bold hover:underline">
                  Abrir Transmissão →
                </Link>
              </div>
            )}
          </div>
        </div>

        {/* Gerenciador de Vídeos */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-slate-900/50 border border-white/5 rounded-3xl p-8 h-full">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-xl font-bold text-white flex items-center gap-2">
                <Video size={20} className="text-orange-500" />
                Vídeos Recentes
              </h2>
              <Link href="/admin/videos" className="text-sm text-orange-500 font-medium hover:underline">
                Ver todos
              </Link>
            </div>

            <div className="space-y-4">
              {ultimosVideos.map((video) => (
                <div key={video.id} className="flex items-center justify-between p-4 rounded-2xl bg-slate-800/40 border border-white/5 hover:border-white/10 transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-slate-700 flex items-center justify-center text-slate-500 overflow-hidden shrink-0">
                       <Video size={20} />
                    </div>
                    <div>
                      <h4 className="text-white font-semibold text-sm line-clamp-1">{video.titulo}</h4>
                      <div className="flex items-center gap-3 mt-1">
                        <span className="text-[10px] text-slate-500 flex items-center gap-1 uppercase font-bold tracking-wider">
                          <Eye size={10} /> {video.visualizacoes || 0} views
                        </span>
                        {video.recomendado === 1 && (
                          <span className="text-[10px] text-orange-400 bg-orange-500/10 px-1.5 py-0.5 rounded-md font-bold">DESTAQUE</span>
                        )}
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button className="p-2 text-slate-500 hover:text-white rounded-lg hover:bg-white/5 transition-colors">
                       <Settings size={18} />
                    </button>
                    <Link href={`/video/${video.id}`} className="p-2 text-slate-500 hover:text-orange-500 rounded-lg hover:bg-white/5 transition-colors">
                       <ArrowUpRight size={18} />
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

      </div>

      {/* Ações Rápidas de Gestão */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pb-20">
        <Link href="/admin/usuarios" className="p-8 rounded-3xl bg-blue-500/5 border border-blue-500/20 hover:bg-blue-500/10 transition-all group">
          <Users size={32} className="text-blue-500 mb-4" />
          <h3 className="text-xl font-bold text-white mb-2">Usuários</h3>
          <p className="text-slate-400 text-sm">Controle permissões, bloqueie acessos e visualize atividades.</p>
        </Link>
        <Link href="/admin/dashboard" className="p-8 rounded-3xl bg-emerald-500/5 border border-emerald-500/20 hover:bg-emerald-500/10 transition-all group">
          <LayoutDashboard size={32} className="text-emerald-500 mb-4" />
          <h3 className="text-xl font-bold text-white mb-2">Personalizar Home</h3>
          <p className="text-slate-400 text-sm">Altere os banners, notícias e recomendações da IA.</p>
        </Link>
        <Link href="/admin/comentarios" className="p-8 rounded-3xl bg-indigo-500/5 border border-indigo-500/20 hover:bg-indigo-500/10 transition-all group">
          <MessageSquare size={32} className="text-indigo-500 mb-4" />
          <h3 className="text-xl font-bold text-white mb-2">Comentários</h3>
          <p className="text-slate-400 text-sm">Veja o que os alunos estão falando e responda dúvidas.</p>
        </Link>
      </div>

      {/* Histórico de Performance Integrado */}
      <div className="space-y-8 pb-20">
        <div className="flex items-center justify-between">
           <h2 className="text-2xl font-black text-white uppercase tracking-widest flex items-center gap-3">
             <Calendar size={28} className="text-orange-500" /> Histórico de Performance
           </h2>
           <Link href="/admin/live" className="text-sm font-bold text-orange-500 hover:underline">Ver Estúdio Completo →</Link>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {history.map((item) => {
            const vid = getVideoId(item.url);
            return (
              <Link 
                key={item.id} 
                href="/admin/live"
                className="group relative bg-slate-900 border border-white/5 rounded-[40px] p-8 hover:border-orange-500/50 transition-all cursor-pointer overflow-hidden"
              >
                {/* Background do YouTube com Máscara */}
                {vid && (
                  <div 
                    className="absolute inset-0 opacity-20 group-hover:opacity-40 transition-opacity duration-500"
                    style={{
                      backgroundImage: `url(https://img.youtube.com/vi/${vid}/maxresdefault.jpg)`,
                      backgroundSize: 'cover',
                      backgroundPosition: 'center'
                    }}
                  >
                    <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent" />
                  </div>
                )}

                <div className="relative z-10">
                  <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                      <span className={`px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${item.ativo === 1 ? 'bg-green-500/20 text-green-500' : 'bg-slate-800 text-slate-500'}`}>
                        {item.ativo === 1 ? 'Ao Vivo' : 'Finalizado'}
                      </span>
                      <span className="text-[10px] font-bold text-slate-500 uppercase">{new Date(item.created_at).toLocaleDateString()}</span>
                    </div>

                    {/* Botão de Excluir Integrado */}
                    <DeleteLiveButton id={item.id} />
                  </div>

                  <h3 className="text-xl font-black text-white mb-8 line-clamp-2 leading-tight group-hover:text-orange-500 transition-colors">
                    {item.titulo}
                  </h3>

                  <div className="grid grid-cols-2 gap-4">
                    <div className="bg-slate-950/50 border border-white/5 p-4 rounded-2xl">
                       <div className="flex items-center gap-2 mb-1 text-orange-500">
                          <Eye size={12} />
                          <span className="text-[10px] font-black uppercase">Views</span>
                       </div>
                       <span className="text-xl font-black text-white">{item.views || 0}</span>
                    </div>
                    <div className="bg-slate-950/50 border border-white/5 p-4 rounded-2xl">
                       <div className="flex items-center gap-2 mb-1 text-indigo-400">
                          <ThumbsUp size={12} />
                          <span className="text-[10px] font-black uppercase">Likes</span>
                       </div>
                       <span className="text-xl font-black text-white">{item.likes || 0}</span>
                    </div>
                  </div>

                  <div className="mt-6 flex items-center justify-between text-slate-500 group-hover:text-white transition-colors">
                     <span className="text-[10px] font-black uppercase tracking-widest">Ver No Estúdio</span>
                     <ArrowLeft size={16} className="rotate-180" />
                  </div>
                </div>
              </Link>
            );
          })}
        </div>
      </div>

    </div>
  );
}

function PlayCircle(props: any) {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
  );
}
