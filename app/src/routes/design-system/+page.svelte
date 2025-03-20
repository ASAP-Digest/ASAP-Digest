<!-- Design System Demo Page -->
<script>
  import CardExample from '$lib/components/examples/CardExample.svelte';
  import { Typography, Button } from '$lib/components/atoms';
</script>

<div class="container mx-auto py-[calc(var(--spacing-unit)*10)]">
  <Typography variant="h1">Design System</Typography>
  
  <Typography variant="p">
    This page demonstrates the atomic components we've created following our style guide.
    These components ensure consistent design across the application while providing
    flexibility for different use cases.
  </Typography>
  
  <div class="mt-[calc(var(--spacing-unit)*8)]">
    <Typography variant="h2">Typography</Typography>
    
    <div class="mt-[calc(var(--spacing-unit)*4)]">
      <Typography variant="h1">Heading 1</Typography>
      <Typography variant="h2">Heading 2</Typography>
      <Typography variant="h3">Heading 3</Typography>
      <Typography variant="h4">Heading 4</Typography>
      <Typography variant="h5">Heading 5</Typography>
      <Typography variant="h6">Heading 6</Typography>
      <Typography variant="p">Paragraph text looks like this. It should be readable and have good spacing.</Typography>
      <Typography variant="caption">This is a caption text, typically used for additional information.</Typography>
      <Typography variant="blockquote">This is a blockquote that can be used for highlighted text or quotes.</Typography>
      <Typography variant="code">const example = 'This is code formatting';</Typography>
    </div>
  </div>
  
  <div class="mt-[calc(var(--spacing-unit)*8)]">
    <Typography variant="h2">Buttons</Typography>
    
    <div class="mt-[calc(var(--spacing-unit)*4)] space-y-[calc(var(--spacing-unit)*4)]">
      <div class="flex flex-wrap gap-[calc(var(--spacing-unit)*3)]">
        <Button variant="primary">Primary</Button>
        <Button variant="secondary">Secondary</Button>
        <Button variant="outline">Outline</Button>
        <Button variant="ghost">Ghost</Button>
        <Button variant="destructive">Destructive</Button>
      </div>
      
      <div class="flex flex-wrap gap-[calc(var(--spacing-unit)*3)]">
        <Button variant="primary" size="sm">Small</Button>
        <Button variant="primary" size="md">Medium</Button>
        <Button variant="primary" size="lg">Large</Button>
      </div>
      
      <div class="flex flex-wrap gap-[calc(var(--spacing-unit)*3)]">
        <Button variant="primary" disabled>Disabled</Button>
      </div>
    </div>
  </div>
  
  <div class="mt-[calc(var(--spacing-unit)*8)]">
    <Typography variant="h2">Card Component</Typography>
    
    <div class="mt-[calc(var(--spacing-unit)*4)]">
      <CardExample />
    </div>
  </div>
</div> 