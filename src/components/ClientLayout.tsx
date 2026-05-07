'use client';

import { useState } from 'react';
import Sidebar from './Sidebar';
import Navbar from './Navbar';

export default function ClientLayout({ 
  children, 
  user, 
  setores 
}: { 
  children: React.ReactNode;
  user: any;
  setores: any[];
}) {
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);
  const closeSidebar = () => setIsSidebarOpen(false);

  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar 
        user={user} 
        setores={setores} 
        isOpen={isSidebarOpen} 
        closeSidebar={closeSidebar} 
      />
      
      <div className={`flex-1 flex flex-col relative z-0 min-w-0 transition-all duration-300`}>
        <Navbar user={user} toggleSidebar={toggleSidebar} />
        
        <main className="flex-1 overflow-y-auto custom-scrollbar">
          {children}
        </main>
      </div>
    </div>
  );
}
