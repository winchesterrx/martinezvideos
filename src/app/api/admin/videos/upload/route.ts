import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import { writeFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';
import { put } from '@vercel/blob';

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
    let sequenciaId = formData.get('sequencia_id') as string;
    const novaTrilhaNome = formData.get('nova_trilha_nome') as string;
    const isSequencia = formData.get('is_sequencia') === '1';
    const sequenciaOrdem = formData.get('sequencia_ordem') as string;

    let videoUrl = formData.get('url') as string;

    const martinezDb = await getDbConnection();

    // Lógica de Sequência (Trilha)
    if (isSequencia && novaTrilhaNome) {
      const [trilhaRes] = await martinezDb.query(
        'INSERT INTO trilhas (nome, modulo_id) VALUES (?, ?)', 
        [novaTrilhaNome, moduloId ? parseInt(moduloId) : null]
      );
      sequenciaId = (trilhaRes as any).insertId.toString();
    }

    // Se for upload real de arquivo
    if (tipoFonte === 'upload') {
      const file = formData.get('video_file') as File;
      if (!file) {
        return NextResponse.json({ error: 'Arquivo não enviado' }, { status: 400 });
      }

      // Tenta upload no Vercel Blob primeiro (para produção)
      if (process.env.BLOB_READ_WRITE_TOKEN) {
        const blob = await put(file.name, file, { access: 'public' });
        videoUrl = blob.url;
      } else {
        // Fallback para local (apenas desenvolvimento local)
        const bytes = await file.arrayBuffer();
        const buffer = Buffer.from(bytes);
        const uploadDir = join(process.cwd(), 'public', 'uploads');
        if (!existsSync(uploadDir)) await mkdir(uploadDir, { recursive: true });
        const fileName = `${Date.now()}-${file.name.replace(/\s+/g, '_')}`;
        const filePath = join(uploadDir, fileName);
        await writeFile(filePath, buffer);
        videoUrl = `/uploads/${fileName}`;
      }
    }

    // Upload de Thumbnail (Banner)
    let thumbnailUrl = null;
    const thumbFile = formData.get('thumbnail_file') as File;
    if (thumbFile) {
      if (process.env.BLOB_READ_WRITE_TOKEN) {
        const blob = await put(thumbFile.name, thumbFile, { access: 'public' });
        thumbnailUrl = blob.url;
      } else {
        const bytes = await thumbFile.arrayBuffer();
        const buffer = Buffer.from(bytes);
        const uploadDir = join(process.cwd(), 'public', 'uploads');
        if (!existsSync(uploadDir)) await mkdir(uploadDir, { recursive: true });
        const fileName = `thumb-${Date.now()}-${thumbFile.name.replace(/\s+/g, '_')}`;
        const filePath = join(uploadDir, fileName);
        await writeFile(filePath, buffer);
        thumbnailUrl = `/uploads/${fileName}`;
      }
    }

    // Busca o nome do setor para manter compatibilidade
    let setorNome = '1';
    if (setorId) {
      const [sRows]: any = await martinezDb.query('SELECT nome FROM setores WHERE id = ?', [setorId]);
      if (sRows.length > 0) setorNome = sRows[0].nome;
    }

    // Insere no banco
    const [result] = await martinezDb.query(`
      INSERT INTO videos (
        titulo, descricao, url_video, setor, setor_id, modulo_id, 
        thumbnail, is_sequencia, sequencia_id, sequencia_ordem, data_upload
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    `, [
      titulo, 
      descricao || '', 
      videoUrl, 
      setorNome,
      setorId ? parseInt(setorId) : null, 
      moduloId ? parseInt(moduloId) : null, 
      thumbnailUrl,
      isSequencia ? 1 : 0, 
      sequenciaId ? parseInt(sequenciaId) : null, 
      sequenciaOrdem ? parseInt(sequenciaOrdem) : 1
    ]);

    return NextResponse.json({ success: true, id: (result as any).insertId });

  } catch (error) {
    console.error('Upload Error:', error);
    return NextResponse.json({ error: 'Erro interno no servidor' }, { status: 500 });
  }
}
