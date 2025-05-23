<!-- Login Page -->
<script>
  import { enhance } from '$app/forms';
  import { browser } from '$app/environment';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { fly } from 'svelte/transition';
  import { auth, useSession } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { LogIn, Mail, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';

  const session = useSession();
  let email = $state('');
  let password = $state('');
  let rememberMe = $state(false);
  let errorMessage = $state('');
  let isLoading = $state(false);

  // Redirect if already authenticated
  $effect(() => {
    if ($session) {
      goto('/dashboard');
    }
  });

  async function handleEmailSignIn() {
    try {
      isLoading = true;
      errorMessage = '';
      // @ts-ignore - Assuming auth.signIn exists on the client auth object
      await auth.signIn('email', { email, password, rememberMe });
      goto('/dashboard');
    } catch (error) {
      console.error('Email login error:', error);
      errorMessage = (error instanceof Error) ? error.message : 'Invalid email or password';
    } finally {
      isLoading = false;
    }
  }

  async function handleGoogleSignIn() {
    try {
      isLoading = true;
      errorMessage = '';
      // @ts-ignore - Assuming auth.signIn exists on the client auth object
      await auth.signIn('google');
      goto('/dashboard');
    } catch (error) {
      console.error('Google login error:', error);
      errorMessage = (error instanceof Error) ? error.message : 'Google Sign-In failed';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-[hsl(var(--canvas-base))]">
  <div class="w-full max-w-md space-y-8 px-4 py-8">
    <div class="text-center">
      <h1 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] tracking-[var(--tracking-tight)] text-[hsl(var(--text-1))]">
        Welcome back
      </h1>
      <p class="mt-2 text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
        Sign in to your account
      </p>
    </div>

    <form class="mt-8 space-y-6" onsubmit={(e) => { e.preventDefault(); handleEmailSignIn(); }}>
      {#if errorMessage}
        <Alert variant="destructive">
          <AlertDescription>{errorMessage}</AlertDescription>
        </Alert>
      {/if}

      <div class="space-y-4">
        <div>
          <Label for="email">Email</Label>
          <Input
            id="email"
            type="email"
            bind:value={email}
            placeholder="you@example.com"
            required
            autocomplete="email"
            disabled={isLoading}
            class=""
          />
        </div>

        <div>
          <Label for="password">Password</Label>
          <Input
            id="password"
            type="password"
            bind:value={password}
            placeholder="Password"
            required
            autocomplete="current-password"
            disabled={isLoading}
            class=""
          />
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Checkbox
              id="remember-me"
              bind:checked={rememberMe}
              disabled={isLoading}
            />
            <Label for="remember-me" class="text-[var(--font-size-sm)]">Remember me</Label>
          </div>
          <a
            href="/forgot-password"
            class="text-[var(--font-size-sm)] text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]"
          >
            Forgot password?
          </a>
        </div>
      </div>

      <Button
        type="submit"
        class="w-full flex items-center justify-center gap-2"
        disabled={isLoading}
      >
        {#if isLoading}
          <Icon icon={Loader2} class="w-4 h-4 animate-spin" />
        {:else}
          <Icon icon={Mail} class="w-4 h-4" />
        {/if}
        {isLoading ? 'Signing in...' : 'Sign in with Email'}
      </Button>

      <div class="relative">
        <div class="absolute inset-0 flex items-center">
          <span class="w-full border-t border-[hsl(var(--border))]"></span>
        </div>
        <div class="relative flex justify-center text-[var(--font-size-xs)] uppercase">
          <span class="bg-[hsl(var(--canvas-base))] px-2 text-[hsl(var(--text-3))]">
            Or continue with
          </span>
        </div>
      </div>

      <Button 
        type="button"
        variant="outline"
        class="w-full flex items-center justify-center gap-2"
        on:click={handleGoogleSignIn}
        disabled={isLoading}
      >
        {#if isLoading}
          <Icon icon={Loader2} class="w-4 h-4 animate-spin" />
        {:else}
          <Icon icon={LogIn} class="w-4 h-4" />
        {/if}
        Google
      </Button>

      <p class="mt-4 text-center text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
        Don't have an account?
        <a
          href="/register"
          class="text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]"
        >
          Sign up
        </a>
      </p>
    </form>
  </div>
</div> 