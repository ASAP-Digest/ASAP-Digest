<script>
  import { page } from '$app/stores';
  import { Home, User, LogIn, Menu } from 'lucide-svelte';
</script>

<header class="bg-white dark:bg-gray-800 shadow-md">
  <div class="container mx-auto px-4 py-4 flex justify-between items-center">
    <div class="flex items-center">
      <a href="/" class="text-xl font-bold flex items-center gap-2">
        <img src="/logo.svg" alt="ASAP Digest" width="32" height="32" loading="lazy" onerror={() => {}} />
        <span class="text-primary">ASAP</span>Digest
      </a>
    </div>
    
    <nav class="hidden md:flex items-center space-x-6">
      <a 
        href="/" 
        class="flex items-center gap-2 {$page.url.pathname === '/' ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary'}"
      >
        <Home size={18} />
        <span>Home</span>
      </a>
      <a 
        href="/profile" 
        class="flex items-center gap-2 {$page.url.pathname === '/profile' ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary'}"
      >
        <User size={18} />
        <span>Profile</span>
      </a>
      <a 
        href="/auth/login" 
        class="flex items-center gap-2 {$page.url.pathname === '/auth/login' ? 'text-primary' : 'text-gray-600 dark:text-gray-300 hover:text-primary dark:hover:text-primary'}"
      >
        <LogIn size={18} />
        <span>Login</span>
      </a>
    </nav>
    
    <button class="md:hidden text-gray-600 dark:text-gray-300">
      <Menu size={24} />
    </button>
  </div>
</header> 