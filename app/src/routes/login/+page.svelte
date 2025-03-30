<!-- login/+page.svelte -->
<script>
    import { authClient } from '$lib/auth-client';
    import { goto } from '$app/navigation';
    import { Button } from '$lib/components/ui/button';
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '$lib/components/ui/card';
    import { Input } from '$lib/components/ui/input';
    import { Label } from '$lib/components/ui/label';
    import { Loader2, LogIn, Mail } from 'lucide-svelte';
    
    const { data: session, signIn } = authClient.useSession();
    let email = '';
    let loading = $state(false);
    let error = $state('');
    
    // Redirect if already authenticated
    $effect(() => {
        if ($session) {
            goto('/dashboard');
        }
    });
    
    async function handleEmailSignIn() {
        loading = true;
        error = '';
        try {
            await signIn('email', { email });
        } catch (e) {
            error = e.message || 'An error occurred during sign in';
        } finally {
            loading = false;
        }
    }
    
    async function handleGoogleSignIn() {
        loading = true;
        error = '';
        try {
            await signIn('google');
        } catch (e) {
            error = e.message || 'An error occurred during sign in';
        } finally {
            loading = false;
        }
    }
</script>

<div class="container mx-auto max-w-md py-12">
    <Card>
        <CardHeader>
            <CardTitle class="text-2xl font-bold">Welcome Back</CardTitle>
            <CardDescription>Sign in to access your ASAP Digest account</CardDescription>
        </CardHeader>
        <CardContent>
            <form on:submit|preventDefault={handleEmailSignIn} class="space-y-4">
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input 
                        type="email" 
                        id="email" 
                        bind:value={email} 
                        placeholder="you@example.com"
                        disabled={loading}
                    />
                </div>
                
                {#if error}
                    <p class="text-[hsl(var(--destructive))] text-sm">{error}</p>
                {/if}
                
                <Button 
                    type="submit" 
                    class="w-full flex items-center justify-center gap-2"
                    disabled={loading}
                >
                    {#if loading}
                        <Loader2 class="w-4 h-4 animate-spin" />
                    {:else}
                        <Mail class="w-4 h-4" />
                    {/if}
                    Continue with Email
                </Button>
                
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-[hsl(var(--border))]" />
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
                    disabled={loading}
                >
                    {#if loading}
                        <Loader2 class="w-4 h-4 animate-spin" />
                    {:else}
                        <LogIn class="w-4 h-4" />
                    {/if}
                    Google
                </Button>
            </form>
        </CardContent>
    </Card>
</div> 