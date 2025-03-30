<!-- Registration Page -->
<script>
  import { goto } from '$app/navigation';
  import { auth, useSession } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { UserPlus } from '$lib/utils/lucide-icons.js';
  import Icon from "$lib/components/ui/Icon.svelte";

  const session = useSession();
  let name = $state('');
  let email = $state('');
  let password = $state('');
  let confirmPassword = $state('');
  let acceptTerms = $state(false);
  let errorMessage = $state('');
  let isLoading = $state(false);

  // Redirect if already authenticated
  $effect(() => {
    if ($session) {
      goto('/dashboard');
    }
  });

  async function handleSubmit() {
    try {
      isLoading = true;
      errorMessage = '';

      if (password !== confirmPassword) {
        errorMessage = 'Passwords do not match';
        return;
      }

      if (!acceptTerms) {
        errorMessage = 'Please accept the terms and conditions';
        return;
      }

      await auth.signUp(email, password, name);
      goto('/dashboard');
    } catch (error) {
      errorMessage = error.message || 'Registration failed.';
      console.error('Registration error:', error);
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-[hsl(var(--background))]">
  <div class="w-full max-w-md space-y-8 px-4 py-8">
    <div class="text-center">
      <h1 class="text-2xl font-bold tracking-tight text-[hsl(var(--foreground))]">
        Create an account
      </h1>
      <p class="mt-2 text-sm text-[hsl(var(--muted-foreground))]">
        Join ASAP Digest today
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
          <Label for="name">Full Name</Label>
          <Input
            id="name"
            type="text"
            bind:value={name}
            placeholder="Enter your full name"
            required
            autocomplete="name"
          />
        </div>

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
            placeholder="Create a password"
            required
            autocomplete="new-password"
          />
        </div>

        <div>
          <Label for="confirm-password">Confirm Password</Label>
          <Input
            id="confirm-password"
            type="password"
            bind:value={confirmPassword}
            placeholder="Confirm your password"
            required
            autocomplete="new-password"
          />
        </div>

        <div class="flex items-center gap-2">
          <Checkbox
            id="terms"
            bind:checked={acceptTerms}
          />
          <Label for="terms" class="text-sm">
            I agree to the
            <a
              href="/terms"
              class="text-[hsl(var(--primary))] hover:text-opacity-90"
            >
              terms and conditions
            </a>
          </Label>
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
          <Icon icon={UserPlus} class="mr-2" size={18} />
        {/if}
        {isLoading ? 'Creating account...' : 'Create account'}
      </Button>

      <p class="mt-4 text-center text-sm text-[hsl(var(--muted-foreground))]">
        Already have an account?
        <a
          href="/login"
          class="text-[hsl(var(--primary))] hover:text-opacity-90"
        >
          Sign in
        </a>
      </p>
    </form>
  </div>
</div> 