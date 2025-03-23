<script>
	import { Play, Pause, Share2, ExternalLink, Maximize } from '$lib/utils/lucide-icons.js';
	import BaseWidget from './BaseWidget.svelte';
	import Icon from '$lib/components/ui/Icon.svelte';
	import { onMount } from 'svelte';
	
	let {
		id = '',
		title = '',
		episode = 1,
		duration = 0,
		summary = '',
		audioUrl = '', // Audio URL for the podcast
		loading = false,
		error = false
	} = $props();
	
	let playing = $state(false);
	let expanded = $state(false);
	let offline = $state(false);
	
	/** @type {HTMLAudioElement|null} */
	let audioElement = null;
	
	/**
	 * Check if device is online
	 */
	function checkOnlineStatus() {
		offline = !navigator.onLine;
	}

	// Set up event listeners for online/offline events
	onMount(() => {
		// Check if we have an audio URL
		if (audioUrl) {
			// Create audio element
			audioElement = new Audio(audioUrl);
			
			// Set up event listeners
			audioElement.addEventListener('ended', () => {
				playing = false;
			});
			
			audioElement.addEventListener('error', () => {
				error = true;
				playing = false;
			});
		}
		
		// Check initial online status
		checkOnlineStatus();
		
		// Add event listeners for online/offline events
		window.addEventListener('online', checkOnlineStatus);
		window.addEventListener('offline', checkOnlineStatus);
		
		return () => {
			// Clean up event listeners
			window.removeEventListener('online', checkOnlineStatus);
			window.removeEventListener('offline', checkOnlineStatus);
			
			// Clean up audio
			if (audioElement) {
				audioElement.pause();
				audioElement.src = '';
			}
		};
	});
	
	/**
	 * Toggle audio play/pause
	 * @param {MouseEvent} event - Mouse event object
	 */
	function togglePlay(event) {
		// Prevent default button behavior
		event.preventDefault();
		
		// Check if we're offline
		if (offline) {
			console.error('Cannot play audio offline');
			return;
		}
		
		// Check if audio URL is available
		if (!audioUrl) {
			console.error('No audio URL provided');
			return;
		}
		
		// Toggle play state
		if (!playing && audioElement) {
			audioElement.play()
				.then(() => {
					playing = true;
					console.log(`Playing podcast:`, id);
				})
				.catch((/** @type {Error} */ err) => {
					console.error('Error playing audio:', err);
					error = true;
				});
		} else if (audioElement) {
			audioElement.pause();
			playing = false;
			console.log(`Paused podcast:`, id);
		}
	}
	
	/**
	 * Expand the podcast view
	 * @param {MouseEvent} event - Mouse event object
	 */
	function expandView(event) {
		expanded = !expanded;
		console.log('Expand podcast view:', id);
	}
	
	/**
	 * Share the podcast
	 * @param {MouseEvent} event - Mouse event object
	 */
	function handleShare(event) {
		console.log('Share podcast:', id);
		if (navigator.share) {
			navigator.share({
				title: title,
				text: summary,
				url: window.location.href,
			}).catch(error => {
				console.error('Error sharing:', error);
			});
		} else {
			console.log('Web Share API not supported');
		}
	}
	
	/**
	 * Format duration as MM:SS
	 * @param {number} seconds - Duration in seconds
	 * @returns {string} - Formatted duration
	 */
	function formatDuration(seconds) {
		const mins = Math.floor(seconds / 60);
		const secs = seconds % 60;
		return `${mins}:${secs.toString().padStart(2, '0')}`;
	}
</script>

<BaseWidget 
	title={title} 
	icon={Play}
	loading={loading}
>
	{#if error}
		<div class="text-[hsl(var(--destructive))] text-[var(--font-size-sm)]">
			Failed to load podcast content
		</div>
	{:else if loading}
		<div class="space-y-[calc(var(--spacing-unit)*2)]">
			<div class="h-4 w-3/4 bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse"></div>
			<div class="h-4 w-1/2 bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse"></div>
			<div class="h-8 w-full bg-[hsl(var(--muted)/0.5)] rounded-[var(--radius-md)] animate-pulse mt-[calc(var(--spacing-unit)*4)]"></div>
		</div>
	{:else}
		<div class="flex flex-col h-full">
			<div class="mb-[calc(var(--spacing-unit)*3)]">
				<div class="flex items-center text-[var(--font-size-xs)] text-[hsl(var(--muted-foreground))] mb-[calc(var(--spacing-unit)*2)]">
					<span>Episode {episode}</span>
					{#if duration}
						<span class="mx-1">•</span>
						<span>{Math.floor(duration / 60)} min</span>
					{/if}
				</div>
				
				<p class="text-[var(--font-size-sm)] text-[hsl(var(--muted-foreground))]">
					{summary}
				</p>
			</div>
			
			<div class="mt-auto pt-[calc(var(--spacing-unit)*3)] border-t border-[hsl(var(--border))]">
				<div class="flex items-center justify-between">
					<button
						onclick={togglePlay}
						disabled={offline || !audioUrl}
						class="play-button p-[calc(var(--spacing-unit)*2)] rounded-full bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] 
                               transition-colors duration-[var(--duration-normal)] ease-[var(--ease-out)] 
                               hover:bg-[hsl(var(--primary)/0.9)] hover:shadow-[var(--glow-sm)_hsl(var(--primary))] 
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2 
                               disabled:opacity-50"
						aria-label={playing ? 'Pause' : 'Play'}
					>
						<Icon icon={playing ? Pause : Play} size={20} color="hsl(var(--primary-foreground))" />
					</button>
					
					<div class="action-buttons flex gap-2">
						<button 
							onclick={expandView}
							class="p-[calc(var(--spacing-unit)*1)] text-[hsl(var(--muted-foreground))] rounded-[var(--radius-sm)] 
                                   transition-colors duration-[var(--duration-normal)] ease-[var(--ease-out)]
                                   hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] 
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Expand view"
						>
							<Icon icon={Maximize} size={16} color="currentColor" />
						</button>
						<button 
							onclick={handleShare}
							class="p-[calc(var(--spacing-unit)*1)] text-[hsl(var(--muted-foreground))] rounded-[var(--radius-sm)] 
                                   transition-colors duration-[var(--duration-normal)] ease-[var(--ease-out)]
                                   hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] 
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Share podcast"
						>
							<Icon icon={Share2} size={16} color="currentColor" />
						</button>
						<a 
							href={`/podcast/${id}`}
							class="p-[calc(var(--spacing-unit)*1)] text-[hsl(var(--muted-foreground))] rounded-[var(--radius-sm)] 
                                  transition-colors duration-[var(--duration-normal)] ease-[var(--ease-out)]
                                  hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] 
                                  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Open podcast details"
						>
							<Icon icon={ExternalLink} size={16} color="currentColor" />
						</a>
					</div>
				</div>
				
				{#if offline}
					<div class="mt-[calc(var(--spacing-unit)*2)] text-[var(--font-size-xs)] text-[hsl(var(--destructive))]">
						Podcasts not available offline
					</div>
				{:else if !audioUrl}
					<div class="mt-[calc(var(--spacing-unit)*2)] text-[var(--font-size-xs)] text-[hsl(var(--muted-foreground))]">
						Audio playback coming soon
					</div>
				{/if}
			</div>
		</div>
	{/if}
</BaseWidget>

<style>
	/* Add animation to utility classes instead of custom CSS */
	@keyframes pulse-slow {
		0% { border-color: hsl(var(--secondary) / 1); }
		50% { border-color: hsl(var(--secondary) / 0.6); }
		100% { border-color: hsl(var(--secondary) / 1); }
	}
	
	:global(.animate-pulse-slow) {
		animation: pulse-slow 3s infinite;
	}
</style>	