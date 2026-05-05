import { getDbConnection } from '@/lib/db';
import { notFound } from 'next/navigation';
import Link from 'next/link';
import { FolderOpen, ArrowLeft, Layers, Video } from 'lucide-react';

async function getSistemaData(id: string) {
  const pool = await getDbConnection();
  
  // Buscar o setor atual
  const [setores] = await pool.query('SELECT * FROM setores WHERE id = ?', [id]);
  const setor = (setores as any[])[0];
  
  if (!setor) return null;

  // Buscar os módulos deste setor com a contagem de vídeos
  const [modulos] = await pool.query(`
    SELECT m.*, COUNT(v.id) as total_videos 
    FROM modulos m
    LEFT JOIN videos v ON v.modulo_id = m.id
    WHERE m.setor_id = ? AND m.ativo = 'S'
    GROUP BY m.id
    ORDER BY m.nome ASC
  `, [id]);

  return { 
    setor, 
    modulos: modulos as any[] 
  };
}

export default async function SistemaPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const data = await getSistemaData(id);
  
  if (!data) {
    notFound();
  }

  const { setor, modulos } = data;

  return (
    <div className="min-h-screen bg-slate-950 text-white relative">
      {/* Background Mask */}
      <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-fixed bg-center opacity-5 pointer-events-none z-0" />
      <div className="absolute inset-0 bg-gradient-to-b from-slate-900/80 via-slate-950 to-slate-950 pointer-events-none z-0" />

      <div className="relative z-10 px-4 md:px-8 py-8 max-w-7xl mx-auto">
        
        {/* Breadcrumb / Header */}
        <div className="mb-10">
          <Link href="/" className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-orange-500 transition-colors mb-6">
            <ArrowLeft className="w-4 h-4" />
            Voltar para o Início
          </Link>
          
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 flex items-center justify-center shadow-lg shadow-orange-500/20">
              <Layers className="w-8 h-8 text-white" />
            </div>
            <div>
              <h4 className="text-orange-500 font-bold tracking-widest text-sm uppercase mb-1">Sistema</h4>
              <h1 className="text-4xl md:text-5xl font-extrabold text-white">{setor.nome}</h1>
            </div>
          </div>
        </div>

        {/* Módulos (Subpastas) */}
        <div className="mb-8 flex items-center gap-3">
          <FolderOpen className="w-6 h-6 text-slate-400" />
          <h2 className="text-2xl font-bold text-white">Módulos Disponíveis</h2>
        </div>

        {modulos.length === 0 ? (
          <div className="bg-slate-900/50 backdrop-blur-sm border border-white/5 rounded-2xl p-12 text-center">
            <FolderOpen className="w-12 h-12 text-slate-600 mx-auto mb-4" />
            <h3 className="text-xl font-bold text-slate-300 mb-2">Nenhum módulo encontrado</h3>
            <p className="text-slate-500">Ainda não existem módulos cadastrados para este sistema.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {modulos.map((modulo) => (
              <Link 
                key={modulo.id} 
                href={`/modulo/${modulo.id}`}
                className="group relative bg-slate-900/60 backdrop-blur-sm border border-white/5 rounded-2xl p-6 hover:bg-slate-800 hover:border-orange-500/40 transition-all shadow-lg hover:shadow-orange-500/10 flex flex-col items-start"
              >
                <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                  <FolderOpen className="w-24 h-24 text-slate-400" />
                </div>
                
                <div className="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center mb-6 group-hover:bg-orange-500 group-hover:text-white text-slate-400 transition-colors z-10">
                  <FolderOpen className="w-6 h-6" />
                </div>
                
                <h3 className="text-xl font-bold text-white mb-2 z-10">{modulo.nome}</h3>
                
                <div className="mt-auto flex items-center gap-2 text-sm text-slate-400 z-10 bg-slate-950/50 px-3 py-1.5 rounded-lg border border-white/5 group-hover:border-orange-500/20">
                  <Video className="w-4 h-4 text-orange-500" />
                  <span>{modulo.total_videos} {modulo.total_videos === 1 ? 'Aula' : 'Aulas'}</span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
