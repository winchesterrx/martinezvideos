import { GoogleGenerativeAI } from '@google/generative-ai';
import { NextResponse } from 'next/server';

const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY || '');

export async function POST(request: Request) {
  try {
    const { message, videoContext } = await request.json();

    if (!process.env.GEMINI_API_KEY) {
      return NextResponse.json(
        { error: 'Chave da API do Gemini não configurada.' },
        { status: 500 }
      );
    }

    const model = genAI.getGenerativeModel({ model: 'gemini-1.5-flash' });

    const prompt = `
Você é o "Tutor Inteligente" da plataforma Martinez Videos. Seu papel é tirar dúvidas dos alunos baseando-se estritamente no contexto da aula em que eles estão.

Contexto da Aula Atual:
- Título do Vídeo: ${videoContext?.titulo || 'Desconhecido'}
- Setor: ${videoContext?.setor_nome || 'Desconhecido'}
- Módulo: ${videoContext?.modulo_nome || 'Desconhecido'}
- Descrição: ${videoContext?.descricao || 'Sem descrição.'}

Dúvida do Aluno:
"${message}"

Diretrizes de resposta:
1. Responda de forma direta, educada, didática e motivadora.
2. Formate sua resposta em parágrafos curtos.
3. Se a pergunta for totalmente irrelevante ao contexto ou à plataforma de estudos, seja gentil e peça para o aluno focar na aula atual.
4. Mantenha a resposta com no máximo 4 parágrafos.
`;

    const result = await model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();

    return NextResponse.json({ reply: text });
  } catch (error) {
    console.error('Erro na API do Gemini:', error);
    return NextResponse.json(
      { error: 'Não foi possível processar sua solicitação no momento.' },
      { status: 500 }
    );
  }
}
