<script>
	import { BookOpen, Share2, Speaker, ExternalLink } from 'lucide-svelte';
	import BaseWidget from './BaseWidget.svelte';
	
	/** @type {string} */
	export let id = '';
	
	/** @type {string} */
	export let title = '';
	
	/** @type {string} */
	export let excerpt = '';
	
	/** @type {string} */
	export let source = '';
	
	/** @type {string} */
	export let sourceUrl = '';
	
	/** @type {string} */
	export let date = '';
	
	/** @type {string} */
	export let imageUrl = '';
	
	/** @type {string[]} */
	export let tags = [];
	
	/** @type {boolean} */
	let loading = false;
	
	/** @type {boolean} */
	let error = false;
	
	/** @type {boolean} */
	let expanded = false;
	
	// Truncate excerpt if it's too long
	$: truncatedExcerpt = excerpt.length > 150 && !expanded 
		? excerpt.substring(0, 150) + '...' 
		: excerpt;
	
	/**
	 * Handle read more button click
	 * @param {MouseEvent} event - Mouse event object
	 */
	function handleReadMore(event) {
		expanded = !expanded;
	}
	
	/**
	 * Handle share button click
	 * @param {MouseEvent} event - Mouse event object
	 */
	function handleShare(event) {
		console.log('Share article:', id);
		if (navigator.share) {
			navigator.share({
				title: title,
				text: excerpt,
				url: window.location.href
			}).catch(error => {
				console.error('Error sharing:', error);
			});
		} else {
			console.log('Web Share API not supported');
		}
	}
	
	/**
	 * Handle text-to-speech button click
	 * @param {MouseEvent} event - Mouse event object
	 */
	function handleTextToSpeech(event) {
		console.log('Text to speech for article:', id);
		const utterance = new SpeechSynthesisUtterance(title + '. ' + excerpt);
		window.speechSynthesis.speak(utterance);
	}
</script>

<BaseWidget title={title} icon={BookOpen} {loading}>
	{#if loading}
		<!-- Loading state handled by BaseWidget -->
	{:else if error}
		<div class="text-[hsl(var(--destructive))] text-[0.875rem]">
			Failed to load article content
		</div>
	{:else}
		<div class="flex flex-col h-full">
			{#if imageUrl}
				<div class="mb-[0.75rem] w-full h-[8rem] overflow-hidden rounded-[0.375rem]">
					<img 
						src={imageUrl} 
						alt={title} 
						class="w-full h-full object-cover"
						onerror={(e) => {
							e.target.style.display = 'none';
							error = true;
						}}
					/>
				</div>
			{/if}
			
			<div class="mb-[0.75rem]">
				<p class="text-[0.875rem] text-[hsl(var(--muted-foreground))]">
					{truncatedExcerpt}
				</p>
				{#if excerpt.length > 150}
					<button 
						onclick={handleReadMore}
						class="text-[0.75rem] text-[hsl(var(--primary))] mt-[0.5rem] hover:underline"
					>
						{expanded ? 'Read less' : 'Read more'}
					</button>
				{/if}
			</div>
			
			<div class="mt-auto">
				<div class="flex justify-between items-center">
					<div class="text-[0.75rem] text-[hsl(var(--muted-foreground))]">
						{#if source}
							<span>{source}</span>
						{/if}
						{#if date}
							<span class="mx-[0.25rem]">•</span>
							<span>{new Date(date).toLocaleDateString()}</span>
						{/if}
					</div>
					
					<div class="flex gap-[0.5rem]">
						<button 
							onclick={handleTextToSpeech}
							class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
							aria-label="Text to speech"
						>
							<Speaker size={16} />
						</button>
						<button 
							onclick={handleShare}
							class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
							aria-label="Share article"
						>
							<Share2 size={16} />
						</button>
						{#if sourceUrl}
							<a 
								href={sourceUrl}
								target="_blank"
								rel="noopener noreferrer"
								class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
								aria-label="Open original source"
							>
								<ExternalLink size={16} />
							</a>
						{/if}
					</div>
				</div>
				
				{#if tags.length > 0}
					<div class="flex flex-wrap gap-[0.25rem] mt-[0.5rem]">
						{#each tags as tag}
							<span class="text-[0.75rem] px-[0.5rem] py-[0.125rem] bg-[hsl(var(--muted)/0.5)] rounded-[0.25rem]">
								{tag}
							</span>
						{/each}
					</div>
				{/if}
			</div>
		</div>
	{/if}
</BaseWidget> 