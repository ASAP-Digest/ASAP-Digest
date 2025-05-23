<script>
  import { onMount } from 'svelte';
  import { page } from '$app/stores';
  import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '$lib/components/ui/card';
  import { Button } from '$lib/components/ui/button';
  import { Badge } from '$lib/components/ui/badge';
  import { Check, X, CreditCard, Clock, Download } from '$lib/utils/lucide-compat.js';
  import Icon from '$lib/components/ui/icon/icon.svelte';
  import { invalidateAll } from '$app/navigation';
  
  let loading = $state(false);
  let currentPlan = $state(null);
  let billingHistory = $state([]);

  // Mock data for demonstration
  onMount(() => {
    // Simulate loading current plan
    currentPlan = {
      name: 'Pro Plan',
      startDate: new Date('2024-01-15'),
      features: [
        'Unlimited digest creation',
        'Advanced analytics',
        'Priority support',
        'Custom branding'
      ]
    };

    // Simulate billing history
    billingHistory = [
    {
        date: new Date('2024-03-01'),
        description: 'Pro Plan - Monthly',
        amount: 29.99,
        status: 'Paid'
    },
    {
        date: new Date('2024-02-01'),
        description: 'Pro Plan - Monthly',
        amount: 29.99,
        status: 'Paid'
      }
    ];
  });

  function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  }

  function manageSubscription() {
    // Handle subscription management
    console.log('Managing subscription...');
  }
</script>

<!-- Billing Header -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <header class="mb-8">
      <h1 class="text-3xl font-bold">Billing & Subscription</h1>
      <p class="text-muted-foreground">Manage your subscription plan and view billing history</p>
    </header>
  </div>
    </div>
    
<!-- Current Plan Section -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <Card class="mb-8">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={CreditCard} class="h-5 w-5 text-primary" />
          Your Current Plan
        </CardTitle>
      </CardHeader>
      <CardContent>
        {#if currentPlan}
          <div class="flex justify-between items-center">
            <div>
              <p class="text-2xl font-semibold">{currentPlan.name}</p>
              <p class="text-muted-foreground text-sm">Active since {formatDate(currentPlan.startDate)}</p>
            </div>
            <Badge variant="default">Active</Badge>
          </div>
          <ul class="mt-6 space-y-2 text-sm text-muted-foreground">
            {#each currentPlan.features as feature}
              <li class="flex items-center gap-2">
                <Icon icon={Check} class="h-4 w-4 text-green-500" /> {feature}
                  </li>
                {/each}
              </ul>
        {:else}
          <p class="text-muted-foreground">You do not currently have an active plan.</p>
          {/if}
      </CardContent>
      <CardFooter class="flex justify-end">
        {#if currentPlan}
          <Button variant="outline" on:click={manageSubscription}>Manage Subscription</Button>
        {:else}
          <a href="/plans"><Button>View Plans</Button></a>
        {/if}
      </CardFooter>
    </Card>
  </div>
      </div>

<!-- Billing History Section -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Icon icon={Clock} class="h-5 w-5 text-primary" />
          Billing History
        </CardTitle>
      </CardHeader>
      <CardContent>
        {#if billingHistory.length > 0}
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Description</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                  <th class="relative px-4 py-2"><span class="sr-only">Actions</span></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                {#each billingHistory as item}
                  <tr>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-muted-foreground">{formatDate(item.date)}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-foreground">{item.description}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-medium text-foreground">${item.amount.toFixed(2)}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                      <Badge variant={item.status === 'Paid' ? 'success' : item.status === 'Due' ? 'warning' : 'destructive'}>
                        {item.status}
                      </Badge>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground">
                        <Icon icon={Download} class="h-4 w-4" />
                      </Button>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {:else}
          <p class="text-muted-foreground text-sm">No billing history available.</p>
        {/if}
      </CardContent>
    </Card>
  </div>
</div> 