export function middleware(context) {
    return context.rewrite('/not-found-path');
}

export const config = {
  matcher: ['/404.html'],
};
