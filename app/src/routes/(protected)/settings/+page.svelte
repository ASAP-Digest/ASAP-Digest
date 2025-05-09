<!--
  Settings Hub
  -----------
  Central settings page with navigation to specific settings areas:
  - Account settings
  - Profile settings
  - Notification settings
  - Privacy settings
  - Security settings
  
  @file-marker settings-hub-page
  @implementation-context: SvelteKit, Better Auth, Shadcn UI
-->
<script>
  import { page } from '$app/stores';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Button } from '$lib/components/ui/button';
  import { 
    User, 
    Bell, 
    ShieldCheck, 
    Eye, 
    Mail,
    Smartphone,
    Languages,
    KeyRound,
    CreditCard,
    Trash2,
    Settings
  } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  
  // Define all settings options
  const settingsOptions = [
    {
      id: 'account',
      title: 'Account Settings',
      description: 'Manage your account details and preferences',
      icon: User,
      href: '/settings/account'
    },
    {
      id: 'notifications',
      title: 'Notification Settings',
      description: 'Control how and when you receive notifications',
      icon: Bell,
      href: '/settings/notifications'
    },
    {
      id: 'security',
      title: 'Security Settings',
      description: 'Manage passwords, 2FA, and account security',
      icon: ShieldCheck,
      href: '/settings/security'
    },
    {
      id: 'privacy',
      title: 'Privacy Settings',
      description: 'Control data usage and privacy preferences',
      icon: Eye,
      href: '/settings/privacy'
    },
    {
      id: 'billing',
      title: 'Billing & Subscription',
      description: 'Manage your subscription plan and payment methods',
      icon: CreditCard,
      href: '/billing'
    }
  ];
</script>

<div class="container py-10 max-w-5xl">
  <header class="mb-8">
    <h1 class="text-3xl font-bold">Settings</h1>
    <p class="text-muted-foreground">Manage your account settings and preferences</p>
  </header>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {#each settingsOptions as option}
      <a href={option.href} class="block group">
        <Card class="h-full transition-all duration-200 hover:shadow-md">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Icon icon={option.icon} class="h-5 w-5 text-primary" />
              {option.title}
            </CardTitle>
            <CardDescription>{option.description}</CardDescription>
          </CardHeader>
          <CardFooter>
            <Button variant="ghost" class="group-hover:bg-primary/10 w-full justify-start">
              Configure {option.id}
            </Button>
          </CardFooter>
        </Card>
      </a>
    {/each}
  </div>
  
  <div class="mt-16">
    <h2 class="text-xl font-semibold mb-4">Additional Settings</h2>
    <div class="border rounded-md divide-y">
      <div class="flex items-center justify-between p-4">
        <div>
          <h3 class="font-medium">Language Preferences</h3>
          <p class="text-sm text-muted-foreground">Set your preferred language for the application</p>
        </div>
        <Button variant="outline" size="sm">
          <Icon icon={Languages} class="h-4 w-4 mr-2" />
          English (US)
        </Button>
      </div>
      
      <div class="flex items-center justify-between p-4">
        <div>
          <h3 class="font-medium text-rose-600">Delete Account</h3>
          <p class="text-sm text-muted-foreground">Permanently delete your account and all data</p>
        </div>
        <Button variant="destructive" size="sm">
          <Icon icon={Trash2} class="h-4 w-4 mr-2" />
          Delete Account
        </Button>
      </div>
    </div>
  </div>
</div> 