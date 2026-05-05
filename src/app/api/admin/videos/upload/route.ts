import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import { writeFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';

export async function POST(request: Request) {
  try {
    const session = await getSession();
    if (!session || session.adm !== 'S') {
      return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
    }

    const formData = await request.formData();
    const titulo = formData.get('titulo') as string;
    const descricao = formData.get('descricao') as string;
    const tipoFonte = formData.get('tipo_fonte') as string;
    const setorId = formData.get('setor_id') as string;
    const moduloId = formData.get('modulo_id') as string;
    const isSequencia = formData.get('is_sequencia') === '1';
    const sequenciaId = formData.get('sequencia_id') as string;
    const sequenciaOrdem = formData.get('sequencia_ordem') as string;

    let videoUrl = formData.get('url') as string;

    // Se for upload real de arquivo
    if (tipoFonte === 'upload') {
      const file = formData.get('video_file') as File;
      if (!file) {
        return NextResponse.json({ error: 'Arquivo não enviado' }, { status: 400 });
      }

      const bytes = await file.arrayBuffer();
      const buffer = Buffer.from(bytes);

      // Caminho de upload (pasta public/uploads)
      const uploadDir = join(process.cwd(), 'public', 'uploads');
      if (!existsSync(uploadDir)) {
        await mkdir(uploadDir, { recursive: true });
      }

      const fileName = `${Date.now()}-${file.name.replace(/\s+/g, '_')}`;
      const filePath = join(uploadDir, fileName);
      await writeFile(filePath, buffer);

      // A URL final será relativa ao public
      videoUrl = `/uploads/${fileName}`;
    }

    const pool = await getDbConnection();

    // Insere no banco
    const [result] = await pool.query(`
      INSERT INTO videos (
        titulo, descricao, url_video, setor_id, modulo_id, 
        is_sequencia, sequencia_id, sequencia_ordem, data_upload
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    `, [
      titulo, 
      descricao, 
      videoUrl, 
      setorId, 
      moduloId, 
      isSequencia ? 1 : 0, 
      sequenciaId || null, 
      sequenciaOrdem || 1
    ]);

    return NextResponse.json({ success: true, id: (result as any).insertId });

  } catch (error) {
    console.error('Upload Error:', error);
    return NextResponse.json({ error: 'Erro interno no servidor' }, { status: 500 });
  }
}
