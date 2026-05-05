'use client';

import { useState } from 'react';
import { Heart, MessageSquare, Share2, Send } from 'lucide-react';

export default function VideoInteractions({ 
  videoId, 
  initialLikes, 
  hasLikedInitially,
  initialComments 
}: { 
  videoId: string, 
  initialLikes: number,
  hasLikedInitially: boolean,
  initialComments: any[]
}) {
  const [likes, setLikes] = useState(initialLikes);
  const [isLiked, setIsLiked] = useState(hasLikedInitially);
  const [isLiking, setIsLiking] = useState(false);
  
  const [comments, setComments] = useState(initialComments);
  const [newComment, setNewComment] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLike = async () => {
    if (isLiking) return;
    setIsLiking(true);
    
    // Optimistic UI
    setIsLiked(!isLiked);
    setLikes(prev => isLiked ? Math.max(0, prev - 1) : prev + 1);

    try {
      await fetch('/api/video/curtir', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ videoId }),
      });
    } catch (e) {
      // Revert on error
      setIsLiked(isLiked);
      setLikes(initialLikes);
    } finally {
      setIsLiking(false);
    }
  };

  const handleComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newComment.trim() || isSubmitting) return;

    setIsSubmitting(true);
    try {
      const res = await fetch('/api/video/comentar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ videoId, conteudo: newComment }),
      });
      const data = await res.json();
      if (data.success) {
        setComments([data.comentario, ...comments]);
        setNewComment('');
      }
    } catch (e) {
      console.error(e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleShare = () => {
    if (navigator.share) {
      navigator.share({
        title: 'Martinez Videos',
        url: window.location.href,
      });
    } else {
      navigator.clipboard.writeText(window.location.href);
      alert('Link copiado!');
    }
  };

  return (
    <div className="mt-6 border-t border-white/10 pt-6">
      
      {/* Action Bar */}
      <div className="flex items-center gap-4 mb-8">
        <button 
          onClick={handleLike}
          disabled={isLiking}
          className={`flex items-center gap-2 px-6 py-3 rounded-full font-bold transition-all ${
            isLiked 
              ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/25' 
              : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
          }`}
        >
          <Heart className={`w-5 h-5 ${isLiked ? 'fill-current' : ''}`} />
          {likes} Curtidas
        </button>

        <button 
          onClick={handleShare}
          className="flex items-center gap-2 px-6 py-3 rounded-full font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all"
        >
          <Share2 className="w-5 h-5" />
          Compartilhar
        </button>
      </div>

      {/* Comments Section */}
      <div className="space-y-6">
        <h3 className="text-xl font-bold flex items-center gap-2">
          <MessageSquare className="w-5 h-5 text-orange-500" />
          Comentários ({comments.length})
        </h3>

        <form onSubmit={handleComment} className="flex gap-3">
          <div className="w-10 h-10 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center shrink-0">
            {/* User Initials placeholder */}
            <UserIcon />
          </div>
          <div className="flex-1 relative">
            <input 
              type="text" 
              value={newComment}
              onChange={(e) => setNewComment(e.target.value)}
              placeholder="Adicione um comentário..."
              className="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-orange-500/50 pr-12"
            />
            <button 
              type="submit" 
              disabled={isSubmitting || !newComment.trim()}
              className="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-orange-500 hover:text-orange-400 disabled:opacity-50 transition-colors"
            >
              <Send className="w-5 h-5" />
            </button>
          </div>
        </form>

        <div className="space-y-4 mt-6">
          {comments.map((comment) => (
            <div key={comment.id} className="flex gap-4 p-4 rounded-xl bg-slate-900/50 border border-white/5">
              <div className="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center shrink-0 text-slate-400">
                <UserIcon />
              </div>
              <div>
                <div className="flex items-baseline gap-2 mb-1">
                  <span className="font-bold text-slate-200">{comment.usuario_nome || 'Usuário'}</span>
                  <span className="text-xs text-slate-500">{new Date(comment.data).toLocaleDateString('pt-BR')}</span>
                </div>
                <p className="text-slate-300 text-sm leading-relaxed">{comment.conteudo}</p>
              </div>
            </div>
          ))}
          {comments.length === 0 && (
            <p className="text-slate-500 text-center py-8">Nenhum comentário ainda. Seja o primeiro!</p>
          )}
        </div>
      </div>
    </div>
  );
}

function UserIcon() {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
  );
}
