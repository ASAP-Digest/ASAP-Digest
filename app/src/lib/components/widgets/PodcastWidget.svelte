<script>
	import { Play, Pause, Share2, ExternalLink, Maximize } from 'lucide-svelte';
	import BaseWidget from './BaseWidget.svelte';
	
	/** @type {string} */
	export let id = '';
	
	/** @type {string} */
	export let title = '';
	
	/** @type {number} */
	export let episode = 1;
	
	/** @type {number} */
	export let duration = 0;
	
	/** @type {string} */
	export let summary = '';
	
	/** @type {boolean} */
	let loading = false;
	
	/** @type {boolean} */
	let error = false;
	
	/** @type {boolean} */
	let playing = false;
	
	/** @type {boolean} */
	let expanded = false;
	
	/** @type {boolean} */
	let offline = false;
	
	/**
	 * Check if device is online
	 */
	function checkOnlineStatus() {
		offline = !navigator.onLine;
	}
	
	/**
	 * Toggle audio play/pause
	 * @param {MouseEvent} event - Mouse event object
	 */
	function togglePlay(event) {
		// Simulate audio playback toggle
		if (offline) {
			console.error('Cannot play audio offline');
			return;
		}
		
		playing = !playing;
		console.log(`${playing ? 'Playing' : 'Paused'} podcast:`, id);
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

<BaseWidget title={title} icon={Play} {loading}>
	{#if loading}
		<!-- Loading state handled by BaseWidget -->
	{:else if error}
		<div class="text-[hsl(var(--destructive))] text-[0.875rem]">
			Failed to load podcast content
		</div>
	{:else}
		<div class="flex flex-col h-full">
			<div class="mb-[0.75rem]">
				<div class="flex items-center text-[0.75rem] text-[hsl(var(--muted-foreground))] mb-[0.5rem]">
					<span>Episode {episode}</span>
					{#if duration}
						<span class="mx-[0.25rem]">•</span>
						<span>{Math.floor(duration / 60)} min</span>
					{/if}
				</div>
				
				<p class="text-[0.875rem] text-[hsl(var(--muted-foreground))]">
					{summary}
				</p>
			</div>
			
			<div class="mt-auto pt-[0.75rem] border-t border-[hsl(var(--border))]">
				<div class="flex items-center justify-between">
					<button
						onclick={togglePlay}
						disabled={offline}
						class="flex items-center justify-center w-[2.5rem] h-[2.5rem] rounded-full bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] disabled:opacity-50"
						aria-label={playing ? 'Pause' : 'Play'}
					>
						{#if playing}
							<Pause size={16} />
						{:else}
							<Play size={16} />
						{/if}
					</button>
					
					<div class="flex gap-[0.5rem]">
						<button 
							onclick={expandView}
							class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
							aria-label="Expand view"
						>
							<Maximize size={16} />
						</button>
						<button 
							onclick={handleShare}
							class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
							aria-label="Share podcast"
						>
							<Share2 size={16} />
						</button>
						<a 
							href={`/podcast/${id}`}
							class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
							aria-label="Open podcast details"
						>
							<ExternalLink size={16} />
						</a>
					</div>
				</div>
				
				{#if offline}
					<div class="mt-[0.5rem] text-[0.75rem] text-[hsl(var(--warning))]">
						Podcasts not available offline
					</div>
				{/if}
			</div>
		</div>
	{/if}
</BaseWidget>

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