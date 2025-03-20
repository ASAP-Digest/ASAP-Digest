<!-- Image.svelte - Atomic component for images -->
<script>
  let {
    /** @type {string} */ src,
    /** @type {string} */ alt,
    /** @type {string} */ aspectRatio = "16/9",
    /** @type {boolean} */ lazy = true,
    /** @type {string} */ objectFit = "cover",
    /** @type {string} */ className = ""
  } = $props();
</script>

<div class="relative overflow-hidden {className}" style="aspect-ratio: {aspectRatio};">
  <img 
    {src}
    {alt}
    loading={lazy ? "lazy" : "eager"}
    class="w-full h-full object-{objectFit} transition-opacity duration-[var(--duration-normal)]"
  />
</div> 