<script>
  /**
   * AudioPlayer component for ASAP Digest
   * Provides standardized audio playback with consistent controls and styling
   */
  import { onMount, onDestroy, createEventDispatcher } from 'svelte';
  import { Play, Pause, SkipBack, SkipForward, Volume2, VolumeX } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { cn } from '$lib/utils';

  // Event dispatcher
  const dispatch = createEventDispatcher();

  // Props
  let {
    src = '',
    autoplay = false,
    loop = false,
    preload = 'metadata',
    className = '',
    size = 'default', // 'sm', 'default', 'lg'
    variant = 'default', // 'default', 'minimal', 'accent'
    showControls = true,
    showVolume = true,
    showTime = true,
    showSeek = true,
    showSkip = false,
    skipAmount = 10, // seconds
  } = $props();

  // State variables
  /** @type {HTMLAudioElement|null} */
  let audio = null;
  let playing = $state(false);
  let currentTime = $state(0);
  let duration = $state(0);
  let volume = $state(1);
  let muted = $state(false);
  let error = $state(false);
  let errorMessage = $state('');
  let loaded = $state(false);
  let progress = $state(0);
  let seeking = $state(false);

  /**
   * Get variant class based on variant prop
   * @returns {string} CSS class for the variant
   */
  const getVariantClass = () => {
    switch (variant) {
      case 'minimal':
        return 'bg-transparent border-none shadow-none';
      case 'accent':
        return 'bg-[hsl(var(--brand)/0.1)] border-[hsl(var(--brand)/0.2)] text-[hsl(var(--brand))]';
      default:
        return 'bg-[hsl(var(--surface-1))] border-[hsl(var(--border))]';
    }
  };

  /**
   * Get size class based on size prop
   * @returns {string} CSS class for the size
   */
  const getSizeClass = () => {
    switch (size) {
      case 'sm':
        return 'p-2 text-[var(--font-size-xs)]';
      case 'lg':
        return 'p-4 text-[var(--font-size-base)]';
      default:
        return 'p-3 text-[var(--font-size-sm)]';
    }
  };

  /**
   * Format seconds as MM:SS
   * @param {number} seconds - Time in seconds
   * @returns {string} - Formatted time
   */
  function formatTime(seconds) {
    if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
    
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  /**
   * Toggle play/pause
   */
  function togglePlay() {
    if (!audio) return;
    
    if (playing) {
      audio.pause();
    } else {
      audio.play().catch(
        /**
         * @param {Error} err
         */
        function(err) {
          console.error('Error playing audio:', err);
          error = true;
          errorMessage = err.message || 'Failed to play audio';
        }
      );
    }
  }

  /**
   * Toggle mute
   */
  function toggleMute() {
    if (!audio) return;
    
    audio.muted = !audio.muted;
    muted = audio.muted;
  }

  /**
   * Skip backward by skipAmount seconds
   */
  function skipBackward() {
    if (!audio) return;
    
    audio.currentTime = Math.max(0, audio.currentTime - skipAmount);
    dispatch('skip', { direction: 'backward', time: audio.currentTime });
  }

  /**
   * Skip forward by skipAmount seconds
   */
  function skipForward() {
    if (!audio) return;
    
    audio.currentTime = Math.min(audio.duration, audio.currentTime + skipAmount);
    dispatch('skip', { direction: 'forward', time: audio.currentTime });
  }

  /**
   * Handle volume change
   * @param {Event} event - Input event
   */
  function handleVolumeChange(event) {
    if (!audio) return;
    
    // Get input value safely
    /** @type {HTMLInputElement} */
    const target = event.target;
    const newVolume = parseFloat(target.value);
    
    audio.volume = newVolume;
    volume = newVolume;
    
    // Update muted state based on volume
    if (newVolume === 0) {
      audio.muted = true;
      muted = true;
    } else if (muted) {
      audio.muted = false;
      muted = false;
    }
    
    dispatch('volumechange', { volume: newVolume });
  }

  /**
   * Handle seeking (user dragging progress bar)
   * @param {Event} event - Input event
   */
  function handleSeek(event) {
    if (!audio || !duration) return;
    
    // Get input value safely
    /** @type {HTMLInputElement} */
    const target = event.target;
    const newTime = (parseFloat(target.value) / 100) * duration;
    
    audio.currentTime = newTime;
    currentTime = newTime;
    progress = (currentTime / duration) * 100;
    
    dispatch('seek', { time: newTime });
  }

  /**
   * Start seeking (user starts dragging)
   */
  function startSeeking() {
    seeking = true;
  }

  /**
   * End seeking (user stops dragging)
   */
  function endSeeking() {
    seeking = false;
  }

  // Set up audio element and event listeners
  onMount(() => {
    // Create audio element if we're in the browser
    if (typeof window !== 'undefined') {
      // If audio element doesn't exist, create it
      if (!audio) {
        audio = new Audio(src);
        audio.autoplay = autoplay;
        audio.loop = loop;
        audio.preload = preload;
        audio.volume = volume;
        audio.muted = muted;
      }

      // Set up event listeners
      audio.addEventListener('play', () => {
        playing = true;
        dispatch('play');
      });

      audio.addEventListener('pause', () => {
        playing = false;
        dispatch('pause');
      });

      audio.addEventListener('ended', () => {
        playing = false;
        dispatch('ended');
      });

      audio.addEventListener('timeupdate', () => {
        if (!seeking) {
          currentTime = audio.currentTime;
          progress = (currentTime / duration) * 100 || 0;
          dispatch('timeupdate', { currentTime, progress });
        }
      });

      audio.addEventListener('loadedmetadata', () => {
        duration = audio.duration;
        loaded = true;
        dispatch('loaded', { duration });
      });

      audio.addEventListener('error', (e) => {
        error = true;
        errorMessage = 'Error loading audio';
        console.error('Audio error:', e);
        dispatch('error', { error: e });
      });

      audio.addEventListener('volumechange', () => {
        volume = audio.volume;
        muted = audio.muted;
      });
    }

    return () => {
      // Clean up
      if (audio) {
        audio.pause();
        audio.src = '';
        
        // Remove event listeners
        audio.removeEventListener('play', () => {});
        audio.removeEventListener('pause', () => {});
        audio.removeEventListener('ended', () => {});
        audio.removeEventListener('timeupdate', () => {});
        audio.removeEventListener('loadedmetadata', () => {});
        audio.removeEventListener('error', () => {});
        audio.removeEventListener('volumechange', () => {});
      }
    };
  });

  // Update audio src if it changes
  $effect(() => {
    if (audio && src) {
      audio.src = src;
      // Reset state
      loaded = false;
      error = false;
      errorMessage = '';
      currentTime = 0;
      duration = 0;
      progress = 0;
    }
  });
</script>

<div 
  class={cn(
    'audio-player relative rounded-md border transition-colors duration-[var(--duration-normal)]',
    getVariantClass(),
    getSizeClass(),
    className
  )}
>
  {#if error}
    <div class="error-message text-[hsl(var(--functional-error))] text-center py-2">
      {errorMessage || 'Failed to load audio'}
    </div>
  {/if}
  
  <div class="flex flex-col gap-2">
    {#if showControls}
      <div class="controls flex items-center justify-between">
        <div class="flex items-center gap-2">
          {#if showSkip}
            <button 
              onclick={skipBackward}
              disabled={!loaded}
              class="p-1 rounded-full text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))] transition-colors disabled:opacity-50"
              aria-label={`Skip back ${skipAmount} seconds`}
            >
              <Icon icon={SkipBack} size={size === 'sm' ? 16 : size === 'lg' ? 24 : 20} color="currentColor" />
            </button>
          {/if}
          
          <button 
            onclick={togglePlay}
            disabled={!loaded && !error}
            class="play-button p-2 rounded-full bg-[hsl(var(--brand))] text-[hsl(var(--brand-fg))] transition-colors hover:bg-[hsl(var(--brand-hover))] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ring))] focus-visible:ring-offset-2 disabled:opacity-50"
            aria-label={playing ? 'Pause' : 'Play'}
          >
            <Icon icon={playing ? Pause : Play} size={size === 'sm' ? 16 : size === 'lg' ? 24 : 20} color="hsl(var(--brand-fg))" />
          </button>
          
          {#if showSkip}
            <button 
              onclick={skipForward}
              disabled={!loaded}
              class="p-1 rounded-full text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))] transition-colors disabled:opacity-50"
              aria-label={`Skip forward ${skipAmount} seconds`}
            >
              <Icon icon={SkipForward} size={size === 'sm' ? 16 : size === 'lg' ? 24 : 20} color="currentColor" />
            </button>
          {/if}
          
          {#if showTime}
            <div class="time-display text-[hsl(var(--text-2))]">
              {formatTime(currentTime)} {#if duration && loaded}<span class="opacity-60">/ {formatTime(duration)}</span>{/if}
            </div>
          {/if}
        </div>
        
        {#if showVolume}
          <div class="volume-controls flex items-center gap-2">
            <button 
              onclick={toggleMute}
              class="p-1 rounded-full text-[hsl(var(--text-1))] hover:bg-[hsl(var(--surface-2))] transition-colors"
              aria-label={muted ? 'Unmute' : 'Mute'}
            >
              <Icon icon={muted ? VolumeX : Volume2} size={size === 'sm' ? 16 : size === 'lg' ? 24 : 20} color="currentColor" />
            </button>
            
            <input 
              type="range" 
              min="0" 
              max="1" 
              step="0.01" 
              value={volume} 
              oninput={handleVolumeChange} 
              class="volume-slider w-16 accent-[hsl(var(--brand))]"
              aria-label="Volume"
            />
          </div>
        {/if}
      </div>
    {/if}
    
    {#if showSeek && loaded}
      <div class="seek-bar">
        <input 
          type="range" 
          min="0" 
          max="100" 
          step="0.1" 
          value={progress} 
          oninput={handleSeek}
          onmousedown={startSeeking}
          onmouseup={endSeeking}
          ontouchstart={startSeeking}
          ontouchend={endSeeking}
          class="progress-slider w-full accent-[hsl(var(--brand))] h-2 rounded-full bg-[hsl(var(--surface-2))]"
          aria-label="Seek"
        />
      </div>
    {/if}
  </div>
</div>

<style>
  /* Custom styling for range inputs */
  input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
    height: 4px;
    border-radius: 4px;
    background: hsl(var(--surface-2));
  }
  
  input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: hsl(var(--brand));
    cursor: pointer;
    transition: all 0.15s ease;
  }
  
  input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.2);
    background: hsl(var(--brand-hover));
  }
  
  input[type="range"]::-moz-range-thumb {
    width: 12px;
    height: 12px;
    border: none;
    border-radius: 50%;
    background: hsl(var(--brand));
    cursor: pointer;
    transition: all 0.15s ease;
  }
  
  input[type="range"]::-moz-range-thumb:hover {
    transform: scale(1.2);
    background: hsl(var(--brand-hover));
  }
  
  /* Progress styling */
  .progress-slider {
    height: 4px;
    border-radius: 4px;
    background: linear-gradient(to right, 
      hsl(var(--brand)) 0%, 
      hsl(var(--brand)) var(--progress), 
      hsl(var(--surface-2)) var(--progress), 
      hsl(var(--surface-2)) 100%);
  }
  
  .progress-slider::-webkit-slider-runnable-track {
    height: 4px;
    border-radius: 4px;
  }
  
  .progress-slider::-moz-range-track {
    height: 4px;
    border-radius: 4px;
  }
</style> 