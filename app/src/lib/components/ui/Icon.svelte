<!-- This is a wrapper component for Lucide icons that makes them compatible with Svelte 5 runes mode -->
<script>
  import { getContext } from 'svelte';
  
  // Define props using the new runes syntax
  const { 
    icon,
    size = 24,
    strokeWidth = 2,
    class: className = '',
    absoluteStrokeWidth = false,
    color,
    ...rest
  } = $props();
  
  // Create SVG element props
  let svgProps = $state({
    width: size,
    height: size,
    stroke: color,
    'stroke-width': strokeWidth,
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    fill: 'none',
    class: `lucide lucide-${icon?.name?.toLowerCase()} ${className}`,
    ...rest
  });
  
  $effect(() => {
    if (absoluteStrokeWidth && size) {
      svgProps['stroke-width'] = (strokeWidth * 24) / Number(size);
    }
    
    // Update class when icon or className changes
    svgProps.class = `lucide lucide-${icon?.name?.toLowerCase()} ${className}`;
    
    // Update size when it changes
    svgProps.width = size;
    svgProps.height = size;
    
    // Update stroke color when it changes
    svgProps.stroke = color;
  });
</script>

{#if icon}
  <svg {...svgProps}>
    {@html icon.svgContent || ''}
  </svg>
{/if} 