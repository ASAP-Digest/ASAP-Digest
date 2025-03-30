<!-- Login Page -->
<script>
  import { goto } from '$app/navigation';
  import { signIn, useSession } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { LogIn } from '$lib/utils/lucide-icons.js';
  import Icon from "$lib/components/ui/Icon.svelte";

  let email = $state('');
  let password = $state('');
  let rememberMe = $state(false);
  let errorMessage = $state('');
  let isLoading = $state(false);

  async function handleSubmit() {
    try {
      isLoading = true;
      errorMessage = '';
      await signIn(email, password, rememberMe);
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

    <form class="mt-8 space-y-6" onsubmit={(e) => { e.preventDefault(); handleSubmit(e); }}>
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
          />
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Checkbox
              id="remember-me"
              bind:checked={rememberMe}
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
        class="w-full"
        disabled={isLoading}
      >
        {#if isLoading}
          <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
        {:else}
          <Icon icon={LogIn} class="mr-2" size={18} />
        {/if}
        {isLoading ? 'Signing in...' : 'Sign in'}
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