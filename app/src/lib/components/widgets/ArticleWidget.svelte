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
		article = /** @type {Article|null} */ (null),
		loading = false,
		showFullSummary = false,
		error = false
	} = $props();
	
	let expanded = $state(false);
	
	// Dynamically calculate truncated summary with $derived
	let summary = $derived(
		article?.summary && !showFullSummary 
			? article.summary.length > 120 
				? article.summary.substring(0, 120) + '...' 
				: article.summary
			: article?.summary || ''
	);
	
	// Format publication date with $derived
	let formattedDate = $derived(
		article?.publishedAt 
			? new Date(article.publishedAt).toLocaleDateString('en-US', {
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
		e.target.style.display = 'none';
	}
</script>

<BaseWidget 
	title="Latest Article" 
	loading={loading}
	icon={BookOpen}
>
	{#if loading}
		<div class="space-y-2">
			<div class="h-32 bg-muted/50 rounded-md animate-pulse"></div>
			<div class="h-4 w-3/4 bg-muted/50 rounded-md animate-pulse"></div>
			<div class="h-4 w-1/2 bg-muted/50 rounded-md animate-pulse"></div>
		</div>
	{:else if error}
		<div class="text-destructive text-sm">
			Failed to load article content
		</div>
	{:else if article}
		<div class="space-y-3">
			{#if article.featuredImage}
				<Image 
					src={article.featuredImage} 
					alt={article.title} 
					aspectRatio="16:9"
					className="rounded-md object-cover w-full"
					onerror={handleImageError}
				/>
			{/if}
			
			<div class="space-y-2">
				<Link href={`/article/${article.slug}`} variant="heading">
					{article.title}
				</Link>
				
				{#if article.author}
					<div class="text-sm text-muted-foreground flex items-center gap-2">
						<span>By {article.author}</span>
						{#if formattedDate}
							<span>•</span>
							<span>{formattedDate}</span>
						{/if}
					</div>
				{/if}
			</div>
			
			{#if summary}
				<Separator />
				<p class="text-sm text-muted-foreground">{summary}</p>
				
				{#if article.summary && article.summary.length > 120 && !showFullSummary}
					<Link href={`/article/${article.slug}`} variant="text" className="text-sm">
						Read more
					</Link>
				{/if}
			{/if}
		</div>
	{:else}
		<div class="text-center text-muted-foreground py-4">
			No article available
		</div>
	{/if}
</BaseWidget> 