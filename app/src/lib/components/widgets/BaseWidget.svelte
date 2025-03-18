<script>
  import { Card } from '$lib/components/ui/card';
  import { CardHeader } from '$lib/components/ui/card';
  import { CardTitle } from '$lib/components/ui/card';
  import { CardContent } from '$lib/components/ui/card';
  import { fade } from 'svelte/transition';
  
  export let title = '';
  export let icon = null;
  export let loading = false;
  
  // Common widget functionality
</script>

<div in:fade={{ duration: 200 }}>
  <Card class="shadow-md w-full">
    <CardHeader>
      <CardTitle class="flex items-center text-lg">
        {#if icon}
          <svelte:component this={icon} class="mr-2 w-5 h-5" />
        {/if}
        {title}
      </CardTitle>
    </CardHeader>
    <CardContent>
      {#if loading}
        <div class="p-4 flex justify-center">
          <div class="animate-spin h-6 w-6 border-2 border-primary rounded-full border-t-transparent"></div>
        </div>
      {:else}
        <slot />
      {/if}
    </CardContent>
  </Card>
</div> 