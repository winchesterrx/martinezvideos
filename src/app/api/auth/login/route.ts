import { NextResponse } from 'next/server';
import { getDbConnection } from '@/lib/db';
import bcrypt from 'bcryptjs';
import { login } from '@/lib/auth';

export async function POST(request: Request) {
  try {
    const { email, password } = await request.json();

    if (!email || !password) {
      return NextResponse.json({ error: 'Email e senha são obrigatórios' }, { status: 400 });
    }

    const pool = await getDbConnection();
    const [rows]: any = await pool.execute('SELECT * FROM usuarios WHERE email = ?', [email]);

    if (rows.length === 0) {
      return NextResponse.json({ error: 'Usuário não encontrado' }, { status: 401 });
    }

    const user = rows[0];

    // Verificar senha gerada pelo password_hash() do PHP
    const isPasswordValid = await bcrypt.compare(password, user.senha);

    if (!isPasswordValid) {
      return NextResponse.json({ error: 'Senha incorreta' }, { status: 401 });
    }

    // Criar a sessão com o JWT usando as funcões do lib/auth.ts
    await login({ id: user.id, email: user.email, nome: user.nome, ADM: user.ADM });

    return NextResponse.json({ success: true, user: { id: user.id, nome: user.nome, adm: user.ADM } });
  } catch (error) {
    console.error('Login error:', error);
    return NextResponse.json({ error: 'Erro interno no servidor' }, { status: 500 });
  }
}
