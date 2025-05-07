<script>
  import { onMount } from 'svelte';
  import { 
    CreditCard, 
    DollarSign, 
    Clock, 
    Plus,
    Check,
    X
  } from '$lib/utils/lucide-compat.js';
  import Icon from "$lib/components/ui/icon/icon.svelte";

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

  // Current user's plan data (mocked)
  let currentPlan = $state({
    id: 'pro',
    name: 'Pro Plan',
    price: 12,
    billingPeriod: 'monthly',
    renewalDate: '2024-05-15',
    status: 'active'
  });

  // Toggle for annual/monthly billing
  let annualBilling = $state(false);
  
  // Payment methods (mocked)
  let paymentMethods = $state([
    {
      id: 'pm_1',
      type: 'card',
      brand: 'Visa',
      last4: '4242',
      expiryMonth: 12,
      expiryYear: 2025,
      isDefault: true
    }
  ]);

  // Billing history (mocked)
  let billingHistory = $state([
    {
      id: 'inv_1',
      date: '2024-04-01',
      amount: 12,
      status: 'paid',
      downloadUrl: '#'
    },
    {
      id: 'inv_2',
      date: '2024-03-01',
      amount: 12,
      status: 'paid',
      downloadUrl: '#'
    },
    {
      id: 'inv_3',
      date: '2024-02-01',
      amount: 12,
      status: 'paid',
      downloadUrl: '#'
    }
  ]);

  // Active tab state
  let activeTab = $state('subscription');

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

  /**
   * Format date string to MM/DD/YYYY
   * @param {string} dateString - ISO date string
   * @returns {string} - Formatted date
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
  }

  /**
   * Add new payment method
   */
  function addPaymentMethod() {
    console.log('Add payment method clicked');
    // In a real app, this would open a payment form
  }

  /**
   * Set a payment method as default
   * @param {string} methodId - Payment method ID
   */
  function setDefaultPaymentMethod(methodId) {
    paymentMethods = paymentMethods.map(method => ({
      ...method,
      isDefault: method.id === methodId
    }));
  }

  /**
   * Delete a payment method
   * @param {string} methodId - Payment method ID
   */
  function deletePaymentMethod(methodId) {
    paymentMethods = paymentMethods.filter(method => method.id !== methodId);
  }

  /**
   * Determine what plan the user is currently on
   * @returns {Plan|undefined} - The current plan
   */
  const getUserPlan = $derived(() => {
    return plans.find(plan => plan.id === currentPlan.id);
  });
</script>

<div class="container py-8">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6 text-[hsl(var(--foreground))]">Billing & Subscription</h1>
    
    <!-- Tabs -->
    <div class="flex border-b border-[hsl(var(--border))] mb-6">
      <button 
        onclick={() => activeTab = 'subscription'}
        class={`px-4 py-2 font-medium ${activeTab === 'subscription' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-[hsl(var(--muted-foreground))]'}`}
      >
        Subscription
      </button>
      <button 
        onclick={() => activeTab = 'payment-methods'}
        class={`px-4 py-2 font-medium ${activeTab === 'payment-methods' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-[hsl(var(--muted-foreground))]'}`}
      >
        Payment Methods
      </button>
      <button 
        onclick={() => activeTab = 'billing-history'}
        class={`px-4 py-2 font-medium ${activeTab === 'billing-history' ? 'text-[hsl(var(--primary))] border-b-2 border-[hsl(var(--primary))]' : 'text-[hsl(var(--muted-foreground))]'}`}
      >
        Billing History
      </button>
    </div>
    
    <!-- Subscription Tab -->
    {#if activeTab === 'subscription'}
      <div class="space-y-6">
        <!-- Current Plan -->
        <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] p-6">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="text-xl font-semibold flex items-center">
                <Icon icon={DollarSign} size={20} class="mr-2" color="currentColor" />
                Current Plan
              </h2>
              <div class="mt-4">
                <div class="flex items-center space-x-2">
                  <span class="bg-[hsl(var(--primary)/0.2)] text-[hsl(var(--primary))] text-sm font-medium px-2 py-1 rounded-full">
                    {currentPlan.name}
                  </span>
                  <span class="bg-[hsl(var(--success)/0.2)] text-[hsl(var(--success))] text-sm px-2 py-1 rounded-full">
                    {currentPlan.status === 'active' ? 'Active' : 'Inactive'}
                  </span>
                </div>
                <p class="mt-2 text-[hsl(var(--foreground))]">
                  <span class="font-medium">${currentPlan.price}</span>/{currentPlan.billingPeriod}
                </p>
                <p class="text-sm text-[hsl(var(--muted-foreground))] mt-1">
                  Renews on {formatDate(currentPlan.renewalDate)}
                </p>
              </div>
            </div>
            <button 
              onclick={() => activeTab = 'change-plan'}
              class="bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))] px-4 py-2 rounded-md hover:bg-[hsl(var(--secondary)/0.9)] transition-colors duration-200"
            >
              Change Plan
            </button>
          </div>
          
          {#if getUserPlan}
            <div class="mt-6 pt-6 border-t border-[hsl(var(--border))]">
              <h3 class="font-medium mb-3">Plan Features:</h3>
              <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                {#each getUserPlan.features as feature}
                  <li class="flex items-start">
                    <span class={`inline-flex items-center justify-center h-5 w-5 rounded-full ${feature.included ? 'bg-[hsl(var(--success)/0.2)] text-[hsl(var(--success))]' : 'bg-[hsl(var(--muted)/0.4)] text-[hsl(var(--muted-foreground))]'} mr-2 flex-shrink-0`}>
                      {#if feature.included}
                        <Icon icon={Check} size={14} color="currentColor" />
                      {:else}
                        <Icon icon={X} size={14} color="currentColor" />
                      {/if}
                    </span>
                    <span class={feature.included ? 'text-[hsl(var(--foreground))]' : 'text-[hsl(var(--muted-foreground))]'}>
                      {feature.name}
                    </span>
                  </li>
                {/each}
              </ul>
            </div>
          {/if}
        </div>
        
        <!-- Billing Cycle -->
        <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] p-6">
          <h2 class="text-xl font-semibold flex items-center">
            <Icon icon={Clock} size={20} class="mr-2" color="currentColor" />
            Billing Cycle
          </h2>
          <div class="mt-4">
            <div class="flex items-center">
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
                Annual <span class="text-[hsl(var(--primary))]">(Save 17%)</span>
              </span>
            </div>
            <p class="mt-3 text-sm text-[hsl(var(--muted-foreground))]">
              Change will apply at next renewal date. You won't be charged until {formatDate(currentPlan.renewalDate)}.
            </p>
          </div>
        </div>
        
        <!-- Cancel Subscription -->
        <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] p-6">
          <h2 class="text-xl font-semibold">Cancel Subscription</h2>
          <p class="mt-2 text-[hsl(var(--muted-foreground))]">
            Your subscription will remain active until the end of your current billing period on {formatDate(currentPlan.renewalDate)}.
          </p>
          <button 
            class="mt-4 text-[hsl(var(--destructive))] hover:text-[hsl(var(--destructive)/0.8)] font-medium transition-colors"
          >
            Cancel subscription
          </button>
        </div>
      </div>
    {/if}
    
    <!-- Change Plan Tab -->
    {#if activeTab === 'change-plan'}
      <div class="space-y-6">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-semibold">Change Plan</h2>
          <button 
            onclick={() => activeTab = 'subscription'}
            class="text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.8)]"
          >
            Back to Subscription
          </button>
        </div>
        
        <!-- Billing toggle -->
        <div class="flex items-center justify-center">
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
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          {#each plans as plan}
            <div class={`relative flex flex-col rounded-lg border ${plan.id === currentPlan.id ? 'border-[hsl(var(--primary))]' : 'border-[hsl(var(--border))]'} bg-[hsl(var(--card))] shadow-sm overflow-hidden`}>
              {#if plan.id === currentPlan.id}
                <div class="absolute top-0 right-0 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] text-xs font-semibold px-3 py-1 rounded-bl-lg">
                  Current Plan
                </div>
              {:else if plan.isPopular}
                <div class="absolute top-0 right-0 bg-[hsl(var(--accent))] text-[hsl(var(--accent-foreground))] text-xs font-semibold px-3 py-1 rounded-bl-lg">
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
                  class={`mt-6 w-full py-2 px-4 rounded-md ${plan.id === currentPlan.id ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]' : 'bg-[hsl(var(--secondary))] text-[hsl(var(--secondary-foreground))]'} hover:bg-opacity-90 transition-colors duration-200`}
                  disabled={plan.id === currentPlan.id}
                >
                  {plan.id === currentPlan.id ? 'Current Plan' : 'Switch to This Plan'}
                </button>
              </div>
            </div>
          {/each}
        </div>
      </div>
    {/if}
    
    <!-- Payment Methods Tab -->
    {#if activeTab === 'payment-methods'}
      <div>
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold flex items-center">
            <Icon icon={CreditCard} size={20} class="mr-2" color="currentColor" />
            Payment Methods
          </h2>
          <button 
            onclick={addPaymentMethod}
            class="flex items-center bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] px-4 py-2 rounded-md hover:bg-[hsl(var(--primary)/0.9)] transition-colors duration-200"
          >
            <Icon icon={Plus} size={16} class="mr-1" color="currentColor" />
            Add New
          </button>
        </div>
        
        {#if paymentMethods.length === 0}
          <div class="text-center py-8 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))]">
            <p class="text-[hsl(var(--muted-foreground))]">No payment methods found.</p>
          </div>
        {:else}
          <div class="space-y-4">
            {#each paymentMethods as method}
              <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] p-6">
                <div class="flex justify-between items-start">
                  <div class="flex items-start">
                    <div class="mr-4">
                      <div class="bg-[hsl(var(--muted)/0.4)] w-12 h-8 rounded flex items-center justify-center">
                        <span class="text-sm font-medium">{method.brand}</span>
                      </div>
                    </div>
                    <div>
                      <p class="font-medium">{method.brand} ending in {method.last4}</p>
                      <p class="text-sm text-[hsl(var(--muted-foreground))]">Expires {method.expiryMonth}/{method.expiryYear}</p>
                      {#if method.isDefault}
                        <span class="mt-1 inline-block bg-[hsl(var(--success)/0.2)] text-[hsl(var(--success))] text-xs px-2 py-1 rounded-full">
                          Default
                        </span>
                      {/if}
                    </div>
                  </div>
                  <div class="flex space-x-2">
                    {#if !method.isDefault}
                      <button 
                        onclick={() => setDefaultPaymentMethod(method.id)}
                        class="text-sm text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.8)]"
                      >
                        Set as default
                      </button>
                    {/if}
                    <button 
                      onclick={() => deletePaymentMethod(method.id)}
                      class="text-sm text-[hsl(var(--destructive))] hover:text-[hsl(var(--destructive)/0.8)]"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    {/if}
    
    <!-- Billing History Tab -->
    {#if activeTab === 'billing-history'}
      <div>
        <h2 class="text-xl font-semibold flex items-center mb-6">
          <Icon icon={Clock} size={20} class="mr-2" color="currentColor" />
          Billing History
        </h2>
        
        {#if billingHistory.length === 0}
          <div class="text-center py-8 bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))]">
            <p class="text-[hsl(var(--muted-foreground))]">No billing history found.</p>
          </div>
        {:else}
          <div class="bg-[hsl(var(--card))] rounded-lg border border-[hsl(var(--border))] overflow-hidden">
            <table class="w-full">
              <thead>
                <tr class="border-b border-[hsl(var(--border))]">
                  <th class="px-6 py-3 text-left text-xs font-medium text-[hsl(var(--muted-foreground))] uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-[hsl(var(--muted-foreground))] uppercase tracking-wider">Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-[hsl(var(--muted-foreground))] uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-[hsl(var(--muted-foreground))] uppercase tracking-wider">Invoice</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[hsl(var(--border))]">
                {#each billingHistory as invoice}
                  <tr>
                    <td class="px-6 py-4 text-sm text-[hsl(var(--foreground))]">{formatDate(invoice.date)}</td>
                    <td class="px-6 py-4 text-sm text-[hsl(var(--foreground))]">${invoice.amount.toFixed(2)}</td>
                    <td class="px-6 py-4">
                      <span class="px-2 py-1 text-xs font-medium rounded-full bg-[hsl(var(--success)/0.2)] text-[hsl(var(--success))]">
                        {invoice.status}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <a 
                        href={invoice.downloadUrl} 
                        class="text-sm text-[hsl(var(--primary))] hover:text-[hsl(var(--primary)/0.8)]"
                      >
                        Download
                      </a>
                    </td>
                  </tr>
                {/each}
              </tbody>
            </table>
          </div>
        {/if}
      </div>
    {/if}
  </div>
</div> 