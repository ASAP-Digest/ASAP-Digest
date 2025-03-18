<script>
	import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { BookOpen, Share2, Volume2 } from 'lucide-svelte';
	import { onMount } from 'svelte';
	import { WIDGET_SPACING } from '$lib/styles/spacing.js';
	import BaseWidget from './BaseWidget.svelte';
	import { Newspaper } from 'lucide-svelte';
	
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

<BaseWidget title="Article" icon={Newspaper} loading={isLoading}>
	<div class="text-sm">
		<h3 class="font-medium text-base">{title}</h3>
		<p class="mt-1 line-clamp-3">{excerpt || "Loading..."}</p>
		<div class="flex justify-between items-center mt-2">
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
		</div>
	</div>
</BaseWidget> 