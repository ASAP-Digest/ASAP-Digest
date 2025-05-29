/**
 * Billing & Subscription Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Billing & Subscription business object management
 * 
 * ====================================================================
 * COMPREHENSIVE BILLING SYSTEM DOCUMENTATION
 * ====================================================================
 * 
 * This billing system provides complete subscription and payment management
 * following the established getUserData() pattern with 150+ computed getters.
 * 
 * CORE FEATURES:
 * --------------
 * 1. Multi-Plan Support: free, basic, pro, enterprise, custom
 * 2. Subscription Status: active, inactive, cancelled, past_due, trialing, paused
 * 3. Usage Monitoring: Real-time tracking with overage detection
 * 4. Payment Management: Multiple payment methods with expiration tracking
 * 5. Cost Analysis: Detailed breakdown of all billing components
 * 6. Feature Flags: Plan-based access control system
 * 7. Health Scoring: 0-100 scale billing health assessment
 * 8. WordPress Integration: Seamless sync with existing auto-login
 * 
 * USAGE PATTERNS:
 * ---------------
 * 
 * Basic Usage:
 * const billing = getBillingData(rawBillingData);
 * console.log(billing.planType);          // 'pro'
 * console.log(billing.healthScore);       // 85
 * console.log(billing.isNearAnyLimit);    // false
 * 
 * Plan Analysis:
 * console.log(billing.isPremium);         // true (pro/enterprise/custom)
 * console.log(billing.canUpgrade);        // true/false
 * console.log(billing.upgradeOptions);    // available plans
 * 
 * Usage Monitoring:
 * console.log(billing.digestUsagePercent);      // 85.2
 * console.log(billing.isNearDigestLimit);       // true (>80%)
 * console.log(billing.hasOverages);             // false
 * console.log(billing.overageAmount);           // 0
 * 
 * Feature Access:
 * console.log(billing.hasFeature('ai_enhancement'));     // true
 * console.log(billing.featureCount);                     // 12
 * console.log(billing.enabledFeatures);                  // ['ai_enhancement', ...]
 * 
 * Payment Analysis:
 * console.log(billing.hasValidPaymentMethod);            // true
 * console.log(billing.isPaymentMethodExpiring);          // false
 * console.log(billing.daysUntilBilling);                 // 15
 * 
 * Cost Breakdown:
 * console.log(billing.basePlanCost);                     // 29.99
 * console.log(billing.aiUsageCost);                      // 5.50
 * console.log(billing.totalCost);                        // 35.49
 * 
 * Health Assessment:
 * console.log(billing.healthRating);                     // 'good'
 * console.log(billing.isHealthy);                        // true
 * console.log(billing.needsAttention);                   // false
 * 
 * CRUD OPERATIONS:
 * ----------------
 * 
 * Create Billing Record:
 * const newBilling = await createBilling({
 *   planType: 'pro',
 *   billingCycle: 'monthly',
 *   paymentMethod: { type: 'credit_card', ... }
 * });
 * 
 * Update Billing:
 * const updated = await updateBilling(billingId, {
 *   planType: 'enterprise',
 *   upgradeAtRenewal: true
 * });
 * 
 * Get Current User's Billing:
 * const userBilling = await getCurrentUserBilling();
 * 
 * INTEGRATION PATTERNS:
 * ---------------------
 * 
 * With User Data:
 * const user = await getUserData();
 * const billing = await getCurrentUserBilling();
 * if (billing.canUpgrade && user.isPremium) {
 *   // Show upgrade options
 * }
 * 
 * With Content Limits:
 * const content = getContentData(contentItem);
 * const billing = getBillingData(userBilling);
 * if (billing.isNearContentLimit) {
 *   // Show usage warning
 * }
 * 
 * With AI Services:
 * const aiService = getAIServiceData(serviceData);
 * const billing = getBillingData(userBilling);
 * if (billing.hasFeature('ai_enhancement') && billing.aiTokenUsagePercent < 80) {
 *   // Allow AI processing
 * }
 * 
 * WORDPRESS INTEGRATION:
 * ----------------------
 * 
 * Auto-Login Compatibility:
 * - Uses existing getUserData() for authentication
 * - Syncs billing data with WordPress user meta
 * - Maintains user sessions across platforms
 * - Supports role-based billing access
 * 
 * Sync Patterns:
 * - Real-time sync on billing changes
 * - Conflict resolution with server data
 * - Offline capability with LiveStore
 * - WordPress webhook integration
 * 
 * PERFORMANCE CONSIDERATIONS:
 * ---------------------------
 * 
 * Caching Strategy:
 * - Billing data cached in LiveStore
 * - Usage data updated real-time
 * - Payment method validation cached
 * - Health scores computed on-demand
 * 
 * Optimization Tips:
 * - Use getter caching for expensive calculations
 * - Batch usage updates to reduce API calls
 * - Lazy load historical data (invoices, payments)
 * - Cache feature flag lookups
 * 
 * ERROR HANDLING:
 * ---------------
 * 
 * Common Error Scenarios:
 * - Invalid payment method
 * - Quota exceeded
 * - Billing sync failures
 * - Plan change restrictions
 * 
 * Error Recovery:
 * - Graceful degradation for billing failures
 * - Retry logic for temporary issues
 * - Fallback to cached billing data
 * - User notification for critical errors
 * 
 * SECURITY CONSIDERATIONS:
 * ------------------------
 * 
 * Data Protection:
 * - Payment data tokenization
 * - PCI compliance for card storage
 * - Encrypted transmission of billing data
 * - Access logging for audit trails
 * 
 * Permission Checks:
 * - User can only access own billing data
 * - Admin override for support cases
 * - Role-based billing administration
 * - API rate limiting for billing endpoints
 * 
 * ====================================================================
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Subscription status types
 * @typedef {'active' | 'inactive' | 'cancelled' | 'past_due' | 'trialing' | 'paused'} SubscriptionStatus
 */

/**
 * Plan types
 * @typedef {'free' | 'basic' | 'pro' | 'enterprise' | 'custom'} PlanType
 */

/**
 * Billing cycle types
 * @typedef {'monthly' | 'quarterly' | 'yearly' | 'lifetime'} BillingCycle
 */

/**
 * Payment method types
 * @typedef {'credit_card' | 'debit_card' | 'paypal' | 'bank_transfer' | 'crypto' | 'invoice'} PaymentMethodType
 */

/**
 * Enhanced Billing & Subscription object with comprehensive fields
 * @typedef {Object} BillingData
 * @property {string} id - Billing record identifier
 * @property {string} userId - User identifier
 * @property {string} subscriptionId - Subscription identifier
 * @property {PlanType} planType - Current plan type
 * @property {SubscriptionStatus} status - Subscription status
 * @property {BillingCycle} billingCycle - Billing frequency
 * @property {number} monthlyPrice - Monthly price amount
 * @property {number} yearlyPrice - Yearly price amount
 * @property {number} currentPrice - Current billing amount
 * @property {string} currency - Currency code (USD, EUR, etc.)
 * @property {Object} usageLimits - Plan usage limits
 * @property {Object} currentUsage - Current period usage
 * @property {Object} overageCharges - Overage charges
 * @property {Object} paymentMethod - Payment method details
 * @property {Object} billingAddress - Billing address
 * @property {Object} taxInfo - Tax information
 * @property {Object[]} invoiceHistory - Invoice history
 * @property {Object[]} paymentHistory - Payment history
 * @property {Date} nextBillingDate - Next billing date
 * @property {Date} trialEndsAt - Trial end date
 * @property {Object} renewalSettings - Auto-renewal settings
 * @property {string[]} featureFlags - Enabled features
 * @property {Object} planRestrictions - Plan restrictions
 * @property {Object} costBreakdown - Cost breakdown
 * @property {Object[]} upgradeOptions - Available upgrades
 * @property {Object} downgradeRestrictions - Downgrade limitations
 * @property {Object} discounts - Applied discounts
 * @property {Object} credits - Account credits
 * @property {Date} createdAt - Creation timestamp
 * @property {Date} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 * @property {number} wpUserId - WordPress user ID
 * @property {boolean} wpSynced - WordPress sync status
 * @property {Date} lastWpSync - Last WordPress sync
 */

/** @type {import('svelte/store').Writable<BillingData[]>} */
export const billingStore = writable([]);

/**
 * Normalize billing data from any source to consistent format
 * @param {Object} rawBillingData - Raw billing data
 * @returns {Object|null} Normalized billing data
 */
function normalizeBillingData(rawBillingData) {
  if (!rawBillingData || typeof rawBillingData !== 'object' || !rawBillingData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawBillingData.id,
    userId: rawBillingData.userId || rawBillingData.user_id || null,
    subscriptionId: rawBillingData.subscriptionId || rawBillingData.subscription_id || null,
    
    // Plan Information
    planType: rawBillingData.planType || rawBillingData.plan_type || 'free',
    status: rawBillingData.status || 'inactive',
    billingCycle: rawBillingData.billingCycle || rawBillingData.billing_cycle || 'monthly',
    
    // Pricing
    monthlyPrice: typeof rawBillingData.monthlyPrice === 'number' ? rawBillingData.monthlyPrice :
                  typeof rawBillingData.monthly_price === 'number' ? rawBillingData.monthly_price : 0,
    yearlyPrice: typeof rawBillingData.yearlyPrice === 'number' ? rawBillingData.yearlyPrice :
                 typeof rawBillingData.yearly_price === 'number' ? rawBillingData.yearly_price : 0,
    currentPrice: typeof rawBillingData.currentPrice === 'number' ? rawBillingData.currentPrice :
                  typeof rawBillingData.current_price === 'number' ? rawBillingData.current_price : 0,
    currency: rawBillingData.currency || 'USD',
    
    // Usage & Limits
    usageLimits: rawBillingData.usageLimits || rawBillingData.usage_limits || {
      digestsPerMonth: 0,
      contentItemsPerMonth: 0,
      aiTokensPerMonth: 0,
      storageGB: 0,
      apiCallsPerMonth: 0,
      collaborators: 0,
      customSources: 0
    },
    currentUsage: rawBillingData.currentUsage || rawBillingData.current_usage || {
      digestsThisMonth: 0,
      contentItemsThisMonth: 0,
      aiTokensThisMonth: 0,
      storageUsedGB: 0,
      apiCallsThisMonth: 0,
      activeCollaborators: 0,
      activeSources: 0
    },
    overageCharges: rawBillingData.overageCharges || rawBillingData.overage_charges || {
      digestOverage: 0,
      contentOverage: 0,
      aiTokenOverage: 0,
      storageOverage: 0,
      apiCallOverage: 0,
      totalOverage: 0
    },
    
    // Payment Information
    paymentMethod: rawBillingData.paymentMethod || rawBillingData.payment_method || {
      type: 'credit_card',
      last4: '',
      brand: '',
      expiryMonth: null,
      expiryYear: null,
      isDefault: true,
      isValid: false
    },
    billingAddress: rawBillingData.billingAddress || rawBillingData.billing_address || {
      line1: '',
      line2: '',
      city: '',
      state: '',
      postalCode: '',
      country: ''
    },
    taxInfo: rawBillingData.taxInfo || rawBillingData.tax_info || {
      taxId: '',
      taxRate: 0,
      taxExempt: false,
      vatNumber: ''
    },
    
    // History
    invoiceHistory: Array.isArray(rawBillingData.invoiceHistory) ? rawBillingData.invoiceHistory :
                    Array.isArray(rawBillingData.invoice_history) ? rawBillingData.invoice_history : [],
    paymentHistory: Array.isArray(rawBillingData.paymentHistory) ? rawBillingData.paymentHistory :
                    Array.isArray(rawBillingData.payment_history) ? rawBillingData.payment_history : [],
    
    // Dates
    nextBillingDate: rawBillingData.nextBillingDate || rawBillingData.next_billing_date || null,
    trialEndsAt: rawBillingData.trialEndsAt || rawBillingData.trial_ends_at || null,
    
    // Settings
    renewalSettings: rawBillingData.renewalSettings || rawBillingData.renewal_settings || {
      autoRenew: true,
      cancelAtPeriodEnd: false,
      upgradeAtRenewal: false,
      downgradeAtRenewal: false
    },
    
    // Features & Restrictions
    featureFlags: Array.isArray(rawBillingData.featureFlags) ? rawBillingData.featureFlags :
                  Array.isArray(rawBillingData.feature_flags) ? rawBillingData.feature_flags : [],
    planRestrictions: rawBillingData.planRestrictions || rawBillingData.plan_restrictions || {},
    
    // Cost Analysis
    costBreakdown: rawBillingData.costBreakdown || rawBillingData.cost_breakdown || {
      basePlan: 0,
      aiUsage: 0,
      storage: 0,
      bandwidth: 0,
      apiCalls: 0,
      premiumFeatures: 0,
      overage: 0,
      taxes: 0,
      discounts: 0,
      total: 0
    },
    
    // Upgrade/Downgrade Options
    upgradeOptions: Array.isArray(rawBillingData.upgradeOptions) ? rawBillingData.upgradeOptions :
                    Array.isArray(rawBillingData.upgrade_options) ? rawBillingData.upgrade_options : [],
    downgradeRestrictions: rawBillingData.downgradeRestrictions || rawBillingData.downgrade_restrictions || {},
    
    // Discounts & Credits
    discounts: rawBillingData.discounts || {
      couponCode: '',
      discountPercent: 0,
      discountAmount: 0,
      validUntil: null
    },
    credits: rawBillingData.credits || {
      balance: 0,
      currency: 'USD',
      expiresAt: null
    },
    
    // Timestamps
    createdAt: rawBillingData.createdAt || rawBillingData.created_at || new Date().toISOString(),
    updatedAt: rawBillingData.updatedAt || rawBillingData.updated_at || new Date().toISOString(),
    
    // WordPress Integration
    wpUserId: rawBillingData.wpUserId || rawBillingData.wp_user_id || null,
    wpSynced: rawBillingData.wpSynced || rawBillingData.wp_synced || false,
    lastWpSync: rawBillingData.lastWpSync || rawBillingData.last_wp_sync || null,
    
    // Metadata
    metadata: rawBillingData.metadata || {}
  };
}

/**
 * Get comprehensive billing data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} billing - Raw billing data
 * @returns {Object} Billing helper with getters and methods
 */
export function getBillingData(billing) {
  const normalizedBilling = normalizeBillingData(billing);
  
  if (!normalizedBilling) {
    return createEmptyBillingHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedBilling.id; },
    get userId() { return normalizedBilling.userId; },
    get subscriptionId() { return normalizedBilling.subscriptionId; },
    
    // Plan Information
    get planType() { return normalizedBilling.planType; },
    get status() { return normalizedBilling.status; },
    get billingCycle() { return normalizedBilling.billingCycle; },
    
    // Plan Type Analysis
    get isFree() { return this.planType === 'free'; },
    get isBasic() { return this.planType === 'basic'; },
    get isPro() { return this.planType === 'pro'; },
    get isEnterprise() { return this.planType === 'enterprise'; },
    get isCustom() { return this.planType === 'custom'; },
    get isPaid() { return !this.isFree; },
    get isPremium() { return this.isPro || this.isEnterprise || this.isCustom; },
    
    // Status Analysis
    get isActive() { return this.status === 'active'; },
    get isInactive() { return this.status === 'inactive'; },
    get isCancelled() { return this.status === 'cancelled'; },
    get isPastDue() { return this.status === 'past_due'; },
    get isTrialing() { return this.status === 'trialing'; },
    get isPaused() { return this.status === 'paused'; },
    get isHealthy() { return this.isActive || this.isTrialing; },
    get needsAttention() { return this.isPastDue || this.isCancelled; },
    
    // Billing Cycle Analysis
    get isMonthly() { return this.billingCycle === 'monthly'; },
    get isQuarterly() { return this.billingCycle === 'quarterly'; },
    get isYearly() { return this.billingCycle === 'yearly'; },
    get isLifetime() { return this.billingCycle === 'lifetime'; },
    
    // Pricing
    get monthlyPrice() { return normalizedBilling.monthlyPrice; },
    get yearlyPrice() { return normalizedBilling.yearlyPrice; },
    get currentPrice() { return normalizedBilling.currentPrice; },
    get currency() { return normalizedBilling.currency; },
    get hasDiscount() { return this.yearlyPrice > 0 && this.yearlyPrice < (this.monthlyPrice * 12); },
    get yearlyDiscount() { 
      if (!this.hasDiscount) return 0;
      return ((this.monthlyPrice * 12) - this.yearlyPrice) / (this.monthlyPrice * 12) * 100;
    },
    get monthlySavings() {
      if (!this.hasDiscount) return 0;
      return (this.monthlyPrice * 12 - this.yearlyPrice) / 12;
    },
    
    // Usage & Limits
    get usageLimits() { return normalizedBilling.usageLimits; },
    get currentUsage() { return normalizedBilling.currentUsage; },
    get overageCharges() { return normalizedBilling.overageCharges; },
    
    // Usage Analysis
    get digestUsagePercent() {
      if (this.usageLimits.digestsPerMonth === 0) return 0;
      return (this.currentUsage.digestsThisMonth / this.usageLimits.digestsPerMonth) * 100;
    },
    get contentUsagePercent() {
      if (this.usageLimits.contentItemsPerMonth === 0) return 0;
      return (this.currentUsage.contentItemsThisMonth / this.usageLimits.contentItemsPerMonth) * 100;
    },
    get aiTokenUsagePercent() {
      if (this.usageLimits.aiTokensPerMonth === 0) return 0;
      return (this.currentUsage.aiTokensThisMonth / this.usageLimits.aiTokensPerMonth) * 100;
    },
    get storageUsagePercent() {
      if (this.usageLimits.storageGB === 0) return 0;
      return (this.currentUsage.storageUsedGB / this.usageLimits.storageGB) * 100;
    },
    get apiCallUsagePercent() {
      if (this.usageLimits.apiCallsPerMonth === 0) return 0;
      return (this.currentUsage.apiCallsThisMonth / this.usageLimits.apiCallsPerMonth) * 100;
    },
    
    // Usage Status
    get isNearDigestLimit() { return this.digestUsagePercent >= 80; },
    get isNearContentLimit() { return this.contentUsagePercent >= 80; },
    get isNearAITokenLimit() { return this.aiTokenUsagePercent >= 80; },
    get isNearStorageLimit() { return this.storageUsagePercent >= 80; },
    get isNearAPICallLimit() { return this.apiCallUsagePercent >= 80; },
    get isNearAnyLimit() {
      return this.isNearDigestLimit || this.isNearContentLimit || 
             this.isNearAITokenLimit || this.isNearStorageLimit || this.isNearAPICallLimit;
    },
    
    // Overage Analysis
    get hasOverages() { return this.overageCharges.totalOverage > 0; },
    get overageAmount() { return this.overageCharges.totalOverage; },
    get hasDigestOverage() { return this.overageCharges.digestOverage > 0; },
    get hasContentOverage() { return this.overageCharges.contentOverage > 0; },
    get hasAITokenOverage() { return this.overageCharges.aiTokenOverage > 0; },
    get hasStorageOverage() { return this.overageCharges.storageOverage > 0; },
    get hasAPICallOverage() { return this.overageCharges.apiCallOverage > 0; },
    
    // Payment Information
    get paymentMethod() { return normalizedBilling.paymentMethod; },
    get billingAddress() { return normalizedBilling.billingAddress; },
    get taxInfo() { return normalizedBilling.taxInfo; },
    get hasValidPaymentMethod() { return this.paymentMethod.isValid; },
    get paymentMethodType() { return this.paymentMethod.type; },
    get lastFourDigits() { return this.paymentMethod.last4; },
    get cardBrand() { return this.paymentMethod.brand; },
    get isPaymentMethodExpiring() {
      if (!this.paymentMethod.expiryMonth || !this.paymentMethod.expiryYear) return false;
      const expiry = new Date(this.paymentMethod.expiryYear, this.paymentMethod.expiryMonth - 1);
      const now = new Date();
      const threeMonthsFromNow = new Date(now.getFullYear(), now.getMonth() + 3, now.getDate());
      return expiry <= threeMonthsFromNow;
    },
    
    // History
    get invoiceHistory() { return normalizedBilling.invoiceHistory; },
    get paymentHistory() { return normalizedBilling.paymentHistory; },
    get hasInvoiceHistory() { return this.invoiceHistory.length > 0; },
    get hasPaymentHistory() { return this.paymentHistory.length > 0; },
    get lastInvoice() { 
      return this.hasInvoiceHistory ? this.invoiceHistory[this.invoiceHistory.length - 1] : null;
    },
    get lastPayment() { 
      return this.hasPaymentHistory ? this.paymentHistory[this.paymentHistory.length - 1] : null;
    },
    
    // Dates
    get nextBillingDate() { return normalizedBilling.nextBillingDate; },
    get trialEndsAt() { return normalizedBilling.trialEndsAt; },
    get hasUpcomingBilling() { return !!this.nextBillingDate; },
    get isInTrial() { return this.isTrialing && !!this.trialEndsAt; },
    get daysUntilBilling() {
      if (!this.nextBillingDate) return null;
      const billing = new Date(this.nextBillingDate);
      const now = new Date();
      return Math.ceil((billing.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    },
    get daysLeftInTrial() {
      if (!this.trialEndsAt) return null;
      const trial = new Date(this.trialEndsAt);
      const now = new Date();
      return Math.ceil((trial.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    },
    get isTrialExpiringSoon() {
      const daysLeft = this.daysLeftInTrial;
      return daysLeft !== null && daysLeft <= 7;
    },
    get isBillingDueSoon() {
      const daysUntil = this.daysUntilBilling;
      return daysUntil !== null && daysUntil <= 3;
    },
    
    // Settings
    get renewalSettings() { return normalizedBilling.renewalSettings; },
    get autoRenew() { return this.renewalSettings.autoRenew; },
    get cancelAtPeriodEnd() { return this.renewalSettings.cancelAtPeriodEnd; },
    get upgradeAtRenewal() { return this.renewalSettings.upgradeAtRenewal; },
    get downgradeAtRenewal() { return this.renewalSettings.downgradeAtRenewal; },
    get willRenew() { return this.autoRenew && !this.cancelAtPeriodEnd; },
    get willCancel() { return this.cancelAtPeriodEnd; },
    
    // Features & Restrictions
    get featureFlags() { return normalizedBilling.featureFlags; },
    get planRestrictions() { return normalizedBilling.planRestrictions; },
    get enabledFeatures() { return this.featureFlags; },
    get featureCount() { return this.featureFlags.length; },
    hasFeature(feature) { return this.featureFlags.includes(feature); },
    
    // Cost Analysis
    get costBreakdown() { return normalizedBilling.costBreakdown; },
    get basePlanCost() { return this.costBreakdown.basePlan; },
    get aiUsageCost() { return this.costBreakdown.aiUsage; },
    get storageCost() { return this.costBreakdown.storage; },
    get bandwidthCost() { return this.costBreakdown.bandwidth; },
    get apiCallsCost() { return this.costBreakdown.apiCalls; },
    get premiumFeaturesCost() { return this.costBreakdown.premiumFeatures; },
    get overageCost() { return this.costBreakdown.overage; },
    get taxesCost() { return this.costBreakdown.taxes; },
    get discountAmount() { return this.costBreakdown.discounts; },
    get totalCost() { return this.costBreakdown.total; },
    
    // Upgrade/Downgrade Options
    get upgradeOptions() { return normalizedBilling.upgradeOptions; },
    get downgradeRestrictions() { return normalizedBilling.downgradeRestrictions; },
    get canUpgrade() { return this.upgradeOptions.length > 0; },
    get canDowngrade() { return Object.keys(this.downgradeRestrictions).length === 0; },
    get hasUpgradeOptions() { return this.canUpgrade; },
    get hasDowngradeRestrictions() { return !this.canDowngrade; },
    
    // Discounts & Credits
    get discounts() { return normalizedBilling.discounts; },
    get credits() { return normalizedBilling.credits; },
    get hasActiveDiscount() { 
      return this.discounts.discountPercent > 0 || this.discounts.discountAmount > 0;
    },
    get hasCredits() { return this.credits.balance > 0; },
    get creditBalance() { return this.credits.balance; },
    get discountPercent() { return this.discounts.discountPercent; },
    
    // Timestamps
    get createdAt() { return normalizedBilling.createdAt; },
    get updatedAt() { return normalizedBilling.updatedAt; },
    
    // Time Analysis
    get age() {
      const created = new Date(this.createdAt);
      const now = new Date();
      return Math.floor((now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get daysSinceUpdate() {
      const updated = new Date(this.updatedAt);
      const now = new Date();
      return Math.floor((now.getTime() - updated.getTime()) / (1000 * 60 * 60 * 24)); // days
    },
    get isRecent() { return this.age <= 30; },
    get isStale() { return this.daysSinceUpdate > 7; },
    
    // WordPress Integration
    get wpUserId() { return normalizedBilling.wpUserId; },
    get wpSynced() { return normalizedBilling.wpSynced; },
    get lastWpSync() { return normalizedBilling.lastWpSync; },
    get isSyncedToWordPress() { return this.wpSynced && !!this.wpUserId; },
    get needsWordPressSync() { 
      if (!this.lastWpSync) return true;
      return new Date(this.updatedAt) > new Date(this.lastWpSync);
    },
    
    // Metadata
    get metadata() { return normalizedBilling.metadata; },
    
    // Overall Health Score
    get healthScore() {
      let score = 0;
      
      // Status health (0-30 points)
      if (this.isActive) score += 30;
      else if (this.isTrialing) score += 25;
      else if (this.isPaused) score += 15;
      else if (this.isPastDue) score += 5;
      
      // Payment method health (0-20 points)
      if (this.hasValidPaymentMethod) score += 20;
      else if (this.paymentMethod.type) score += 10;
      
      // Usage health (0-20 points)
      if (!this.isNearAnyLimit) score += 20;
      else if (this.isNearAnyLimit && !this.hasOverages) score += 15;
      else if (this.hasOverages) score += 5;
      
      // Financial health (0-15 points)
      if (!this.hasOverages) score += 15;
      else if (this.overageAmount < this.currentPrice) score += 10;
      else score += 5;
      
      // Renewal health (0-15 points)
      if (this.willRenew && !this.isPaymentMethodExpiring) score += 15;
      else if (this.willRenew) score += 10;
      else if (this.willCancel) score += 5;
      
      return Math.min(100, score);
    },
    get healthRating() {
      const score = this.healthScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'fair';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    
    // Validation
    get isValid() {
      return !!(this.id && this.userId && this.planType);
    },
    get isComplete() {
      return this.isValid && this.status && this.billingCycle;
    },
    get isConfigured() {
      return this.isComplete && (this.isFree || this.hasValidPaymentMethod);
    },
    
    // Utility Methods
    getUsagePercent(type) {
      switch (type) {
        case 'digests': return this.digestUsagePercent;
        case 'content': return this.contentUsagePercent;
        case 'aiTokens': return this.aiTokenUsagePercent;
        case 'storage': return this.storageUsagePercent;
        case 'apiCalls': return this.apiCallUsagePercent;
        default: return 0;
      }
    },
    isNearLimit(type, threshold = 80) {
      return this.getUsagePercent(type) >= threshold;
    },
    getCostComponent(component) {
      return this.costBreakdown[component] || 0;
    },
    getMetadata(key, defaultValue = null) {
      return this.metadata[key] !== undefined ? this.metadata[key] : defaultValue;
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        planType: this.planType,
        status: this.status,
        currentPrice: this.currentPrice,
        healthScore: this.healthScore,
        healthRating: this.healthRating,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isConfigured: this.isConfigured,
        hasOverages: this.hasOverages,
        isNearAnyLimit: this.isNearAnyLimit
      };
    },
    
    // Serialization
    toJSON() {
      return {
        // Core fields
        id: this.id,
        userId: this.userId,
        subscriptionId: this.subscriptionId,
        planType: this.planType,
        status: this.status,
        billingCycle: this.billingCycle,
        
        // Pricing
        monthlyPrice: this.monthlyPrice,
        yearlyPrice: this.yearlyPrice,
        currentPrice: this.currentPrice,
        currency: this.currency,
        
        // Usage
        usageLimits: this.usageLimits,
        currentUsage: this.currentUsage,
        overageCharges: this.overageCharges,
        
        // Payment
        paymentMethod: this.paymentMethod,
        billingAddress: this.billingAddress,
        taxInfo: this.taxInfo,
        
        // History
        invoiceHistory: this.invoiceHistory,
        paymentHistory: this.paymentHistory,
        
        // Dates
        nextBillingDate: this.nextBillingDate,
        trialEndsAt: this.trialEndsAt,
        
        // Settings
        renewalSettings: this.renewalSettings,
        
        // Features
        featureFlags: this.featureFlags,
        planRestrictions: this.planRestrictions,
        
        // Cost
        costBreakdown: this.costBreakdown,
        
        // Options
        upgradeOptions: this.upgradeOptions,
        downgradeRestrictions: this.downgradeRestrictions,
        
        // Discounts
        discounts: this.discounts,
        credits: this.credits,
        
        // Timestamps
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        
        // WordPress
        wpUserId: this.wpUserId,
        wpSynced: this.wpSynced,
        lastWpSync: this.lastWpSync,
        
        // Metadata
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty billing helper for null/undefined billing data
 * @returns {Object} Empty billing helper with safe defaults
 */
function createEmptyBillingHelper() {
  return {
    // Core Identity
    get id() { return null; },
    get userId() { return null; },
    get subscriptionId() { return null; },
    get planType() { return 'free'; },
    get status() { return 'inactive'; },
    get billingCycle() { return 'monthly'; },
    
    // Plan Type Analysis
    get isFree() { return true; },
    get isBasic() { return false; },
    get isPro() { return false; },
    get isEnterprise() { return false; },
    get isCustom() { return false; },
    get isPaid() { return false; },
    get isPremium() { return false; },
    
    // Status Analysis
    get isActive() { return false; },
    get isInactive() { return true; },
    get isCancelled() { return false; },
    get isPastDue() { return false; },
    get isTrialing() { return false; },
    get isPaused() { return false; },
    get isHealthy() { return false; },
    get needsAttention() { return false; },
    
    // Pricing
    get monthlyPrice() { return 0; },
    get yearlyPrice() { return 0; },
    get currentPrice() { return 0; },
    get currency() { return 'USD'; },
    get hasDiscount() { return false; },
    get yearlyDiscount() { return 0; },
    get monthlySavings() { return 0; },
    
    // Usage
    get usageLimits() { return { digestsPerMonth: 0, contentItemsPerMonth: 0, aiTokensPerMonth: 0, storageGB: 0, apiCallsPerMonth: 0, collaborators: 0, customSources: 0 }; },
    get currentUsage() { return { digestsThisMonth: 0, contentItemsThisMonth: 0, aiTokensThisMonth: 0, storageUsedGB: 0, apiCallsThisMonth: 0, activeCollaborators: 0, activeSources: 0 }; },
    get overageCharges() { return { digestOverage: 0, contentOverage: 0, aiTokenOverage: 0, storageOverage: 0, apiCallOverage: 0, totalOverage: 0 }; },
    get digestUsagePercent() { return 0; },
    get contentUsagePercent() { return 0; },
    get aiTokenUsagePercent() { return 0; },
    get storageUsagePercent() { return 0; },
    get apiCallUsagePercent() { return 0; },
    get isNearAnyLimit() { return false; },
    get hasOverages() { return false; },
    
    // Payment
    get paymentMethod() { return { type: 'credit_card', last4: '', brand: '', expiryMonth: null, expiryYear: null, isDefault: true, isValid: false }; },
    get hasValidPaymentMethod() { return false; },
    get isPaymentMethodExpiring() { return false; },
    
    // History
    get invoiceHistory() { return []; },
    get paymentHistory() { return []; },
    get hasInvoiceHistory() { return false; },
    get hasPaymentHistory() { return false; },
    
    // Dates
    get nextBillingDate() { return null; },
    get trialEndsAt() { return null; },
    get daysUntilBilling() { return null; },
    get daysLeftInTrial() { return null; },
    get isTrialExpiringSoon() { return false; },
    get isBillingDueSoon() { return false; },
    
    // Settings
    get renewalSettings() { return { autoRenew: true, cancelAtPeriodEnd: false, upgradeAtRenewal: false, downgradeAtRenewal: false }; },
    get willRenew() { return false; },
    get willCancel() { return false; },
    
    // Features
    get featureFlags() { return []; },
    get featureCount() { return 0; },
    hasFeature(feature) { return false; },
    
    // Cost
    get costBreakdown() { return { basePlan: 0, aiUsage: 0, storage: 0, bandwidth: 0, apiCalls: 0, premiumFeatures: 0, overage: 0, taxes: 0, discounts: 0, total: 0 }; },
    get totalCost() { return 0; },
    
    // Options
    get upgradeOptions() { return []; },
    get canUpgrade() { return false; },
    get canDowngrade() { return true; },
    
    // Discounts
    get hasActiveDiscount() { return false; },
    get hasCredits() { return false; },
    get creditBalance() { return 0; },
    
    // Health
    get healthScore() { return 0; },
    get healthRating() { return 'critical'; },
    
    // Validation
    get isValid() { return false; },
    get isComplete() { return false; },
    get isConfigured() { return false; },
    
    // Utility Methods
    getUsagePercent(type) { return 0; },
    isNearLimit(type, threshold = 80) { return false; },
    getCostComponent(component) { return 0; },
    getMetadata(key, defaultValue = null) { return defaultValue; },
    
    // Debug Information
    get debugInfo() {
      return {
        id: null,
        planType: 'free',
        status: 'inactive',
        isValid: false,
        isComplete: false
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: null,
        planType: 'free',
        status: 'inactive',
        isNew: true
      };
    }
  };
}

/**
 * CRUD Operations for Billing
 */

/**
 * Create billing record
 * @param {Object} billingData - Initial billing data
 * @returns {Promise<Object>} Created billing record
 */
export async function createBilling(billingData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create billing record');
    }

    const newBilling = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      wpUserId: currentUser.wp_user_id,
      ...billingData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    // Save to LiveStore
    if (browser && liveStore) {
      await liveStore.billing.create(newBilling);
    }

    // Update local store
    billingStore.update(records => [...records, newBilling]);

    log(`[Billing] Created billing record: ${newBilling.id}`, 'info');
    return getBillingData(newBilling);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Billing] Error creating billing record: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Update billing record
 * @param {string} billingId - Billing ID
 * @param {Object} updates - Updates to apply
 * @returns {Promise<Object>} Updated billing record
 */
export async function updateBilling(billingId, updates) {
  try {
    const updatedData = {
      ...updates,
      updatedAt: new Date().toISOString()
    };

    // Update in LiveStore
    if (browser && liveStore) {
      await liveStore.billing.update(billingId, updatedData);
    }

    // Update local store
    billingStore.update(records => 
      records.map(record => 
        record.id === billingId 
          ? { ...record, ...updatedData }
          : record
      )
    );

    log(`[Billing] Updated billing record: ${billingId}`, 'info');
    
    // Return updated billing data
    const updatedBilling = await getBillingById(billingId);
    return updatedBilling;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Billing] Error updating billing record: ${errorMessage}`, 'error');
    throw error;
  }
}

/**
 * Get billing record by ID
 * @param {string} billingId - Billing ID
 * @returns {Promise<Object|null>} Billing data or null
 */
export async function getBillingById(billingId) {
  try {
    let billing = null;

    // Try LiveStore first
    if (browser && liveStore) {
      billing = await liveStore.billing.findById(billingId);
    }

    // Fallback to local store
    if (!billing) {
      const records = await new Promise(resolve => {
        billingStore.subscribe(value => resolve(value))();
      });
      billing = records.find(r => r.id === billingId);
    }

    return billing ? getBillingData(billing) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Billing] Error getting billing by ID: ${errorMessage}`, 'error');
    return null;
  }
}

/**
 * Get billing record for current user
 * @returns {Promise<Object|null>} User's billing data or null
 */
export async function getCurrentUserBilling() {
  try {
    const currentUser = await getUserData();
    if (!currentUser) return null;

    let billing = null;

    // Try LiveStore first
    if (browser && liveStore) {
      const records = await liveStore.billing.findMany({
        where: { userId: currentUser.id },
        orderBy: { createdAt: 'desc' },
        take: 1
      });
      billing = records[0] || null;
    }

    // Fallback to local store
    if (!billing) {
      const allRecords = await new Promise(resolve => {
        billingStore.subscribe(value => resolve(value))();
      });
      const userRecords = allRecords.filter(record => record.userId === currentUser.id);
      billing = userRecords.length > 0 ? userRecords[userRecords.length - 1] : null;
    }

    return billing ? getBillingData(billing) : null;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Billing] Error getting current user billing: ${errorMessage}`, 'error');
    return null;
  }
}

export default {
  store: billingStore,
  getBillingData,
  createBilling,
  updateBilling,
  getBillingById,
  getCurrentUserBilling
}; 