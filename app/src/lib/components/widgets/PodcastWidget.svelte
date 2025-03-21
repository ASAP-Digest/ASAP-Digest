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
	let audioElement;
	
	/**
	 * Check if device is online
	 */
	function checkOnlineStatus() {
		offline = !navigator.onLine;
	}

	// Set up event listeners for online/offline events
	onMount(() => {
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
		if (!playing) {
			audioElement.play()
				.then(() => {
					playing = true;
					console.log(`Playing podcast:`, id);
				})
				.catch(err => {
					console.error('Error playing audio:', err);
					error = true;
				});
		} else {
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
	loadingSlot={loadingContent} 
	default={podcastContent}
>
</BaseWidget>

{#snippet loadingContent()}
	<div class="flex justify-center items-center h-full">
		<span class="text-[hsl(var(--muted-foreground))] text-sm">Loading...</span>
	</div>
{/snippet}

{#snippet podcastContent()}
	{#if error}
		<div class="text-[hsl(var(--destructive))] text-sm">
			Failed to load podcast content
		</div>
	{:else}
		<div class="flex flex-col h-full">
			<div class="mb-3">
				<div class="flex items-center text-xs text-[hsl(var(--muted-foreground))] mb-2">
					<span>Episode {episode}</span>
					{#if duration}
						<span class="mx-1">•</span>
						<span>{Math.floor(duration / 60)} min</span>
					{/if}
				</div>
				
				<p class="text-sm text-[hsl(var(--muted-foreground))]">
					{summary}
				</p>
			</div>
			
			<div class="mt-auto pt-3 border-t border-[hsl(var(--border))]">
				<div class="flex items-center justify-between">
					<button
						onclick={togglePlay}
						disabled={offline || !audioUrl}
						class="play-button p-2 rounded-full bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] transition-colors duration-200 hover:bg-[hsl(var(--primary)/0.9)] hover:shadow-[0_0_4px_hsl(var(--primary)/0.5)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2 disabled:opacity-50"
						aria-label={playing ? 'Pause' : 'Play'}
					>
						<Icon icon={playing ? Pause : Play} size={20} color="hsl(var(--primary-foreground))" />
					</button>
					
					<div class="action-buttons flex gap-2">
						<button 
							onclick={expandView}
							class="p-1 text-[hsl(var(--muted-foreground))] rounded-md transition-colors duration-200 hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Expand view"
						>
							<Icon icon={Maximize} size={16} color="currentColor" />
						</button>
						<button 
							onclick={handleShare}
							class="p-1 text-[hsl(var(--muted-foreground))] rounded-md transition-colors duration-200 hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Share podcast"
						>
							<Icon icon={Share2} size={16} color="currentColor" />
						</button>
						<a 
							href={`/podcast/${id}`}
							class="p-1 text-[hsl(var(--muted-foreground))] rounded-md transition-colors duration-200 hover:text-[hsl(var(--foreground))] hover:bg-[hsl(var(--muted)/0.1)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))]"
							aria-label="Open podcast details"
						>
							<Icon icon={ExternalLink} size={16} color="currentColor" />
						</a>
					</div>
				</div>
				
				{#if offline}
					<div class="mt-2 text-xs text-[hsl(var(--warning))]">
						Podcasts not available offline
					</div>
				{:else if !audioUrl}
					<div class="mt-2 text-xs text-[hsl(var(--muted-foreground))]">
						Audio not available for this podcast
					</div>
				{/if}
			</div>
		</div>
	{/if}
{/snippet}

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