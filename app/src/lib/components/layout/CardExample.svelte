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

<div class="max-w-[400px] mx-auto">
  <Card hover={true}>
    <CardHeader>
      <CardTitle>Card Example</CardTitle>
      <CardDescription>This card showcases our atomic components</CardDescription>
    </CardHeader>
    
    <Image 
      src="https://picsum.photos/400/200" 
      alt="Example image" 
      aspectRatio="2/1" 
      lazy={true} 
    />
    
    <CardContent>
      <Typography variant="p">
        This is an example of how our atomic components can be used together to create 
        consistent UI elements following our style guide.
      </Typography>
    </CardContent>
    
    <CardFooter>
      <Button variant="primary" size="md">Primary Action</Button>
      <Button variant="ghost" size="md" className="ml-[0.75rem]">Secondary Action</Button>
    </CardFooter>
  </Card>
</div> 