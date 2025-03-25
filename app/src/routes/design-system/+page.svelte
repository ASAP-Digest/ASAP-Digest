<!-- Design System Demo Page -->
<script>
  import CardExample from '$lib/components/layout/CardExample.svelte';
  import { Button, Link, Typography } from '$lib/components/atoms';
  import DesignSystemLayout from '$lib/components/layout/DesignSystemLayout.svelte';
  
  /**
   * Handle link click events
   * @param {Event} event - The click event
   */
  function handleLinkClick(event) {
    // Prevent default navigation for demo links
    event.preventDefault();
    
    // Type assertion for the event target
    const target = /** @type {HTMLAnchorElement} */ (event.currentTarget);
    console.log('Link clicked:', target.href);
  }
</script>

<DesignSystemLayout>
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
          <Button variant="primary" className="btn-primary">Primary</Button>
          <Button variant="secondary" className="btn-secondary">Secondary</Button>
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
      <Typography variant="h2">Links</Typography>
      
      <div class="mt-[calc(var(--spacing-unit)*4)] space-y-[calc(var(--spacing-unit)*4)]">
        <div class="flex flex-wrap gap-[calc(var(--spacing-unit)*6)]">
          <Link href="#" on:click={handleLinkClick}>Default Link</Link>
          <Link href="#" variant="underlined" on:click={handleLinkClick}>Underlined Link</Link>
          <Link href="#" variant="muted" on:click={handleLinkClick}>Muted Link</Link>
          <Link href="https://asapdigest.com" external>External Link</Link>
        </div>
        
        <Typography variant="p">
          <Link href="/design-system/link-demo" variant="underlined">View the full Link component demo</Link> for more examples and states.
        </Typography>
      </div>
    </div>
    
    <div class="mt-[calc(var(--spacing-unit)*8)]">
      <Typography variant="h2">Card Component</Typography>
      
      <div class="mt-[calc(var(--spacing-unit)*4)]">
        <CardExample />
      </div>
    </div>
  </div>
</DesignSystemLayout> 