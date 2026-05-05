import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";
import Sidebar from "@/components/Sidebar";
import { getSession } from "@/lib/auth";

const inter = Inter({ subsets: ["latin"] });

import ClientLayout from "@/components/ClientLayout";

export const metadata: Metadata = {
  title: "Martinez Videos - Premium Learning",
  description: "Plataforma de ensino inteligente e premium.",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const session = await getSession();
  
  let setores = [];
  if (session) {
    const { getDbConnection } = await import("@/lib/db");
    const pool = await getDbConnection();
    const [rows] = await pool.query('SELECT id, nome FROM setores WHERE ativo = "S" ORDER BY nome ASC');
    setores = rows as any[];
  }

  return (
    <html lang="pt-BR" className="dark">
      <body className={`${inter.className} bg-slate-950 text-slate-200 antialiased min-h-screen`}>
        {session ? (
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
