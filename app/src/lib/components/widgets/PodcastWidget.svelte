<script>
	import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { Mic, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';
	import { onMount, createEventDispatcher } from 'svelte';
	
	/**
	 * @typedef {Object} PodcastProps
	 * @property {string} id - Unique identifier for the podcast
	 * @property {string} title - Title of the podcast
	 * @property {string} summary - Summary or description
	 * @property {number} episode - Episode number
	 * @property {number} duration - Duration in minutes
	 */
	
	/**
	 * Podcast Widget component displays a single podcast episode with essential information
	 * @type {PodcastProps}
	 */
	let {
		id = '',
		title = 'Podcast Title',
		summary = 'Episode summary goes here...',
		episode = 1,
		duration = 0
	} = $props();
	
	const dispatch = createEventDispatcher();
	
	/**
	 * Loading state for the podcast
	 * @type {boolean}
	 */
	let isLoading = $state(true);
	
	/**
	 * Error state for the podcast
	 * @type {string|null}
	 */
	let error = $state(null);
	
	/**
	 * Expanded state for the podcast
	 * @type {boolean}
	 */
	let expanded = $state(false);
	
	/**
	 * Audio playing state
	 * @type {boolean}
	 */
	let audioPlaying = $state(false);
	
	/**
	 * Text to speech reference
	 * @type {Object|null}
	 */
	let textToSpeech = null;
	
	/**
	 * Offline state detection
	 * @type {boolean}
	 */
	let isOffline = $state(false);
	
	/**
	 * Reduced motion preference
	 * @type {boolean}
	 */
	let prefersReducedMotion = false;
	
	onMount(async () => {
		// Check for offline status
		isOffline = !navigator.onLine;
		
		// Check for reduced motion preference
		prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		
		try {
			// Simulate API fetch for now
			// In the future, we'll use the WordPress API client
			// const response = await fetchPodcastById(id);
			
			// For now, just use props or default values
			if (isOffline) {
				const cachedData = localStorage.getItem(`podcast_${id}`);
				if (cachedData) {
					const parsed = JSON.parse(cachedData);
					title = parsed.title;
					summary = parsed.summary;
					episode = parsed.episode;
					duration = parsed.duration;
				}
			} else {
				// Save to cache
				localStorage.setItem(`podcast_${id}`, JSON.stringify({
					title,
					summary,
					episode,
					duration
				}));
			}
			
			isLoading = false;
		} catch (e) {
			console.error('Error loading podcast:', e);
			error = e instanceof Error ? e.message : String(e);
			isLoading = false;
		}
	});
	
	/**
	 * Handles toggle of audio playback
	 */
	function toggleAudio() {
		if (!audioPlaying) {
			// In the future, we'll use the TextToSpeech component
			// textToSpeech?.play();
			audioPlaying = true;
			dispatch('playAudio', { text: summary });
		} else {
			// textToSpeech?.stop();
			audioPlaying = false;
			dispatch('stopAudio');
		}
	}
	
	/**
	 * Handles toggle of expanded view
	 */
	function toggleExpand() {
		expanded = !expanded;
		dispatch('expand', { id, title, summary, expanded });
	}
	
	/**
	 * Handles sharing the podcast
	 */
	async function sharePodcast() {
		const shareData = {
			title: title,
			text: summary,
			url: window.location.href,
		};
		
		try {
			if (navigator.share) {
				await navigator.share(shareData);
				// Track analytics if available
				if (typeof window !== 'undefined' && 'gtag' in window && typeof window['gtag'] === 'function') {
					window['gtag']('event', 'share', { event_category: 'Podcast', event_label: title });
				}
			} else {
				await navigator.clipboard.writeText(`${title}: ${summary} - ${window.location.href}`);
				alert('Podcast link copied to clipboard!');
			}
		} catch (error) {
			console.error('Share error:', error);
		}
	}
</script>

<Card class="overflow-hidden h-full hover:shadow-lg transition-shadow duration-200 bg-white/80 border-2 border-cyan-400">
	{#if isLoading}
		<div class="p-4 flex items-center justify-center h-48">
			<div class="w-8 h-8 border-t-2 border-b-2 border-[hsl(var(--primary))] rounded-full animate-spin"></div>
		</div>
	{:else if error}
		<div class="p-4 text-center text-red-500">
			<p>Error loading podcast: {error}</p>
		</div>
	{:else}
		<CardHeader class="pb-4">
			<CardTitle class="text-lg font-serif flex items-center">
				<Mic class="mr-2 w-6 h-6" />{title}
			</CardTitle>
			<CardDescription class="flex items-center gap-2 text-xs text-[hsl(var(--muted-foreground))]">
				<span>Episode {episode}</span>
			</CardDescription>
		</CardHeader>
		
		<CardContent class="pb-4">
			<p class="text-sm text-[hsl(var(--foreground))] {expanded ? '' : 'line-clamp-3'}">
				{summary || (isOffline ? 'Offline content unavailable' : 'Loading...')}
			</p>
			
			<div class="flex justify-between items-center mt-4">
				<span class="text-sm text-[hsl(var(--muted-foreground))]">{duration} mins</span>
				
				<div class="flex gap-2">
					<Button 
						variant="outline" 
						size="sm" 
						onclick={toggleAudio}
						class={audioPlaying ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]' : ''}
						aria-label={audioPlaying ? 'Pause audio' : 'Play audio'}
					>
						{#if audioPlaying}
							<Pause class="w-4 h-4" />
						{:else}
							<Play class="w-4 h-4" />
						{/if}
					</Button>
					
					<Button 
						variant="outline" 
						size="sm" 
						onclick={toggleExpand}
						class={expanded ? 'rotate-180' : ''}
						aria-label={expanded ? 'Collapse podcast' : 'Expand podcast'}
					>
						<ChevronDown class="w-4 h-4" />
					</Button>
					
					<Button 
						variant="outline" 
						size="sm" 
						onclick={sharePodcast}
						aria-label="Share podcast"
					>
						<Share2 class="w-4 h-4" />
					</Button>
				</div>
			</div>
			
			{#if audioPlaying}
				<!-- Will add TextToSpeech component in the future -->
				<!-- <TextToSpeech bind:this={textToSpeech} text={summary} autoPlay={false} /> -->
			{/if}
			
			{#if expanded}
				<div class="mt-4 p-3 bg-[hsl(var(--muted))] rounded-md">
					<p class="text-sm">{summary}</p>
					<a href="/podcasts" class="text-cyan-600 hover:text-cyan-800 text-sm mt-2 inline-block">
						Listen to Full Podcast
					</a>
				</div>
			{/if}
		</CardContent>
	{/if}
</Card>

<style>
	@keyframes pulse-slow {
		0% { border-color: rgba(0, 255, 255, 1); }
		50% { border-color: rgba(0, 255, 255, 0.6); }
		100% { border-color: rgba(0, 255, 255, 1); }
	}
	
	:global(.animate-pulse-slow) {
		animation: pulse-slow 3s infinite;
	}
</style>	