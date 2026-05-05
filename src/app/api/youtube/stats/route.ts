import { NextResponse } from 'next/server';
import { getYouTubeVideoStats, getYouTubeChatMessages, getYouTubeVideoComments } from '@/lib/youtube';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const videoId = searchParams.get('videoId');
  const chatId = searchParams.get('chatId');

  if (chatId) {
    const messages = await getYouTubeChatMessages(chatId);
    return NextResponse.json({ messages });
  }

  const type = searchParams.get('type');
  if (type === 'comments' && videoId) {
    const comments = await getYouTubeVideoComments(videoId);
    return NextResponse.json({ comments });
  }

  if (videoId) {
    const stats = await getYouTubeVideoStats(videoId);
    return NextResponse.json({ stats });
  }

  return NextResponse.json({ error: 'Missing params' }, { status: 400 });
}
