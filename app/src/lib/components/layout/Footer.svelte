<script module>
  import { SITE_URL } from '$lib/api/wordpress.js';
</script>

<footer class="mt-auto">
  <div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
      <div class="mb-4 md:mb-0">
        <p class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))]">
          &copy; {new Date().getFullYear()} ⚡️ ASAP Digest. All rights reserved.
        </p>
      </div>
      <div class="flex space-x-4">
        <a href={SITE_URL + "/privacy"} class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))] hover:text-[hsl(var(--link))]">
          Privacy Policy
        </a>
        <a href={SITE_URL + "/terms"} class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))] hover:text-[hsl(var(--link))]">
          Terms of Service
        </a>
        <a href={SITE_URL + "/contact"} class="text-[var(--font-size-sm)] text-[hsl(var(--text-2))] hover:text-[hsl(var(--link))]">
          Contact
        </a>
      </div>
    </div>
  </div>
</footer> 