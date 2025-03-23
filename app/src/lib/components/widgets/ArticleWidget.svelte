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
		/** @type {HTMLImageElement|null} */
		const target = /** @type {HTMLImageElement|null} */ (e.target);
		if (target) {
			target.style.display = 'none';
		}
	}
</script>

<BaseWidget 
	title="Latest Article" 
	loading={loading}
	className="transition-all duration-200"
>
	{#if loading}
		<div class="space-y-2">
			<div class="h-32 bg-[hsl(var(--muted)/0.5)] rounded-md animate-pulse"></div>
			<div class="h-4 w-3/4 bg-[hsl(var(--muted)/0.5)] rounded-md animate-pulse"></div>
			<div class="h-4 w-1/2 bg-[hsl(var(--muted)/0.5)] rounded-md animate-pulse"></div>
		</div>
	{:else if error}
		<div class="text-[hsl(var(--destructive))] text-sm">
			Failed to load article content
		</div>
	{:else if article}
		<div class="space-y-3">
			{#if article.featuredImage}
				<img 
					src={article.featuredImage} 
					alt={article.title} 
					class="rounded-md object-cover w-full aspect-video transition-opacity duration-300"
					onerror={(e) => handleImageError(e)}
				/>
			{/if}
			
			<div class="space-y-2">
				<Link href={`/article/${article.slug}`} variant="heading">
					{article.title}
				</Link>
				
				{#if article.author}
					<div class="text-sm text-[hsl(var(--muted-foreground))] flex items-center gap-2">
						<span>By {article.author}</span>
						{#if formattedDate}
							<span>•</span>
							<span>{formattedDate}</span>
						{/if}
					</div>
				{/if}
			</div>
			
			{#if summary}
				<hr class="border-t border-[hsl(var(--border))]" />
				<p class="text-sm text-[hsl(var(--muted-foreground))]">{summary}</p>
				
				{#if article.summary && article.summary.length > 120 && !showFullSummary}
					<Link href={`/article/${article.slug}`} variant="text" className="text-sm hover:text-[hsl(var(--primary))] transition-colors duration-200">
						Read more
					</Link>
				{/if}
			{/if}
		</div>
	{:else}
		<div class="text-center text-[hsl(var(--muted-foreground))] py-4">
			No article available
		</div>
	{/if}
</BaseWidget> 