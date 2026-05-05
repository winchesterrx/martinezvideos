const API_KEY = process.env.YOUTUBE_API_KEY;

export async function getYouTubeVideoStats(videoId: string) {
  try {
    const res = await fetch(`https://www.googleapis.com/youtube/v3/videos?part=statistics,liveStreamingDetails,snippet&id=${videoId}&key=${API_KEY}`);
    const data = await res.json();
    
    if (!data.items || data.items.length === 0) return null;
    
    const item = data.items[0];
    return {
      views: item.statistics.viewCount,
      likes: item.statistics.likeCount,
      liveChatId: item.liveStreamingDetails?.activeLiveChatId || null,
      isLive: item.snippet.liveBroadcastContent === 'live',
      title: item.snippet.title,
      thumbnail: item.snippet.thumbnails.maxresdefault?.url || item.snippet.thumbnails.high?.url
    };
  } catch (error) {
    console.error('YouTube API Error:', error);
    return null;
  }
}

export async function getYouTubeChatMessages(chatId: string) {
  try {
    const res = await fetch(`https://www.googleapis.com/youtube/v3/liveChat/messages?liveChatId=${chatId}&part=snippet,authorDetails&key=${API_KEY}`);
    const data = await res.json();
    return data.items || [];
  } catch (error) {
    return [];
  }
}

export async function getYouTubeVideoComments(videoId: string) {
  try {
    const res = await fetch(`https://www.googleapis.com/youtube/v3/commentThreads?videoId=${videoId}&part=snippet&maxResults=50&key=${API_KEY}`);
    const data = await res.json();
    return data.items || [];
  } catch (error) {
    console.error('YouTube Comments Error:', error);
    return [];
  }
}
