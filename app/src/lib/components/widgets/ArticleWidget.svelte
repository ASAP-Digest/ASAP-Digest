<script>
	import { BookOpen } from '$lib/utils/lucide-icons.js';
	import BaseWidget from './BaseWidget.svelte';
	import { Separator } from '$lib/components/ui/separator';
	import Link from '$lib/components/atoms/Link.svelte';
	import Image from '$lib/components/atoms/Image.svelte';
	
	/** @typedef {Object} Article
	 * @property {string} title - Article title
	 * @property {string} slug - Article slug
	 * @property {string} [summary] - Article summary
	 * @property {string} [featuredImage] - Featured image URL
	 * @property {string} [author] - Article author
	 * @property {string} [publishedAt] - Publication date
	 */
	
	let {
		// Direct props mode (from home page)
		id = '',
		title = '',
		excerpt = '',
		source = '',
		date = '',
		tags = [],
		sourceUrl = '',
		
		// Article object mode
		article = /** @type {Article|null} */ (null),
		
		// Other props
		loading = false,
		showFullSummary = false,
		error = false
	} = $props();
	
	let expanded = $state(false);
	
	// Flag to determine if we're using direct props or article object
	let usingDirectProps = $derived(!!title || !!excerpt || !!source);
	
	// Dynamically calculate combined article data
	let displayTitle = $derived(usingDirectProps ? title : article?.title || '');
	let displayExcerpt = $derived(usingDirectProps ? excerpt : article?.summary || '');
	let displaySource = $derived(usingDirectProps ? source : article?.author || '');
	let displayDate = $derived(usingDirectProps ? date : article?.publishedAt || '');
	
	// Dynamically calculate truncated summary with $derived
	let summary = $derived(
		displayExcerpt && !showFullSummary 
			? displayExcerpt.length > 120 
				? displayExcerpt.substring(0, 120) + '...' 
				: displayExcerpt
			: displayExcerpt || ''
	);
	
	// Format publication date with $derived
	let formattedDate = $derived(
		displayDate 
			? new Date(displayDate).toLocaleDateString('en-US', {
				year: 'numeric',
				month: 'short',
				day: 'numeric'
			})
			: ''
	);
	
	/**
	 * Handle image error
	 * @param {Event} e - Event object
	 */
	function handleImageError(e) {
		/** @type {HTMLImageElement|null} */
		const target = /** @type {HTMLImageElement|null} */ (e.target);
		if (target) {
			target.style.display = 'none';
		}
	}
</script>

<BaseWidget 
	title={displayTitle || "Latest Article"}
	loading={loading}
	className="transition-all duration-[var(--duration-normal)] ease-[var(--ease-out)]"
>
	{#if loading}
		<div class="space-y-[calc(var(--spacing-unit)*2)]">
			<div class="h-32 bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse"></div>
			<div class="h-4 w-3/4 bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse"></div>
			<div class="h-4 w-1/2 bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse"></div>
		</div>
	{:else if error}
		<div class="text-[hsl(var(--destructive))] text-[var(--font-size-sm)]">
			Failed to load article content
		</div>
	{:else if displayTitle || (article && article.title)}
		<div class="space-y-[calc(var(--spacing-unit)*4)]">
			{#if article && article.featuredImage}
				<img 
					src={article.featuredImage} 
					alt={displayTitle} 
					class="rounded-[var(--radius-md)] object-cover w-full aspect-video transition-opacity duration-[var(--duration-normal)]"
					onerror={(e) => handleImageError(e)}
				/>
			{/if}
			
			<div class="space-y-[calc(var(--spacing-unit)*3)]">
				{#if sourceUrl || (article && article.slug)}
					<Link href={sourceUrl || (article ? `/article/${article.slug}` : '')} variant="heading">
						{displayTitle}
					</Link>
				{:else}
					<h3 class="text-[var(--font-size-lg)] font-[var(--font-weight-medium)] text-[hsl(var(--foreground))]">
						{displayTitle}
					</h3>
				{/if}
				
				{#if displaySource}
					<div class="text-[var(--font-size-sm)] text-[hsl(var(--muted-foreground))] flex items-center gap-[calc(var(--spacing-unit)*2)]">
						<span>{#if article?.author}By {/if}{displaySource}</span>
						{#if formattedDate}
							<span>•</span>
							<span>{formattedDate}</span>
						{/if}
					</div>
				{/if}
			</div>
			
			{#if summary}
				<hr class="border-t border-[hsl(var(--border))]" />
				<p class="text-[var(--font-size-sm)] text-[hsl(var(--muted-foreground))]">{summary}</p>
				
				{#if displayExcerpt && displayExcerpt.length > 120 && !showFullSummary}
					<Link href={sourceUrl || (article ? `/article/${article.slug}` : '')} variant="text" 
						  className="text-[var(--font-size-sm)] hover:text-[hsl(var(--primary))] transition-colors duration-[var(--duration-normal)]">
						Read more
					</Link>
				{/if}
			{/if}
		</div>
	{:else}
		<div class="text-center text-[hsl(var(--muted-foreground))] py-[calc(var(--spacing-unit)*4)]">
			No article available
		</div>
	{/if}
</BaseWidget> 