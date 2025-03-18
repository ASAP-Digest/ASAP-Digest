<script>
	import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { Mic, Play, Pause, ChevronDown, Share2 } from 'lucide-svelte';
	import { onMount, createEventDispatcher } from 'svelte';
	import { WIDGET_SPACING } from '$lib/styles/spacing.js';
	
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

<Card class="overflow-hidden border-[#0891b2] hover:shadow-[0_10px_15px_-3px_rgba(0,0,0,0.1),_0_4px_6px_-2px_rgba(0,0,0,0.05)] transition-shadow duration-200 {WIDGET_SPACING.wrapper}">
	{#if isLoading}
		<div class="flex items-center justify-center h-[9rem]">
			<div class="w-[2rem] h-[2rem] border-t-2 border-b-2 border-[hsl(var(--primary))] rounded-full animate-spin"></div>
		</div>
	{:else if error}
		<div class="text-center text-[#ef4444] {WIDGET_SPACING.content}">
			<p>Error loading podcast: {error}</p>
		</div>
	{:else}
		<CardHeader class={WIDGET_SPACING.header + " pb-0"}>
			<div class="flex items-start justify-between">
				<div>
					<div class="flex items-center gap-2">
						<Mic class="h-[1.25rem] w-[1.25rem] text-[hsl(var(--primary))]" />
						<CardTitle class="text-lg font-semibold">{title}</CardTitle>
					</div>
					<CardDescription class="text-xs text-[hsl(var(--muted-foreground))] mt-[0.25rem]">
						Episode {episode} • {duration} min
					</CardDescription>
				</div>
				<Button 
					variant="ghost" 
					size="sm" 
					class="rounded-full p-[0.25rem] h-[2rem] w-[2rem]" 
					onclick={() => expanded = !expanded}
					aria-label={expanded ? "Collapse podcast" : "Expand podcast"}
				>
					<ChevronDown class="h-[1.25rem] w-[1.25rem]" style={expanded ? "transform: rotate(180deg)" : ""} />
				</Button>
			</div>
		</CardHeader>
		
		<CardContent class={WIDGET_SPACING.content}>
			{#if expanded}
				<p class="text-sm text-[hsl(var(--muted-foreground))] mb-[1rem]">{summary}</p>
			{/if}
			
			<div class="flex items-center justify-between mt-[0.5rem]">
				<div class="flex-1 h-[0.25rem] bg-[hsl(var(--muted))] rounded-full mr-[1rem]">
					<div class="h-full w-0 bg-[hsl(var(--primary))] rounded-full"></div>
				</div>
				<span class="text-xs text-[hsl(var(--muted-foreground))]">0:00</span>
			</div>
		</CardContent>
		
		<CardFooter class="flex justify-between pt-[0.75rem] border-t {WIDGET_SPACING.footer}">
			<Button 
				variant="outline" 
				size="sm" 
				onclick={toggleAudio}
				class="flex items-center"
			>
				{#if audioPlaying}
					<Pause class="h-[1rem] w-[1rem] mr-[0.5rem]" />
					<span>Pause</span>
				{:else}
					<Play class="h-[1rem] w-[1rem] mr-[0.5rem]" />
					<span>Play</span>
				{/if}
			</Button>
			
			<Button 
				variant="ghost" 
				size="sm" 
				onclick={sharePodcast}
				class="flex items-center"
			>
				<Share2 class="h-[1rem] w-[1rem]" />
			</Button>
		</CardFooter>
	{/if}
</Card>

<style>
	/* Add animation to utility classes instead of custom CSS */
	@keyframes pulse-slow {
		0% { border-color: rgba(0, 255, 255, 1); }
		50% { border-color: rgba(0, 255, 255, 0.6); }
		100% { border-color: rgba(0, 255, 255, 1); }
	}
	
	:global(.animate-pulse-slow) {
		animation: pulse-slow 3s infinite;
	}
</style>	