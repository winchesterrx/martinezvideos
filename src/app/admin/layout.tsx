import { getSession } from "@/lib/auth";
import { redirect } from "next/navigation";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await getSession();

  // Proteção: Apenas ADM pode entrar aqui
  if (!session || session.adm !== 'S') {
    redirect('/');
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      {/* Background Decor */}
      <div className="fixed inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.05),transparent)] pointer-events-none" />
      
      <div className="relative z-10">
        {children}
      </div>
    </div>
  );
}
