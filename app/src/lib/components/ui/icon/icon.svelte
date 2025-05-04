<!-- Icon.svelte -->
<script>
  /**
   * @typedef {Object} IconProps
   * @property {string} [class] - Additional CSS classes
   * @property {{ name: string, svgContent: string }} icon - Icon object with SVG content
   * @property {number} [size=24] - Icon size in pixels
   * @property {string} [strokeWidth='2'] - SVG stroke width
   * @property {Object} [rest] - Additional props to pass to the SVG element
   */

  /** @type {IconProps} */
  const { 
    class: className = '', 
    icon, 
    size = 24, 
    strokeWidth = '2',
    ...rest 
  } = $props();
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
  {...rest}
>
  {@html icon?.svgContent || ''}
</svg> 