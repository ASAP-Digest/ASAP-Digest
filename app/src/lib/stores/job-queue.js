/**
 * Job Queue Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/** @type {import('svelte/store').Writable<Object[]>} */
export const jobQueueStore = writable([]);

function normalizeJobQueueData(rawJobData) {
  if (!rawJobData || typeof rawJobData !== 'object' || !rawJobData.id) {
    return null;
  }

  return {
    id: rawJobData.id,
    userId: rawJobData.userId || rawJobData.user_id || null,
    pendingJobs: Array.isArray(rawJobData.pendingJobs) ? rawJobData.pendingJobs : [],
    runningJobs: Array.isArray(rawJobData.runningJobs) ? rawJobData.runningJobs : [],
    completedJobs: Array.isArray(rawJobData.completedJobs) ? rawJobData.completedJobs : [],
    failedJobs: Array.isArray(rawJobData.failedJobs) ? rawJobData.failedJobs : [],
    jobPriorities: Array.isArray(rawJobData.jobPriorities) ? rawJobData.jobPriorities : [],
    retryPolicies: Array.isArray(rawJobData.retryPolicies) ? rawJobData.retryPolicies : [],
    workerStatus: Array.isArray(rawJobData.workerStatus) ? rawJobData.workerStatus : [],
    queueHealth: rawJobData.queueHealth || {
      status: 'healthy',
      backlogSize: 0,
      averageProcessingTime: 0,
      errorRate: 0,
      throughput: 0
    },
    performanceMetrics: Array.isArray(rawJobData.performanceMetrics) ? rawJobData.performanceMetrics : [],
    errorHandling: rawJobData.errorHandling || {
      maxRetries: 3,
      retryDelay: 1000,
      deadLetterQueue: true,
      alertOnFailure: true
    },
    scheduledTasks: Array.isArray(rawJobData.scheduledTasks) ? rawJobData.scheduledTasks : [],
    cronStatus: rawJobData.cronStatus || {
      enabled: true,
      lastRun: null,
      nextRun: null,
      failureCount: 0
    },
    createdAt: rawJobData.createdAt || rawJobData.created_at || new Date().toISOString(),
    updatedAt: rawJobData.updatedAt || rawJobData.updated_at || new Date().toISOString(),
    metadata: rawJobData.metadata || {}
  };
}

export function getJobQueueData(jobQueue) {
  const normalizedJobQueue = normalizeJobQueueData(jobQueue);
  
  if (!normalizedJobQueue) {
    return createEmptyJobQueueHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedJobQueue.id; },
    get userId() { return normalizedJobQueue.userId; },
    
    // Job Queues
    get pendingJobs() { return normalizedJobQueue.pendingJobs; },
    get runningJobs() { return normalizedJobQueue.runningJobs; },
    get completedJobs() { return normalizedJobQueue.completedJobs; },
    get failedJobs() { return normalizedJobQueue.failedJobs; },
    get pendingJobCount() { return this.pendingJobs.length; },
    get runningJobCount() { return this.runningJobs.length; },
    get completedJobCount() { return this.completedJobs.length; },
    get failedJobCount() { return this.failedJobs.length; },
    get totalJobCount() { 
      return this.pendingJobCount + this.runningJobCount + this.completedJobCount + this.failedJobCount; 
    },
    get hasPendingJobs() { return this.pendingJobCount > 0; },
    get hasRunningJobs() { return this.runningJobCount > 0; },
    get hasFailedJobs() { return this.failedJobCount > 0; },
    get isActive() { return this.hasRunningJobs || this.hasPendingJobs; },
    
    // Job Priority Analysis
    get jobPriorities() { return normalizedJobQueue.jobPriorities; },
    get highPriorityJobs() { 
      return this.pendingJobs.filter(job => job.priority === 'high' || job.priority === 'urgent'); 
    },
    get lowPriorityJobs() { 
      return this.pendingJobs.filter(job => job.priority === 'low'); 
    },
    get normalPriorityJobs() { 
      return this.pendingJobs.filter(job => job.priority === 'normal' || !job.priority); 
    },
    get highPriorityJobCount() { return this.highPriorityJobs.length; },
    get hasHighPriorityJobs() { return this.highPriorityJobCount > 0; },
    
    // Job Types
    get jobsByType() {
      const allJobs = [...this.pendingJobs, ...this.runningJobs, ...this.completedJobs, ...this.failedJobs];
      const types = {};
      allJobs.forEach(job => {
        if (!types[job.type]) types[job.type] = [];
        types[job.type].push(job);
      });
      return types;
    },
    get jobTypeCount() { return Object.keys(this.jobsByType).length; },
    get mostCommonJobType() {
      const typeCounts = Object.entries(this.jobsByType).map(([type, jobs]) => ({ type, count: jobs.length }));
      if (typeCounts.length === 0) return { type: '', count: 0 };
      return typeCounts.reduce((max, current) => current.count > max.count ? current : max);
    },
    
    // Performance Analysis
    get performanceMetrics() { return normalizedJobQueue.performanceMetrics; },
    get averageJobDuration() {
      const completedWithDuration = this.completedJobs.filter(job => job.duration);
      if (completedWithDuration.length === 0) return 0;
      const totalDuration = completedWithDuration.reduce((sum, job) => sum + job.duration, 0);
      return totalDuration / completedWithDuration.length;
    },
    get fastestJob() {
      return this.completedJobs.reduce((fastest, job) => 
        (job.duration || Infinity) < (fastest?.duration || Infinity) ? job : fastest, null
      );
    },
    get slowestJob() {
      return this.completedJobs.reduce((slowest, job) => 
        (job.duration || 0) > (slowest?.duration || 0) ? job : slowest, null
      );
    },
    get jobsCompletedToday() {
      const today = new Date().toDateString();
      return this.completedJobs.filter(job => 
        new Date(job.completedAt).toDateString() === today
      );
    },
    get todayCompletionCount() { return this.jobsCompletedToday.length; },
    
    // Queue Health
    get queueHealth() { return normalizedJobQueue.queueHealth; },
    get queueStatus() { return this.queueHealth.status; },
    get backlogSize() { return this.queueHealth.backlogSize; },
    get averageProcessingTime() { return this.queueHealth.averageProcessingTime; },
    get errorRate() { return this.queueHealth.errorRate; },
    get throughput() { return this.queueHealth.throughput; },
    get isQueueHealthy() { return this.queueStatus === 'healthy'; },
    get isQueueOverloaded() { return this.backlogSize > 100 || this.errorRate > 10; },
    get queueEfficiency() {
      const totalProcessed = this.completedJobCount + this.failedJobCount;
      return totalProcessed > 0 ? (this.completedJobCount / totalProcessed) * 100 : 0;
    },
    
    // Worker Status
    get workerStatus() { return normalizedJobQueue.workerStatus; },
    get workerCount() { return this.workerStatus.length; },
    get activeWorkers() { return this.workerStatus.filter(worker => worker.status === 'active'); },
    get idleWorkers() { return this.workerStatus.filter(worker => worker.status === 'idle'); },
    get busyWorkers() { return this.workerStatus.filter(worker => worker.status === 'busy'); },
    get activeWorkerCount() { return this.activeWorkers.length; },
    get idleWorkerCount() { return this.idleWorkers.length; },
    get busyWorkerCount() { return this.busyWorkers.length; },
    get workerUtilization() {
      return this.workerCount > 0 ? (this.busyWorkerCount / this.workerCount) * 100 : 0;
    },
    get hasAvailableWorkers() { return this.idleWorkerCount > 0; },
    
    // Retry Policies & Error Handling
    get retryPolicies() { return normalizedJobQueue.retryPolicies; },
    get errorHandling() { return normalizedJobQueue.errorHandling; },
    get maxRetries() { return this.errorHandling.maxRetries; },
    get retryDelay() { return this.errorHandling.retryDelay; },
    get deadLetterQueue() { return this.errorHandling.deadLetterQueue; },
    get alertOnFailure() { return this.errorHandling.alertOnFailure; },
    get jobsWithRetries() { 
      return [...this.pendingJobs, ...this.runningJobs, ...this.failedJobs].filter(job => job.retryCount > 0); 
    },
    get retryJobCount() { return this.jobsWithRetries.length; },
    get averageRetryCount() {
      if (this.retryJobCount === 0) return 0;
      const totalRetries = this.jobsWithRetries.reduce((sum, job) => sum + job.retryCount, 0);
      return totalRetries / this.retryJobCount;
    },
    
    // Scheduled Tasks & Cron
    get scheduledTasks() { return normalizedJobQueue.scheduledTasks; },
    get cronStatus() { return normalizedJobQueue.cronStatus; },
    get scheduledTaskCount() { return this.scheduledTasks.length; },
    get hasScheduledTasks() { return this.scheduledTaskCount > 0; },
    get cronEnabled() { return this.cronStatus.enabled; },
    get cronLastRun() { return this.cronStatus.lastRun; },
    get cronNextRun() { return this.cronStatus.nextRun; },
    get cronFailureCount() { return this.cronStatus.failureCount; },
    get hasCronFailures() { return this.cronFailureCount > 0; },
    get activeScheduledTasks() { return this.scheduledTasks.filter(task => task.enabled); },
    get activeScheduledTaskCount() { return this.activeScheduledTasks.length; },
    
    // Time-based Analysis
    get oldestPendingJob() {
      return this.pendingJobs.reduce((oldest, job) => 
        new Date(job.createdAt) < new Date(oldest?.createdAt || Date.now()) ? job : oldest, null
      );
    },
    get oldestJobAge() {
      if (!this.oldestPendingJob) return 0;
      return Math.floor((Date.now() - new Date(this.oldestPendingJob.createdAt).getTime()) / (1000 * 60)); // minutes
    },
    get hasStaleJobs() { return this.oldestJobAge > 60; }, // older than 1 hour
    
    // Overall Queue Score
    get overallQueueScore() {
      let score = 100;
      
      // Queue health (0-30 points)
      if (!this.isQueueHealthy) score -= 15;
      if (this.isQueueOverloaded) score -= 15;
      
      // Worker efficiency (0-25 points)
      if (this.workerCount === 0) {
        score -= 25;
      } else {
        const utilizationPenalty = this.workerUtilization > 90 ? 10 : 0;
        score -= utilizationPenalty;
      }
      
      // Error rate (0-20 points)
      if (this.errorRate > 20) score -= 20;
      else if (this.errorRate > 10) score -= 10;
      else if (this.errorRate > 5) score -= 5;
      
      // Backlog management (0-15 points)
      if (this.backlogSize > 200) score -= 15;
      else if (this.backlogSize > 100) score -= 10;
      else if (this.backlogSize > 50) score -= 5;
      
      // Stale jobs (0-10 points)
      if (this.hasStaleJobs) score -= 10;
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get queueRating() {
      const score = this.overallQueueScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get needsAttention() {
      return !this.isQueueHealthy || this.isQueueOverloaded || this.hasStaleJobs || 
             this.errorRate > 10 || this.workerCount === 0;
    },
    
    // Timestamps
    get createdAt() { return normalizedJobQueue.createdAt; },
    get updatedAt() { return normalizedJobQueue.updatedAt; },
    get metadata() { return normalizedJobQueue.metadata; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId); },
    get isComplete() { return this.isValid && this.workerCount > 0; },
    get isOperational() { return this.isComplete && this.overallQueueScore >= 60; },
    
    // Utility Methods
    getJobById(jobId) {
      const allJobs = [...this.pendingJobs, ...this.runningJobs, ...this.completedJobs, ...this.failedJobs];
      return allJobs.find(job => job.id === jobId);
    },
    getJobsByType(jobType) {
      return this.jobsByType[jobType] || [];
    },
    getWorkerById(workerId) {
      return this.workerStatus.find(worker => worker.id === workerId);
    },
    getScheduledTaskById(taskId) {
      return this.scheduledTasks.find(task => task.id === taskId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        totalJobCount: this.totalJobCount,
        pendingJobCount: this.pendingJobCount,
        runningJobCount: this.runningJobCount,
        failedJobCount: this.failedJobCount,
        workerCount: this.workerCount,
        workerUtilization: this.workerUtilization,
        queueEfficiency: this.queueEfficiency,
        errorRate: this.errorRate,
        overallQueueScore: this.overallQueueScore,
        queueRating: this.queueRating,
        needsAttention: this.needsAttention,
        isValid: this.isValid,
        isOperational: this.isOperational
      };
    },
    
    // Serialization
    toJSON() {
      return normalizedJobQueue;
    }
  };
}

function createEmptyJobQueueHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get pendingJobs() { return []; },
    get runningJobs() { return []; },
    get completedJobs() { return []; },
    get failedJobs() { return []; },
    get totalJobCount() { return 0; },
    get workerCount() { return 0; },
    get queueEfficiency() { return 0; },
    get overallQueueScore() { return 0; },
    get queueRating() { return 'critical'; },
    get needsAttention() { return true; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getJobById() { return null; },
    getWorkerById() { return null; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

export async function createJobQueueConfig(configData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create job queue config');
    }

    const newConfig = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...configData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.jobQueue.create(newConfig);
    }

    jobQueueStore.update(configs => [...configs, newConfig]);
    log(`[JobQueue] Created job queue config: ${newConfig.id}`, 'info');
    return getJobQueueData(newConfig);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[JobQueue] Error creating config: ${errorMessage}`, 'error');
    throw error;
  }
}

export async function enqueueJob(jobData) {
  try {
    const job = {
      id: crypto.randomUUID(),
      ...jobData,
      status: 'pending',
      createdAt: new Date().toISOString(),
      retryCount: 0
    };

    log(`[JobQueue] Enqueued job: ${job.type}`, 'info');
    return job;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[JobQueue] Error enqueuing job: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: jobQueueStore,
  getJobQueueData,
  createJobQueueConfig,
  enqueueJob
}; 