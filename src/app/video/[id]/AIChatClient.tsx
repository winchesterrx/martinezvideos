'use client';

import { useState, useRef, useEffect } from 'react';
import { Send, Loader2, Sparkles, User } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

type Message = {
  id: string;
  role: 'user' | 'assistant';
  content: string;
};

export default function AIChatClient({ videoContext, isLoggedIn }: { videoContext: any, isLoggedIn: boolean }) {
  const [messages, setMessages] = useState<Message[]>([
    {
      id: 'welcome',
      role: 'assistant',
      content: `Olá! Sou o seu Tutor Virtual. Ficou com alguma dúvida sobre a aula "${videoContext?.titulo || 'atual'}"? Me pergunte!`
    }
  ]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!isLoggedIn) {
      window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
      return;
    }
    if (!input.trim() || isLoading) return;

    const userMsg = input.trim();
    setInput('');
    const newMessages: Message[] = [...messages, { id: Date.now().toString(), role: 'user', content: userMsg }];
    setMessages(newMessages);
    setIsLoading(true);

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMsg, videoContext }),
      });

      const data = await res.json();

      if (res.ok) {
        setMessages([...newMessages, { id: (Date.now() + 1).toString(), role: 'assistant', content: data.reply }]);
      } else {
        setMessages([...newMessages, { id: (Date.now() + 1).toString(), role: 'assistant', content: 'Desculpe, encontrei um erro ao tentar processar sua dúvida. Tente novamente mais tarde.' }]);
      }
    } catch (error) {
      setMessages([...newMessages, { id: (Date.now() + 1).toString(), role: 'assistant', content: 'Erro de conexão. Verifique sua internet.' }]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex-1 flex flex-col overflow-hidden bg-slate-900">
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        <AnimatePresence>
          {messages.map((msg) => (
            <motion.div
              key={msg.id}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              className={`flex gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : ''}`}
            >
              <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${msg.role === 'user' ? 'bg-orange-600' : 'bg-indigo-600'}`}>
                {msg.role === 'user' ? <User size={16} className="text-white" /> : <Sparkles size={16} className="text-white" />}
              </div>
              <div className={`max-w-[80%] rounded-2xl px-4 py-3 text-sm ${msg.role === 'user' ? 'bg-orange-500/20 text-orange-50 border border-orange-500/30' : 'bg-slate-800 text-slate-300 border border-white/5'}`}>
                {msg.content}
              </div>
            </motion.div>
          ))}
        </AnimatePresence>
        {isLoading && (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="flex gap-3">
            <div className="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0">
              <Sparkles size={16} className="text-white animate-pulse" />
            </div>
            <div className="bg-slate-800 rounded-2xl px-4 py-3 flex items-center gap-2 border border-white/5">
              <Loader2 size={16} className="animate-spin text-indigo-400" />
              <span className="text-sm text-slate-400">Pensando...</span>
            </div>
          </motion.div>
        )}
        <div ref={messagesEndRef} />
      </div>

      <div className="p-4 bg-slate-950 border-t border-white/5">
        <form onSubmit={handleSubmit} className="relative flex items-center">
          <input
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            placeholder={isLoggedIn ? "Digite sua dúvida..." : "Faça login para perguntar..."}
            onFocus={() => {
              if (!isLoggedIn) {
                window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
              }
            }}
            className="w-full bg-slate-900 border border-white/10 rounded-full py-3 pl-4 pr-12 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
            disabled={isLoading}
          />
          <button
            type="submit"
            disabled={!isLoggedIn ? false : (!input.trim() || isLoading)}
            className="absolute right-2 p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <Send size={16} />
          </button>
        </form>
      </div>
    </div>
  );
}
