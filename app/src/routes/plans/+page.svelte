<script>
  import { onMount } from 'svelte';
  
  /** @typedef {Object} PlanFeature
   * @property {string} name - Feature name
   * @property {boolean} included - Whether the feature is included
   */
  
  /** @typedef {Object} Plan
   * @property {string} id - Plan ID
   * @property {string} name - Plan name
   * @property {string} description - Plan description
   * @property {number} price - Plan price in dollars
   * @property {string} billingPeriod - Billing period (monthly/yearly)
   * @property {boolean} isPopular - Whether the plan is popular
   * @property {PlanFeature[]} features - Plan features
   */
  
  /** @type {Plan[]} */
  const plans = [
    {
      id: 'basic',
      name: 'Basic Plan',
      description: 'Essential features for casual readers',
      price: 5,
      billingPeriod: 'monthly',
      isPopular: false,
      features: [
        { name: 'Daily digest emails', included: true },
        { name: 'Basic recommendation engine', included: true },
        { name: 'Limited article access', included: true },
        { name: 'Save up to 10 articles', included: true },
        { name: 'Web app access', included: true },
        { name: 'Mobile app access', included: false },
        { name: 'Offline reading', included: false },
        { name: 'Advanced analytics', included: false },
        { name: 'Custom topics', included: false }
      ]
    },
    {
      id: 'pro',
      name: 'Pro Plan',
      description: 'Perfect for active readers',
      price: 12,
      billingPeriod: 'monthly',
      isPopular: true,
      features: [
        { name: 'Daily digest emails', included: true },
        { name: 'Advanced recommendation engine', included: true },
        { name: 'Unlimited article access', included: true },
        { name: 'Save unlimited articles', included: true },
        { name: 'Web app access', included: true },
        { name: 'Mobile app access', included: true },
        { name: 'Offline reading', included: true },
        { name: 'Advanced analytics', included: false },
        { name: 'Custom topics', included: false }
      ]
    },
    {
      id: 'premium',
      name: 'Premium Plan',
      description: 'The ultimate experience',
      price: 20,
      billingPeriod: 'monthly',
      isPopular: false,
      features: [
        { name: 'Daily digest emails', included: true },
        { name: 'Advanced recommendation engine', included: true },
        { name: 'Unlimited article access', included: true },
        { name: 'Save unlimited articles', included: true },
        { name: 'Web app access', included: true },
        { name: 'Mobile app access', included: true },
        { name: 'Offline reading', included: true },
        { name: 'Advanced analytics', included: true },
        { name: 'Custom topics', included: true }
      ]
    }
  ];
  
  let annualBilling = $state(false);
  
  /**
   * Gets the adjusted price based on billing period
   * @param {number} price - The monthly price
   * @returns {number} - The adjusted price
   */
  function getAdjustedPrice(price) {
    return annualBilling ? Math.round(price * 10) : price;
  }
  
  /**
   * Handle plan selection
   * @param {string} planId - The selected plan ID
   */
  function selectPlan(planId) {
    console.log(`Selected plan: ${planId}`);
    // In a real app, we would handle payment flow
  }
</script>

<!-- The outermost div with grid-stack-item is already added by a previous edit -->
<!-- Remove the inner container wrapper and make sections direct grid-stack-items -->

<!-- Header Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <div class="text-center max-w-3xl mx-auto mb-10">
      <h1 class="text-3xl font-bold mb-4 text-[hsl(var(--foreground))]">Choose Your Plan</h1>
      <p class="text-[hsl(var(--muted-foreground))]">
        Select the plan that best fits your needs. All plans include a 7-day free trial.
      </p>
      
      <!-- Billing toggle -->
      <div class="mt-6 flex items-center justify-center">
        <span class={`mr-3 text-sm ${!annualBilling ? 'font-semibold text-[hsl(var(--foreground))]' : 'text-[hsl(var(--muted-foreground))]'}`}>
          Monthly
        </span>
        <label class="relative inline-flex items-center cursor-pointer">
          <input 
            type="checkbox" 
            class="sr-only peer" 
            checked={annualBilling}
            onchange={() => annualBilling = !annualBilling}
          />
          <div class="w-14 h-7 bg-[hsl(var(--muted))] rounded-full peer peer-checked:bg-[hsl(var(--primary))] peer-focus:ring-2 peer-focus:ring-[hsl(var(--ring))] peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all"></div>
        </label>
        <span class={`ml-3 text-sm ${annualBilling ? 'font-semibold text-[hsl(var(--foreground))]' : 'text-[hsl(var(--muted-foreground))]'}`}>
          Annual <span class="text-[hsl(var(--success))]">(Save 17%)</span>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Plans Grid Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      {#each plans as plan}
        <div class={`relative flex flex-col rounded-lg border ${plan.isPopular ? 'border-[hsl(var(--primary))]' : 'border-[hsl(var(--border))]'} bg-[hsl(var(--card))] shadow-sm overflow-hidden`}>
          {#if plan.isPopular}
            <div class="absolute top-0 right-0 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] text-xs font-semibold px-3 py-1 rounded-bl-lg">
              Most Popular
            </div>
          {/if}
          
          <div class="p-6">
            <h3 class="text-xl font-bold">{plan.name}</h3>
            <p class="text-sm text-[hsl(var(--muted-foreground))] mt-1">{plan.description}</p>
            
            <div class="mt-4 flex items-baseline">
              <span class="text-3xl font-bold">${getAdjustedPrice(plan.price)}</span>
              <span class="ml-1 text-[hsl(var(--muted-foreground))]">/{annualBilling ? 'year' : 'month'}</span>
            </div>
            
            <button 
              onclick={() => selectPlan(plan.id)}
              class={`mt-6 w-full py-2 px-4 rounded-md ${plan.isPopular ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]' : 'bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))]'} hover:bg-opacity-90 transition-colors duration-200`}
            >
              {plan.isPopular ? 'Get Started' : 'Choose Plan'}
            </button>
          </div>
          
          <div class="p-6 bg-[hsl(var(--muted)/0.3)] flex-1">
            <h4 class="font-semibold mb-4">What's included:</h4>
            <ul class="space-y-3">
              {#each plan.features as feature}
                <li class="flex items-start">
                  <span class={`inline-flex items-center justify-center h-5 w-5 rounded-full ${feature.included ? 'bg-[hsl(var(--success)/0.2)] text-[hsl(var(--success))]' : 'bg-[hsl(var(--muted)/0.4)] text-[hsl(var(--muted-foreground))]'} mr-2 flex-shrink-0`}>
                    {#if feature.included}
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                      </svg>
                    {:else}
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                      </svg>
                    {/if}
                  </span>
                  <span class={feature.included ? 'text-[hsl(var(--foreground))]' : 'text-[hsl(var(--muted-foreground))]'}>
                    {feature.name}
                  </span>
                </li>
              {/each}
            </ul>
          </div>
        </div>
      {/each}
    </div>
  </div>
</div>

<!-- Contact/Info Section - Treat as Gridstack item -->
<div class="grid-stack-item" data-gs-no-resize="true" data-gs-no-move="true" data-gs-auto-position="true" data-gs-width="12" data-gs-height="auto">
  <div class="grid-stack-item-content">
    <div class="mt-12 text-center text-sm text-[hsl(var(--muted-foreground))]">
      <p>All plans include a 7-day free trial. No credit card required.</p>
      <p class="mt-2">Need a custom plan for your organization? <a href="/contact" class="text-[hsl(var(--primary))] hover:underline">Contact us</a>.</p>
    </div>
  </div>
</div> 