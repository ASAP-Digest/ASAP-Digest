import { redirect } from '@sveltejs/kit';

/** @type {import('./$types').LayoutLoad} */
export async function load({ parent }) {
  /** @type {{ session?: { user?: any } }} */
  const parentData = await parent();
  const session = parentData.session;
  
  if (!session?.user) {
    throw redirect(307, '/login');
  }

  return {
    user: session.user
  };
} 