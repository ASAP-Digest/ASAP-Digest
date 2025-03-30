<!-- Login Page -->
<script>
  import { goto } from '$app/navigation';
  import { auth, useSession } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { LogIn, Mail, Loader2 } from '$lib/utils/lucide-compat.js';

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
      await auth.signIn('email', { email, password, rememberMe });
      goto('/dashboard');
    } catch (error) {
      errorMessage = error.message || 'Login failed.';
      console.error('Login error:', error);
    } finally {
      isLoading = false;
    }
  }

  async function handleGoogleSignIn() {
    try {
      isLoading = true;
      errorMessage = '';
      await auth.signIn('google');
      goto('/dashboard');
    } catch (error) {
      errorMessage = error.message || 'Login failed.';
      console.error('Login error:', error);
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-[hsl(var(--background))]">
  <div class="w-full max-w-md space-y-8 px-4 py-8">
    <div class="text-center">
      <h1 class="text-2xl font-bold tracking-tight text-[hsl(var(--foreground))]">
        Welcome back
      </h1>
      <p class="mt-2 text-sm text-[hsl(var(--muted-foreground))]">
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
            placeholder="Enter your email"
            required
            autocomplete="email"
            disabled={isLoading}
          />
        </div>

        <div>
          <Label for="password">Password</Label>
          <Input
            id="password"
            type="password"
            bind:value={password}
            placeholder="Enter your password"
            required
            autocomplete="current-password"
            disabled={isLoading}
          />
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Checkbox
              id="remember-me"
              bind:checked={rememberMe}
              disabled={isLoading}
            />
            <Label for="remember-me" class="text-sm">Remember me</Label>
          </div>
          <a
            href="/forgot-password"
            class="text-sm text-[hsl(var(--primary))] hover:text-opacity-90"
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
          <Loader2 class="w-4 h-4 animate-spin" />
        {:else}
          <Mail class="w-4 h-4" />
        {/if}
        {isLoading ? 'Signing in...' : 'Sign in with Email'}
      </Button>

      <div class="relative">
        <div class="absolute inset-0 flex items-center">
          <span class="w-full border-t border-[hsl(var(--border))]"></span>
        </div>
        <div class="relative flex justify-center text-xs uppercase">
          <span class="bg-[hsl(var(--background))] px-2 text-[hsl(var(--muted-foreground))]">
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
          <Loader2 class="w-4 h-4 animate-spin" />
        {:else}
          <LogIn class="w-4 h-4" />
        {/if}
        Google
      </Button>

      <p class="mt-4 text-center text-sm text-[hsl(var(--muted-foreground))]">
        Don't have an account?
        <a
          href="/register"
          class="text-[hsl(var(--primary))] hover:text-opacity-90"
        >
          Sign up
        </a>
      </p>
    </form>
  </div>
</div> 