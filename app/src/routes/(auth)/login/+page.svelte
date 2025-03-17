<script>
  import { preventDefault } from 'svelte/legacy';

  import { LogIn, Mail, Lock, ArrowRight } from 'lucide-svelte';
  
  let email = $state('');
  let password = $state('');
  let rememberMe = $state(false);
  
  function handleSubmit() {
    // TODO: Implement login functionality
    console.log('Login submitted', { email, password, rememberMe });
  }
</script>

<div class="flex flex-col items-center justify-center py-8">
  <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold flex items-center justify-center gap-2">
        <LogIn size={24} />
        <span>Sign In</span>
      </h1>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
        Welcome back! Sign in to your account
      </p>
    </div>
    
    <form onsubmit={preventDefault(handleSubmit)} class="space-y-4">
      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">Email</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Mail size={16} class="text-gray-400" />
          </div>
          <input 
            type="email" 
            id="email" 
            bind:value={email} 
            required
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="you@example.com"
          />
        </div>
      </div>
      
      <div class="space-y-2">
        <label for="password" class="text-sm font-medium">Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Lock size={16} class="text-gray-400" />
          </div>
          <input 
            type="password" 
            id="password" 
            bind:value={password} 
            required
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="••••••••"
          />
        </div>
      </div>
      
      <div class="flex items-center justify-between">
        <label class="flex items-center">
          <input 
            type="checkbox" 
            bind:checked={rememberMe}
            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
          />
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
        </label>
        
        <a href="/auth/reset-password" class="text-sm text-primary hover:underline">
          Forgot password?
        </a>
      </div>
      
      <button 
        type="submit" 
        class="w-full flex items-center justify-center gap-2 bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90 transition-colors"
      >
        <span>Sign In</span>
        <ArrowRight size={16} />
      </button>
    </form>
    
    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Don't have an account? 
        <a href="/auth/register" class="text-primary hover:underline">
          Sign up
        </a>
      </p>
    </div>
  </div>
</div> 