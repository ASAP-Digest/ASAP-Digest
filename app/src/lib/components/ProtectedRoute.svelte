<!-- ProtectedRoute.svelte -->
<script>
    import { useSession } from '$lib/auth-client';
    import { goto } from '$app/navigation';
    import { Loader2 } from 'lucide-svelte';
    
    const session = useSession();
    let { children } = $props();
    
    // Redirect to login if not authenticated
    $effect(() => {
        if ($session === null) {
            goto('/login');
        }
    });
</script>

{#if $session === undefined}
    <div class="flex justify-center items-center min-h-[50vh]">
        <Loader2 class="w-8 h-8 animate-spin text-[hsl(var(--primary))]" />
    </div>
{:else if $session}
    {@render children?.()}
{/if} 