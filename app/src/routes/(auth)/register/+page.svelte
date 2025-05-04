<!-- Registration Page -->
<script>
  import { goto } from '$app/navigation';
  import { auth, useSession } from '$lib/auth-client';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Label } from '$lib/components/ui/label';
  import { Checkbox } from '$lib/components/ui/checkbox';
  import { Alert, AlertDescription } from '$lib/components/ui/alert';
  import { UserPlus, Loader2 } from '$lib/utils/lucide-compat.js';
  import Icon from "$lib/components/ui/icon/icon.svelte";

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

  /**
   * Handles the form submission for registration.
   * @param {Event} event The form submission event.
   */
  async function handleSubmit(event) {
    event.preventDefault();
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

      // @ts-ignore - Assuming auth.signUp exists on the client auth object
      await auth.signUp(email, password, name);
      goto('/dashboard');
    } catch (error) {
      console.error('Registration error:', error);
      errorMessage = (error instanceof Error) ? error.message : 'Registration failed. Please try again.';
    } finally {
      isLoading = false;
    }
  }
</script>

<div class="flex min-h-screen items-center justify-center bg-[hsl(var(--canvas-base))]">
  <div class="w-full max-w-md space-y-8 px-4 py-8">
    <div class="text-center">
      <h1 class="text-[var(--font-size-lg)] font-[var(--font-weight-semibold)] tracking-[var(--tracking-tight)] text-[hsl(var(--text-1))]">
        Create an account
      </h1>
      <p class="mt-2 text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
        Join ASAP Digest today
      </p>
    </div>

    <form class="space-y-6" onsubmit={handleSubmit}>
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
            class=""
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
            class=""
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
            class=""
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
            class=""
          />
        </div>

        <div class="flex items-center gap-2">
          <Checkbox
            id="terms"
            bind:checked={acceptTerms}
          />
          <Label for="terms" class="text-[var(--font-size-sm)]">
            I agree to the
            <a
              href="/terms"
              class="text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]"
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
          <Icon icon={Loader2} class="mr-2 h-5 w-5 animate-spin" />
        {:else}
          <Icon icon={UserPlus} class="mr-2" size={18} />
        {/if}
        {isLoading ? 'Creating account...' : 'Create account'}
      </Button>

      <p class="mt-4 text-center text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
        Already have an account?
        <a
          href="/login"
          class="text-[hsl(var(--link))] hover:text-[hsl(var(--link-hover))]"
        >
          Sign in
        </a>
      </p>
    </form>
  </div>
</div> 