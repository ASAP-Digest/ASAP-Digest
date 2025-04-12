<!-- CardExample.svelte - Example using atomic components -->
<script>
  import { 
    Card, 
    CardHeader, 
    CardTitle, 
    CardDescription, 
    CardContent, 
    CardFooter,
    Button,
    Typography,
    Image
  } from '$lib/components/atoms';
</script>

<div class="max-w-[25rem] mx-auto">
  <Card hover={true}>
    {#snippet header()}
      <CardHeader>
        <!-- Restore dynamic CardTitle -->
        <CardTitle>Card Example</CardTitle>
        <CardDescription textContent="This card showcases our atomic components" />
      </CardHeader>
    {/snippet}

    {#snippet content()}
      <Image 
        src="https://picsum.photos/400/200" 
        alt="Example image" 
        aspectRatio="2/1" 
        lazy={true} 
      />
      <CardContent>
        <!-- Restore Typography, using textContent prop -->
        <Typography variant="p" textContent="This is an example of how our atomic components can be used together to create consistent UI elements following our style guide." />
        <!-- <p>Static content placeholder for SSR debugging.</p> -->
      </CardContent>
    {/snippet}

    {#snippet footer()}
      <CardFooter>
        <Button variant="primary" size="md">Primary Action</Button>
        <Button variant="ghost" size="md" className="ml-[0.75rem]">Secondary Action</Button>
      </CardFooter>
    {/snippet}
  </Card>
</div> 