<script>
	import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { BookOpen, Share2, Volume2 } from 'lucide-svelte';
	import { onMount } from 'svelte';
	import { WIDGET_SPACING } from '$lib/styles/spacing.js';
	
	/**
	 * @typedef {Object} ArticleProps
	 * @property {string} id - Unique identifier for the article
	 * @property {string} title - Title of the article
	 * @property {string} excerpt - Short excerpt or summary
	 * @property {string} source - Source of the article
	 * @property {string} sourceUrl - URL to the source
	 * @property {string} [imageUrl] - Optional featured image URL
	 * @property {string} [date] - Publication date
	 * @property {string[]} [tags] - Array of tags
	 */
	
	/**
	 * Article Widget component displays a single article with essential information
	 * @type {ArticleProps}
	 */
	let { 
		id = '',
		title = 'Article Title',
		excerpt = 'Article excerpt or summary goes here...',
		source = 'Source Name',
		sourceUrl = '#',
		imageUrl = '',
		date = new Date().toLocaleDateString(),
		tags = []
	} = $props();
	
	/**
	 * Loading state for the article
	 * @type {boolean}
	 */
	let isLoading = $state(false);
	
	/**
	 * Error state for the article
	 * @type {string|null}
	 */
	let error = $state(null);
	
	/**
	 * Handles click on the read more button
	 */
	function handleReadMore() {
		console.log('Read more clicked for article ID:', id);
		// Will implement navigation to article detail page
	}
	
	/**
	 * Handles click on the share button
	 */
	function handleShare() {
		console.log('Share clicked for article ID:', id);
		// Will implement share functionality
	}
	
	/**
	 * Handles click on the text-to-speech button
	 */
	function handleTextToSpeech() {
		console.log('TTS clicked for article ID:', id);
		// Will implement text-to-speech functionality
	}
	
	// Truncate the excerpt if it's too long
	const MAX_EXCERPT_LENGTH = 150;
	$effect(() => {
		if (excerpt && excerpt.length > MAX_EXCERPT_LENGTH) {
			excerpt = excerpt.substring(0, MAX_EXCERPT_LENGTH) + '...';
		}
	});
</script>

<Card class="overflow-hidden h-full hover:shadow-lg transition-shadow duration-200 hover:-translate-y-1 {WIDGET_SPACING.wrapper}">
	{#if isLoading}
		<div class="flex items-center justify-center h-64">
			<div class="w-8 h-8 border-t-2 border-b-2 border-[hsl(var(--primary))] rounded-full animate-spin"></div>
		</div>
	{:else if error}
		<div class="text-center text-red-500 {WIDGET_SPACING.content}">
			<p>Error loading article: {error}</p>
		</div>
	{:else}
		<CardHeader class="pb-0 {WIDGET_SPACING.header}">
			{#if imageUrl}
				<div class="h-32 overflow-hidden rounded-md mb-3">
					<img src={imageUrl} alt={title} class="w-full h-full object-cover" />
				</div>
			{/if}
			<CardTitle class="text-lg font-semibold line-clamp-2">{title}</CardTitle>
			<CardDescription class="flex items-center gap-2 text-xs text-gray-500">
				<span>{source}</span>
				<span class="inline-block w-1 h-1 rounded-full bg-gray-400"></span>
				<span>{date}</span>
			</CardDescription>
		</CardHeader>
		<CardContent class={WIDGET_SPACING.content}>
			<p class="text-sm text-[hsl(var(--muted-foreground))] line-clamp-3 mb-4">{excerpt}</p>
			{#if tags && tags.length > 0}
				<div class="flex flex-wrap gap-2 mb-2">
					{#each tags as tag}
						<span class="text-xs bg-[hsl(var(--muted))] text-[hsl(var(--muted-foreground))] px-2 py-1 rounded-md">{tag}</span>
					{/each}
				</div>
			{/if}
		</CardContent>
		<CardFooter class="flex justify-between pt-4 border-t {WIDGET_SPACING.footer}">
			<Button variant="outline" size="sm" onclick={handleReadMore} class="flex items-center">
				<BookOpen class="h-4 w-4 mr-2" />
				<span>Read</span>
			</Button>
			<div class="flex gap-2">
				<Button variant="ghost" size="sm" onclick={handleTextToSpeech} class="flex items-center">
					<Volume2 class="h-4 w-4" />
				</Button>
				<Button variant="ghost" size="sm" onclick={handleShare} class="flex items-center">
					<Share2 class="h-4 w-4" />
				</Button>
			</div>
		</CardFooter>
	{/if}
</Card> 