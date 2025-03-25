<script>
  import { preventDefault } from 'svelte/legacy';
  import { goto } from '$app/navigation';
  import { UserPlus, Mail, Lock, User, ArrowRight } from '$lib/utils/lucide-icons.js';
  import Icon from "$lib/components/ui/Icon.svelte";
  import { authStore, isLoading } from '$lib/auth';
  
  let name = $state('');
  let email = $state('');
  let password = $state('');
  let confirmPassword = $state('');
  let acceptTerms = $state(false);
  let errorMessage = $state('');
  
  async function handleSubmit() {
    try {
      errorMessage = '';
      
      if (password !== confirmPassword) {
        errorMessage = 'Passwords do not match';
        return;
      }
      
      console.log('Attempting registration with:', { email, name, acceptTerms });
      
      await authStore.register(email, password, name);
      console.log('Registration successful, redirecting to dashboard');
      goto('/dashboard'); // Redirect to dashboard on success
    } catch (error) {
      console.error('Registration error details:', error);
      errorMessage = error.message || 'Registration failed. Please try again.';
    }
  }
</script>

<div class="flex flex-col items-center justify-center py-8">
  <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border border-gray-200 dark:border-gray-700">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold flex items-center justify-center gap-2">
        <Icon icon={UserPlus} size={24} />
        <span>Create Account</span>
      </h1>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
        Sign up for your personalized digest
      </p>
    </div>
    
    {#if errorMessage}
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
        <span class="block sm:inline">{errorMessage}</span>
      </div>
    {/if}
    
    <form onsubmit={preventDefault(handleSubmit)} class="space-y-4">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Full Name</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <User size={16} class="text-gray-400" />
          </div>
          <input 
            type="text" 
            id="name" 
            bind:value={name} 
            required
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="John Doe"
          />
        </div>
      </div>
      
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
      
      <div class="space-y-2">
        <label for="confirmPassword" class="text-sm font-medium">Confirm Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Lock size={16} class="text-gray-400" />
          </div>
          <input 
            type="password" 
            id="confirmPassword" 
            bind:value={confirmPassword} 
            required
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="••••••••"
          />
        </div>
      </div>
      
      <div>
        <label class="flex items-start">
          <input 
            type="checkbox" 
            bind:checked={acceptTerms}
            required
            class="w-4 h-4 mt-1 text-primary border-gray-300 rounded focus:ring-primary"
          />
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
            I agree to the 
            <a href="/terms" class="text-primary hover:underline">Terms of Service</a>
            and
            <a href="/privacy" class="text-primary hover:underline">Privacy Policy</a>
          </span>
        </label>
      </div>
      
      <button 
        type="submit" 
        class="w-full flex items-center justify-center gap-2 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] py-2 px-4 rounded-md hover:bg-[hsl(var(--primary)/0.9)] transition-colors"
        disabled={$isLoading}
      >
        <span>{$isLoading ? 'Creating Account...' : 'Create Account'}</span>
        <Icon icon={ArrowRight} size={16} />
      </button>
    </form>
    
    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Already have an account? 
        <a href="/login" class="text-[hsl(var(--primary))] hover:underline">
          Sign in
        </a>
      </p>
    </div>
  </div>
</div> 