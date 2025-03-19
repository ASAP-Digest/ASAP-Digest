<script module>
  import { SITE_URL } from '$lib/api/wordpress.js';
</script>

<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
  <div class="container mx-auto px-4 py-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
      <div class="mb-4 md:mb-0">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          &copy; {new Date().getFullYear()} ASAP Digest. All rights reserved.
        </p>
      </div>
      <div class="flex space-x-4">
        <a href={SITE_URL + "/privacy"} class="text-sm text-gray-600 dark:text-gray-400 hover:text-[hsl(var(--primary))] dark:hover:text-[hsl(var(--primary))]">
          Privacy Policy
        </a>
        <a href={SITE_URL + "/terms"} class="text-sm text-gray-600 dark:text-gray-400 hover:text-[hsl(var(--primary))] dark:hover:text-[hsl(var(--primary))]">
          Terms of Service
        </a>
        <a href={SITE_URL + "/contact"} class="text-sm text-gray-600 dark:text-gray-400 hover:text-[hsl(var(--primary))] dark:hover:text-[hsl(var(--primary))]">
          Contact
        </a>
      </div>
    </div>
  </div>
</footer> 