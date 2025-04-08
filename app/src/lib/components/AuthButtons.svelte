<!-- AuthButtons.svelte -->
<script>
    import { auth, useSession } from '$lib/auth-client';
    import { Button } from '$lib/components/ui/button';
    import { LogIn, LogOut, Loader2 } from '$lib/utils/lucide-compat.js';
    
    const session = useSession();
    let loading = $state(false);
    
    async function handleSignIn() {
        loading = true;
        try {
            await auth.signIn('google');
        } catch (error) {
            console.error('Sign in error:', error);
        } finally {
            loading = false;
        }
    }
    
    async function handleSignOut() {
        loading = true;
        try {
            await auth.signOut();
        } catch (error) {
            console.error('Sign out error:', error);
        } finally {
            loading = false;
        }
    }
</script>

{#if $session}
    <Button 
        variant="outline" 
        on:click={handleSignOut} 
        disabled={loading}
        class="flex items-center gap-2"
    >
        {#if loading}
            <Loader2 class="w-4 h-4 animate-spin" />
        {:else}
            <LogOut class="w-4 h-4" />
        {/if}
        Sign Out
    </Button>
{:else}
    <Button 
        variant="default" 
        on:click={handleSignIn} 
        disabled={loading}
        class="flex items-center gap-2 text-[hsl(var(--primary-foreground))] bg-[hsl(var(--primary))]"
    >
        {#if loading}
            <Loader2 class="w-4 h-4 animate-spin" />
        {:else}
            <LogIn class="w-4 h-4" />
        {/if}
        Sign In
    </Button>
{/if} 