import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { getSession } from '@/lib/auth';

const publicRoutes = ['/login', '/api/auth/login'];

export async function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;
  
  // Injeta headers para o layout saber a rota atual (Server Side)
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set('x-pathname', pathname);
  requestHeaders.set('x-url', request.url);

  const isPublicRoute = 
    pathname === '/' ||
    pathname === '/login' ||
    pathname === '/live' ||
    pathname.startsWith('/video/') ||
    pathname.startsWith('/sistema/') ||
    pathname.startsWith('/modulo/') ||
    pathname.startsWith('/busca') ||
    pathname.startsWith('/_next') ||
    pathname.startsWith('/favicon.ico');

  if (isPublicRoute) {
    return NextResponse.next({
      request: { headers: requestHeaders }
    });
  }

  const session = await getSession();

  if (!session) {
    return NextResponse.redirect(new URL(`/login?redirect=${encodeURIComponent(pathname)}`, request.url));
  }

  return NextResponse.next({
    request: { headers: requestHeaders }
  });
}

export const config = {
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico).*)'],
};
