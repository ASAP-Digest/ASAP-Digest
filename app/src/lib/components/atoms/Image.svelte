<!-- Image.svelte - Atomic component for images -->
<script>
  /** @type {string} src - Image source URL */
  let { src } = $props();
  
  /** @type {string} alt - Alternative text for image */
  let { alt } = $props();
  
  /** @type {string} [aspectRatio="16/9"] - Image aspect ratio */
  let { aspectRatio = "16/9" } = $props();
  
  /** @type {boolean} [lazy=true] - Whether to lazy load the image */
  let { lazy = true } = $props();
  
  /** @type {string} [objectFit="cover"] - Object-fit property */
  let { objectFit = "cover" } = $props();
  
  /** @type {string} [className=""] - Additional CSS classes */
  let { className = "" } = $props();
</script>

<div class="relative overflow-hidden {className}" style="aspect-ratio: {aspectRatio};">
  <img 
    src={src} 
    alt={alt}
    loading={lazy ? "lazy" : "eager"}
    class="w-full h-full object-{objectFit} transition-opacity duration-[var(--duration-normal)]"
  />
</div> 