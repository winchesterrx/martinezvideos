import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import Sidebar from "@/components/Sidebar";
import { getSession } from "@/lib/auth";
import { headers } from "next/headers";

const inter = Inter({ subsets: ["latin"] });

import ClientLayout from "@/components/ClientLayout";

export const metadata: Metadata = {
  title: "Martinez & Carvalho - Premium Learning",
  description: "Plataforma de ensino inteligente e premium.",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  // Garantir que as tabelas mestras existam
  const { initMasterLogs } = await import("@/lib/db-init");
  await initMasterLogs();

  const session = await getSession();
  
  let setores = [];
  if (session) {
    try {
      const { getDbConnection } = await import("@/lib/db");
      const pool = await getDbConnection();
      const [rows] = await pool.query('SELECT id, nome FROM setores WHERE ativo = "S" ORDER BY nome ASC');
      setores = rows as any[];
    } catch (error) {
      console.error('Falha ao carregar setores (Banco offline):', error);
      setores = [];
    }
  }

  const headerList = await headers();
  const pathname = headerList.get('x-pathname') || '';
  const isLoginPage = pathname === '/login';

  return (
    <html lang="pt-BR" className="dark">
      <body className={`${inter.className} text-slate-200 antialiased min-h-screen`}>
        {session && !isLoginPage ? (
          <ClientLayout user={session} setores={setores}>
            {children}
          </ClientLayout>
        ) : (
          <main>{children}</main>
        )}
      </body>
    </html>
  );
}
