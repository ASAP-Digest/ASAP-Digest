<script>
    import { Link } from '$lib/components/atoms';
    import { Card, CardContent, CardHeader, CardTitle } from '$lib/components/ui/card';

    /**
     * @param {MouseEvent & { currentTarget: EventTarget & HTMLAnchorElement }} event
     */
    function handleClick(event) {
        // Prevent default navigation for demo links
        event.preventDefault();
        console.log('Link clicked:', (event.currentTarget as HTMLAnchorElement).href);
    }
</script>

<div class="p-[2rem] bg-[hsl(var(--background))] min-h-screen">
    <h1 class="text-[2rem] font-[700] mb-[2rem]">Link Component Demo</h1>
    
    <Card class="">
        <CardHeader class="">
            <CardTitle class="">Link Variants</CardTitle>
        </CardHeader>
        <CardContent class="">
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">Default Link</h3>
                <Link href="#" on:click={handleClick}>Default link style</Link>
            </div>
            
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">Underlined Link</h3>
                <Link href="#" variant="underlined" on:click={handleClick}>Underlined link style</Link>
            </div>
            
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">Muted Link</h3>
                <Link href="#" variant="muted" on:click={handleClick}>Muted link style</Link>
            </div>
            
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">External Link</h3>
                <Link href="https://asapdigest.com" external>External link with icon</Link>
            </div>
            
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">External Underlined Link</h3>
                <Link href="https://asapdigest.com" variant="underlined" external>External underlined link</Link>
            </div>
            
            <div>
                <h3 class="text-[1.25rem] font-[600] mb-[0.5rem]">External Muted Link</h3>
                <Link href="https://asapdigest.com" variant="muted" external>External muted link</Link>
            </div>
        </CardContent>
    </Card>
    
    <Card class="mt-[2rem]">
        <CardHeader class="">
            <CardTitle class="">Link States</CardTitle>
        </CardHeader>
        <CardContent class="">
            <p class="mb-[1rem]">
                Hover over, click, and use keyboard navigation to see different link states:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[1rem]">
                <div class="p-[1rem] border border-[hsl(var(--border))] rounded-[0.5rem]">
                    <span class="block mb-[0.5rem] text-[hsl(var(--muted-foreground))]">Default (:link)</span>
                    <Link href="#" on:click={handleClick}>Default state</Link>
                </div>
                
                <div class="p-[1rem] border border-[hsl(var(--border))] rounded-[0.5rem]">
                    <span class="block mb-[0.5rem] text-[hsl(var(--muted-foreground))]">Hover (:hover)</span>
                    <div class="relative">
                        <Link href="#" on:click={handleClick}>Hover state</Link>
                        <span class="absolute top-0 left-0 w-full h-full bg-[transparent] pointer-events-none after:content-['(Hover_over_me)'] after:absolute after:text-[0.75rem] after:text-[hsl(var(--muted-foreground))] after:top-[calc(100%+0.25rem)] after:left-0"></span>
                    </div>
                </div>
                
                <div class="p-[1rem] border border-[hsl(var(--border))] rounded-[0.5rem]">
                    <span class="block mb-[0.5rem] text-[hsl(var(--muted-foreground))]">Active (:active)</span>
                    <div class="relative">
                        <Link href="#" on:click={handleClick}>Active state</Link>
                        <span class="absolute top-0 left-0 w-full h-full bg-[transparent] pointer-events-none after:content-['(Click_and_hold)'] after:absolute after:text-[0.75rem] after:text-[hsl(var(--muted-foreground))] after:top-[calc(100%+0.25rem)] after:left-0"></span>
                    </div>
                </div>
                
                <div class="p-[1rem] border border-[hsl(var(--border))] rounded-[0.5rem]">
                    <span class="block mb-[0.5rem] text-[hsl(var(--muted-foreground))]">Focus (:focus-visible)</span>
                    <div class="relative">
                        <Link href="#" on:click={handleClick}>Focus state</Link>
                        <span class="absolute top-0 left-0 w-full h-full bg-[transparent] pointer-events-none after:content-['(Tab_to_me)'] after:absolute after:text-[0.75rem] after:text-[hsl(var(--muted-foreground))] after:top-[calc(100%+0.25rem)] after:left-0"></span>
                    </div>
                </div>
                
                <div class="p-[1rem] border border-[hsl(var(--border))] rounded-[0.5rem]">
                    <span class="block mb-[0.5rem] text-[hsl(var(--muted-foreground))]">Visited (:visited)</span>
                    <Link href="https://example.com">Visited state</Link>
                </div>
            </div>
        </CardContent>
    </Card>
    
    <Card class="mt-[2rem]">
        <CardHeader class="">
            <CardTitle class="">Usage Examples</CardTitle>
        </CardHeader>
        <CardContent class="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-[2rem]">
                <div>
                    <h3 class="text-[1.25rem] font-[600] mb-[1rem]">Navigation</h3>
                    <nav class="flex gap-[1rem]">
                        <Link href="#" variant="underlined" on:click={handleLinkClick}>Home</Link>
                        <Link href="#" variant="underlined" on:click={handleLinkClick}>Features</Link>
                        <Link href="#" variant="underlined" on:click={handleLinkClick}>Pricing</Link>
                        <Link href="#" variant="underlined" on:click={handleLinkClick}>About</Link>
                    </nav>
                </div>
                
                <div>
                    <h3 class="text-[1.25rem] font-[600] mb-[1rem]">Article Links</h3>
                    <p class="mb-[0.5rem]">
                        This is a paragraph with a <Link href="#" on:click={handleLinkClick}>regular link</Link> inside the text, 
                        along with an <Link href="https://example.com" external>external link</Link> that shows an icon.
                    </p>
                    <p>
                        For less prominent links, you can use <Link href="#" variant="muted" on:click={handleLinkClick}>muted links</Link> within content.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</div> 