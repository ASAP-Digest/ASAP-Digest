<script>
  import { preventDefault } from 'svelte/legacy';
  import { goto } from '$app/navigation';
  import { LogIn, Mail, Lock, ArrowRight } from '$lib/utils/lucide-icons.js';
  import Icon from "$lib/components/ui/Icon.svelte";
  import { authStore, isLoading } from '$lib/auth';
  
  let email = $state('');
  let password = $state('');
  let rememberMe = $state(false);
  let errorMessage = $state('');
  
  async function handleSubmit() {
    try {
      errorMessage = '';
      await authStore.signIn(email, password, rememberMe);
      goto('/dashboard'); // Redirect to dashboard on success
    } catch (error) {
      errorMessage = error.message || 'Login failed. Please check your credentials.';
      console.error('Login error:', error);
    }
  }
</script>

<div class="flex flex-col items-center justify-center py-8">
  <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold flex items-center justify-center gap-2">
        <Icon icon={LogIn} size={24} />
        <span> ⚡️ ASAP Digest - Login </span>
      </h1>
      <p class="text-sm text-center text-gray-600 dark:text-gray-400 mt-2">
        Devour insights at AI speed
      </p>
    </div>
    
    {#if errorMessage}
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline">{errorMessage}</span>
      </div>
    {/if}
    
    <form onsubmit={preventDefault(handleSubmit)} class="space-y-4">
      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">Email</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon icon={Mail} size={16} class="text-gray-400" />
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
            <Icon icon={Lock} size={16} class="text-gray-400" />
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
            class="w-4 h-4 text-[hsl(var(--primary))] border-gray-300 rounded focus:ring-[hsl(var(--primary))]"
          />
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
        </label>
        
        <a href="/auth/reset-password" class="text-sm text-[hsl(var(--primary))] hover:underline">
          Forgot password?
        </a>
      </div>
      
      <button 
        type="submit" 
        class="w-full flex items-center justify-center gap-2 bg-[hsl(var(--primary))] text-white py-2 px-4 rounded-md hover:bg-[hsl(var(--primary))]/90 transition-colors"
      >
        <span>Sign In</span>
        <Icon icon={ArrowRight} size={16} />
      </button>
    </form>
    
    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Don't have an account? 
        <a href="/register" class="text-[hsl(var(--primary))] hover:underline">
          Sign up
        </a>
      </p>
    </div>
  </div>
</div> 