<script>
  import { goto } from '$app/navigation';
  import { authClient } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { Mail, Loader2 } from 'lucide-svelte';

  let email = $state('');
  let isLoading = $state(false);
  let errorMessage = $state('');
  let successMessage = $state('');

  async function handleSubmit() {
    try {
      isLoading = true;
      errorMessage = '';
      successMessage = '';
      
      await authClient.sendPasswordResetEmail(email);
      successMessage = 'Password reset instructions have been sent to your email';
      
      // Redirect to login after 3 seconds
      setTimeout(() => goto('/login'), 3000);
    } catch (error) {
      errorMessage = error.message || 'Failed to send reset email';
      console.error('Password reset error:', error);
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-[hsl(var(--background))]">
  <div class="w-full max-w-md space-y-8 px-4 py-8">
    <div class="text-center">
      <h1 class="text-2xl font-bold tracking-tight text-[hsl(var(--foreground))]">
        Reset Password
      </h1>
      <p class="mt-2 text-sm text-[hsl(var(--muted-foreground))]">
        Enter your email to receive reset instructions
      </p>
    </div>

    <form class="mt-8 space-y-6" on:submit|preventDefault={handleSubmit}>
      {#if errorMessage}
        <Alert variant="destructive">
          <AlertDescription>{errorMessage}</AlertDescription>
        </Alert>
      {/if}

      {#if successMessage}
        <Alert>
          <AlertDescription>{successMessage}</AlertDescription>
        </Alert>
      {/if}

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

      <Button
        type="submit"
        class="w-full"
        disabled={isLoading}
      >
        {#if isLoading}
          <Loader2 class="w-4 h-4 mr-2 animate-spin" />
          Sending...
        {:else}
          <Mail class="w-4 h-4 mr-2" />
          Send Reset Link
        {/if}
      </Button>

      <div class="text-center">
        <a
          href="/login"
          class="text-sm text-[hsl(var(--primary))] hover:text-opacity-90"
        >
          Back to Login
        </a>
      </div>
    </form>
  </div>
</div> 