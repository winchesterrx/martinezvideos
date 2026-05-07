import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import { getSession } from '@/lib/auth';
import bcrypt from 'bcryptjs';

export async function GET() {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const pool = await getDbConnection();
    const [rows] = await pool.query('SELECT id, nome, email, ADM, ativo, data_cadastro FROM usuarios ORDER BY nome ASC');
    return NextResponse.json({ users: rows });
  } catch (error) {
    return NextResponse.json({ error: 'Erro ao buscar usuários' }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getSession();
  if (!session || session.adm !== 'S') {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { nome, email, senha, adm } = await request.json();

    if (!nome || !email || !senha) {
      return NextResponse.json({ error: 'Nome, email e senha são obrigatórios' }, { status: 400 });
    }

    const hashedPassword = await bcrypt.hash(senha, 10);
    const pool = await getDbConnection();
    
    const [result]: any = await pool.execute(
      'INSERT INTO usuarios (nome, email, senha, ADM, ativo, estado_id) VALUES (?, ?, ?, ?, 1, 1)',
      [nome, email, hashedPassword, adm || 'N']
    );

    return NextResponse.json({ success: true, id: result.insertId });
  } catch (error: any) {
    if (error.code === 'ER_DUP_ENTRY') {
      return NextResponse.json({ error: 'Email já cadastrado' }, { status: 400 });
    }
    return NextResponse.json({ error: 'Erro ao criar usuário' }, { status: 500 });
  }
}
