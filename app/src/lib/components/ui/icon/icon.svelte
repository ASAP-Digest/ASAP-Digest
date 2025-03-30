<!-- Icon.svelte -->
<script>
  /** @type {string} */
  let className = '';
  export { className as class };

  /** @type {{ name: string, svgContent: string }} */
  let icon;
  export { icon };

  /** @type {number} */
  let size = 24;
  export { size };

  /** @type {string} */
  let strokeWidth = '2';
  export { strokeWidth };
</script>

<svg
  xmlns="http://www.w3.org/2000/svg"
  width={size}
  height={size}
  viewBox="0 0 24 24"
  fill="none"
  stroke="currentColor"
  stroke-width={strokeWidth}
  stroke-linecap="round"
  stroke-linejoin="round"
  class={className}
  aria-hidden="true"
  {...$$restProps}
>
  {@html icon?.svgContent || ''}
</svg> 