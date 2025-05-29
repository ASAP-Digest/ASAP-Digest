/**
 * Usage Tracking System Examples
 * Demonstrates how to use the getUsageData() business object
 */

import { getUsageData, trackUsage, getUserUsage } from '../stores/usage.js';

// Example 1: Track a feature usage event
async function trackFeatureUsage() {
  try {
    const usageRecord = await trackUsage({
      eventType: 'feature_use',
      featureUsed: 'digest_creation',
      usageCount: 1,
      duration: 5000, // 5 seconds
      resourceConsumption: {
        cpu: 15,
        memory: 25,
        apiCalls: 3,
        aiTokens: 150
      },
      costAttribution: 0.05, // $0.05
      billingCategory: 'ai_usage'
    });

    console.log('Usage tracked:', usageRecord.debugInfo);
    console.log('Is billable:', usageRecord.isBillable);
    console.log('Usage rating:', usageRecord.usageRating);
  } catch (error) {
    console.error('Error tracking usage:', error);
  }
}

// Example 2: Track an AI processing event
async function trackAIProcessing() {
  try {
    const usageRecord = await trackUsage({
      eventType: 'ai_processing',
      featureUsed: 'content_summarization',
      usageCount: 1,
      duration: 12000, // 12 seconds
      resourceConsumption: {
        aiTokens: 2500,
        apiCalls: 1
      },
      costAttribution: 0.25, // $0.25
      billingCategory: 'ai_usage',
      metadata: {
        model: 'gpt-4',
        provider: 'openai',
        inputTokens: 1500,
        outputTokens: 1000
      }
    });

    console.log('AI processing tracked:', {
      id: usageRecord.id,
      cost: usageRecord.costAttribution,
      tokens: usageRecord.aiTokensUsage,
      isExpensive: usageRecord.isExpensive,
      isCostly: usageRecord.isCostly
    });
  } catch (error) {
    console.error('Error tracking AI usage:', error);
  }
}

// Example 3: Analyze usage data
function analyzeUsageData(rawUsageData) {
  const usage = getUsageData(rawUsageData);
  
  console.log('Usage Analysis:', {
    // Core info
    feature: usage.featureUsed,
    type: usage.eventType,
    count: usage.usageCount,
    
    // Type analysis
    isAI: usage.isAiProcessing,
    isAPI: usage.isApiCall,
    isFeature: usage.isFeatureUse,
    
    // Usage patterns
    isHeavy: usage.isHighUsage,
    isSingle: usage.isSingleUse,
    isRecent: usage.isRecent,
    
    // Resource consumption
    aiTokens: usage.aiTokensUsage,
    apiCalls: usage.apiCallsUsage,
    
    // Cost analysis
    cost: usage.costAttribution,
    hasCost: usage.hasCost,
    isExpensive: usage.isExpensive,
    billingType: usage.billingCategory,
    
    // Performance
    performanceRating: usage.performanceRating,
    hasErrors: usage.hasErrors,
    
    // Validation
    isValid: usage.isValid,
    isComplete: usage.isComplete,
    isBillable: usage.isBillable
  });
}

// Example 4: Get user usage history
async function getUserUsageHistory(userId) {
  try {
    // Get all usage for user
    const allUsage = await getUserUsage(userId, { limit: 50 });
    
    // Get only AI processing events
    const aiUsage = await getUserUsage(userId, { 
      eventType: 'ai_processing',
      limit: 20 
    });
    
    console.log('User Usage Summary:', {
      totalRecords: allUsage.length,
      aiRecords: aiUsage.length,
      totalCost: allUsage.reduce((sum, usage) => sum + usage.costAttribution, 0),
      aiCost: aiUsage.reduce((sum, usage) => sum + usage.costAttribution, 0),
      recentUsage: allUsage.filter(usage => usage.isRecent).length,
      expensiveUsage: allUsage.filter(usage => usage.isExpensive).length
    });
    
    // Analyze patterns
    const usagePatterns = {
      highUsageEvents: allUsage.filter(usage => usage.isHighUsage),
      errorEvents: allUsage.filter(usage => usage.hasErrors),
      billableEvents: allUsage.filter(usage => usage.isBillable),
      recentEvents: allUsage.filter(usage => usage.isRecent)
    };
    
    console.log('Usage Patterns:', usagePatterns);
    
  } catch (error) {
    console.error('Error getting usage history:', error);
  }
}

// Example 5: Monitor quota usage
function monitorQuotaUsage(rawUsageData) {
  const usage = getUsageData(rawUsageData);
  
  if (usage.hasQuotaLimit) {
    console.log('Quota Status:', {
      current: usage.currentQuota,
      limit: usage.quotaLimit,
      percentage: usage.quotaPercentage,
      remaining: usage.quotaRemaining,
      isNearLimit: usage.isNearQuotaLimit,
      isOverLimit: usage.isOverQuotaLimit,
      resetDate: usage.quotaResetDate
    });
    
    // Alert if near limit
    if (usage.isNearQuotaLimit) {
      console.warn('⚠️ Approaching quota limit!', {
        percentage: usage.quotaPercentage,
        remaining: usage.quotaRemaining
      });
    }
    
    // Alert if over limit
    if (usage.isOverQuotaLimit) {
      console.error('🚨 Quota limit exceeded!', {
        overage: usage.currentQuota - usage.quotaLimit
      });
    }
  }
}

// Example usage data for testing
const exampleUsageData = {
  id: 'usage-123',
  userId: 'user-456',
  eventType: 'ai_processing',
  featureUsed: 'content_summarization',
  usageCount: 1,
  timestamp: new Date().toISOString(),
  duration: 8000,
  resourceConsumption: {
    cpu: 20,
    memory: 30,
    aiTokens: 1200,
    apiCalls: 1
  },
  costAttribution: 0.15,
  billingCategory: 'ai_usage',
  quotaStatus: {
    current: 850,
    limit: 1000,
    percentage: 85,
    resetDate: '2024-01-01T00:00:00Z'
  }
};

// Run examples
console.log('=== Usage Tracking Examples ===');
analyzeUsageData(exampleUsageData);
monitorQuotaUsage(exampleUsageData);

export {
  trackFeatureUsage,
  trackAIProcessing,
  analyzeUsageData,
  getUserUsageHistory,
  monitorQuotaUsage
}; 