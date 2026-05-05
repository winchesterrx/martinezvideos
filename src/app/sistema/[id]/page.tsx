import { getDbConnection } from '@/lib/db';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import { Layers, ChevronRight, Video } from 'lucide-react';

async function getSistemaData(id: string) {
  const pool = await getDbConnection();
  
  const [setores] = await pool.query('SELECT * FROM setores WHERE id = ?', [id]);
  const setor = (setores as any[])[0];
  
  if (!setor) return null;

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

export default async function SistemaPage({ params }: { params: { id: string } }) {
  const data = await getSistemaData(params.id);
  
  if (!data) {
    notFound();
  }

  const { setor, modulos } = data;

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-8">
      {/* Breadcrumb & Header */}
      <div className="flex flex-col gap-4 mb-8">
        <div className="flex items-center gap-2 text-sm text-slate-400 font-medium">
          <Link href="/" className="hover:text-white transition-colors">Início</Link>
          <ChevronRight size={16} />
          <span className="text-orange-500">Sistemas</span>
        </div>
        
        <div className="flex items-center gap-4">
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 border border-white/10">
            <Layers className="text-white" size={32} />
          </div>
          <div>
            <h1 className="text-3xl font-extrabold text-white">
              Sistema {setor.nome}
            </h1>
            <p className="text-slate-400 mt-1">Selecione um módulo para ver as aulas.</p>
          </div>
        </div>
      </div>

      {/* Grid de Módulos */}
      {modulos.length === 0 ? (
        <div className="bg-slate-900/50 border border-white/5 rounded-2xl p-12 text-center text-slate-400">
          Nenhum módulo encontrado neste sistema.
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {modulos.map((modulo) => (
            <Link 
              href={`/modulo/${modulo.id}`} 
              key={modulo.id} 
              className="group bg-slate-900/40 border border-white/5 rounded-2xl p-6 hover:bg-slate-800/60 hover:border-indigo-500/30 transition-all duration-300 shadow-lg relative overflow-hidden"
            >
              {/* Highlight gradient on hover */}
              <div className="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
              
              <div className="relative z-10">
                <div className="flex justify-between items-start mb-4">
                  <div 
                    className="w-12 h-12 rounded-xl flex items-center justify-center shadow-inner"
                    style={{ backgroundColor: modulo.cor ? `${modulo.cor}20` : '#4f46e520', color: modulo.cor || '#818cf8' }}
                  >
                    <Layers size={24} />
                  </div>
                  <div className="flex items-center gap-1 bg-white/5 px-3 py-1 rounded-full text-xs font-medium text-slate-300">
                    <Video size={14} /> {modulo.total_videos} aulas
                  </div>
                </div>
                
                <h3 className="text-xl font-bold text-white mb-2 group-hover:text-indigo-400 transition-colors">
                  {modulo.nome}
                </h3>
                {modulo.descricao && (
                  <p className="text-sm text-slate-400 line-clamp-2">
                    {modulo.descricao}
                  </p>
                )}
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
