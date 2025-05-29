/**
 * Dashboard Configuration Management Store
 * Follows getUserData() pattern with comprehensive getters and local-first capabilities
 * Integrates with V6 Auto-Login system and LiveStore
 * 
 * @fileoverview Dashboard configuration business object for widget layout and user preferences
 */

import { writable } from 'svelte/store';
import { browser } from '$app/environment';
import { liveStore } from './livestore-config.js';
import { getUserData } from './user.js';
import { log } from '$lib/utils/log.js';

/**
 * Widget types
 * @typedef {'chart' | 'table' | 'metric' | 'list' | 'text' | 'calendar' | 'map' | 'custom'} WidgetType
 */

/**
 * Dashboard types
 * @typedef {'main' | 'analytics' | 'content' | 'admin' | 'custom'} DashboardType
 */

/**
 * Widget configuration
 * @typedef {Object} WidgetConfig
 * @property {string} id - Widget identifier
 * @property {WidgetType} type - Widget type
 * @property {string} title - Widget title
 * @property {number} x - X position in grid
 * @property {number} y - Y position in grid
 * @property {number} width - Width in grid units
 * @property {number} height - Height in grid units
 * @property {Object} settings - Widget-specific settings
 * @property {boolean} visible - Widget visibility
 * @property {string[]} dataSources - Associated data sources
 */

/**
 * Enhanced Dashboard Configuration object with comprehensive fields
 * @typedef {Object} DashboardData
 * @property {string} id - Dashboard instance identifier
 * @property {string} userId - User identifier
 * @property {WidgetConfig[]} widgetLayout - Widget layout configuration
 * @property {string[]} visibleWidgets - Currently visible widget IDs
 * @property {Object[]} widgetSettings - Widget-specific settings
 * @property {Object} userPreferences - User dashboard preferences
 * @property {Object[]} defaultViews - Default view configurations
 * @property {Object[]} customDashboards - Custom dashboard configurations
 * @property {Object[]} sharedDashboards - Shared dashboard access
 * @property {Object[]} refreshIntervals - Auto-refresh configurations
 * @property {string[]} dataSources - Available data sources
 * @property {Object[]} exportOptions - Export configuration options
 * @property {Object} collaborationSettings - Collaboration and sharing settings
 * @property {Date|string} createdAt - Creation timestamp
 * @property {Date|string} updatedAt - Last update timestamp
 * @property {Object} metadata - Additional metadata
 */

/** @type {import('svelte/store').Writable<DashboardData[]>} */
export const dashboardStore = writable([]);

/**
 * Normalize dashboard data from any source to consistent format
 * @param {Object} rawDashboardData - Raw dashboard data
 * @returns {Object|null} Normalized dashboard data
 */
function normalizeDashboardData(rawDashboardData) {
  if (!rawDashboardData || typeof rawDashboardData !== 'object' || !rawDashboardData.id) {
    return null;
  }

  return {
    // Core Identity
    id: rawDashboardData.id,
    userId: rawDashboardData.userId || rawDashboardData.user_id || null,
    
    // Widget Management
    widgetLayout: Array.isArray(rawDashboardData.widgetLayout) ? rawDashboardData.widgetLayout :
                  Array.isArray(rawDashboardData.widget_layout) ? rawDashboardData.widget_layout : [],
    visibleWidgets: Array.isArray(rawDashboardData.visibleWidgets) ? rawDashboardData.visibleWidgets :
                    Array.isArray(rawDashboardData.visible_widgets) ? rawDashboardData.visible_widgets : [],
    widgetSettings: Array.isArray(rawDashboardData.widgetSettings) ? rawDashboardData.widgetSettings :
                    Array.isArray(rawDashboardData.widget_settings) ? rawDashboardData.widget_settings : [],
    
    // User Preferences
    userPreferences: rawDashboardData.userPreferences || rawDashboardData.user_preferences || {
      theme: 'auto',
      compactMode: false,
      autoRefresh: true,
      defaultRefreshInterval: 300000, // 5 minutes
      gridSize: 'medium',
      animationsEnabled: true,
      tooltipsEnabled: true
    },
    
    // Views & Dashboards
    defaultViews: Array.isArray(rawDashboardData.defaultViews) ? rawDashboardData.defaultViews :
                  Array.isArray(rawDashboardData.default_views) ? rawDashboardData.default_views : [],
    customDashboards: Array.isArray(rawDashboardData.customDashboards) ? rawDashboardData.customDashboards :
                      Array.isArray(rawDashboardData.custom_dashboards) ? rawDashboardData.custom_dashboards : [],
    sharedDashboards: Array.isArray(rawDashboardData.sharedDashboards) ? rawDashboardData.sharedDashboards :
                      Array.isArray(rawDashboardData.shared_dashboards) ? rawDashboardData.shared_dashboards : [],
    
    // Configuration
    refreshIntervals: Array.isArray(rawDashboardData.refreshIntervals) ? rawDashboardData.refreshIntervals :
                      Array.isArray(rawDashboardData.refresh_intervals) ? rawDashboardData.refresh_intervals : [],
    dataSources: Array.isArray(rawDashboardData.dataSources) ? rawDashboardData.dataSources :
                 Array.isArray(rawDashboardData.data_sources) ? rawDashboardData.data_sources : [],
    exportOptions: Array.isArray(rawDashboardData.exportOptions) ? rawDashboardData.exportOptions :
                   Array.isArray(rawDashboardData.export_options) ? rawDashboardData.export_options : [],
    
    // Collaboration
    collaborationSettings: rawDashboardData.collaborationSettings || rawDashboardData.collaboration_settings || {
      allowSharing: true,
      allowPublicDashboards: false,
      defaultPermissions: 'view',
      requireApproval: true,
      maxSharedUsers: 10
    },
    
    // Timestamps
    createdAt: rawDashboardData.createdAt || rawDashboardData.created_at || new Date().toISOString(),
    updatedAt: rawDashboardData.updatedAt || rawDashboardData.updated_at || new Date().toISOString(),
    
    // Metadata
    metadata: rawDashboardData.metadata || {}
  };
}

/**
 * Get comprehensive dashboard data with computed properties
 * Follows getUserData() pattern with extensive getters
 * @param {Object} dashboard - Raw dashboard data
 * @returns {Object} Dashboard helper with getters and methods
 */
export function getDashboardData(dashboard) {
  const normalizedDashboard = normalizeDashboardData(dashboard);
  
  if (!normalizedDashboard) {
    return createEmptyDashboardHelper();
  }

  return {
    // Core Identity
    get id() { return normalizedDashboard.id; },
    get userId() { return normalizedDashboard.userId; },
    
    // Widget Layout
    get widgetLayout() { return normalizedDashboard.widgetLayout; },
    get visibleWidgets() { return normalizedDashboard.visibleWidgets; },
    get widgetSettings() { return normalizedDashboard.widgetSettings; },
    get widgetCount() { return this.widgetLayout.length; },
    get visibleWidgetCount() { return this.visibleWidgets.length; },
    get hasWidgets() { return this.widgetCount > 0; },
    get hasVisibleWidgets() { return this.visibleWidgetCount > 0; },
    
    // Widget Analysis
    get widgetsByType() {
      const types = {};
      this.widgetLayout.forEach(widget => {
        if (!types[widget.type]) types[widget.type] = [];
        types[widget.type].push(widget);
      });
      return types;
    },
    get chartWidgets() { return this.widgetLayout.filter(w => w.type === 'chart'); },
    get tableWidgets() { return this.widgetLayout.filter(w => w.type === 'table'); },
    get metricWidgets() { return this.widgetLayout.filter(w => w.type === 'metric'); },
    get listWidgets() { return this.widgetLayout.filter(w => w.type === 'list'); },
    get customWidgets() { return this.widgetLayout.filter(w => w.type === 'custom'); },
    get chartWidgetCount() { return this.chartWidgets.length; },
    get tableWidgetCount() { return this.tableWidgets.length; },
    get metricWidgetCount() { return this.metricWidgets.length; },
    
    // Grid Analysis
    get gridUtilization() {
      const totalGridArea = this.widgetLayout.reduce((total, widget) => {
        return total + (widget.width * widget.height);
      }, 0);
      const maxGridArea = Math.max(...this.widgetLayout.map(w => (w.x + w.width) * (w.y + w.height)));
      return maxGridArea > 0 ? (totalGridArea / maxGridArea) * 100 : 0;
    },
    get averageWidgetSize() {
      if (this.widgetCount === 0) return 0;
      const totalArea = this.widgetLayout.reduce((total, widget) => {
        return total + (widget.width * widget.height);
      }, 0);
      return totalArea / this.widgetCount;
    },
    get largestWidget() {
      return this.widgetLayout.reduce((largest, widget) => {
        const area = widget.width * widget.height;
        const largestArea = largest ? largest.width * largest.height : 0;
        return area > largestArea ? widget : largest;
      }, null);
    },
    get smallestWidget() {
      return this.widgetLayout.reduce((smallest, widget) => {
        const area = widget.width * widget.height;
        const smallestArea = smallest ? smallest.width * smallest.height : Infinity;
        return area < smallestArea ? widget : smallest;
      }, null);
    },
    
    // User Preferences
    get userPreferences() { return normalizedDashboard.userPreferences; },
    get theme() { return this.userPreferences.theme; },
    get compactMode() { return this.userPreferences.compactMode; },
    get autoRefresh() { return this.userPreferences.autoRefresh; },
    get defaultRefreshInterval() { return this.userPreferences.defaultRefreshInterval; },
    get gridSize() { return this.userPreferences.gridSize; },
    get animationsEnabled() { return this.userPreferences.animationsEnabled; },
    get tooltipsEnabled() { return this.userPreferences.tooltipsEnabled; },
    get isDarkTheme() { return this.theme === 'dark'; },
    get isLightTheme() { return this.theme === 'light'; },
    get isAutoTheme() { return this.theme === 'auto'; },
    get isCompactMode() { return this.compactMode; },
    get hasAutoRefresh() { return this.autoRefresh; },
    get refreshIntervalMinutes() { return Math.round(this.defaultRefreshInterval / 60000); },
    
    // Views & Dashboards
    get defaultViews() { return normalizedDashboard.defaultViews; },
    get customDashboards() { return normalizedDashboard.customDashboards; },
    get sharedDashboards() { return normalizedDashboard.sharedDashboards; },
    get defaultViewCount() { return this.defaultViews.length; },
    get customDashboardCount() { return this.customDashboards.length; },
    get sharedDashboardCount() { return this.sharedDashboards.length; },
    get hasDefaultViews() { return this.defaultViewCount > 0; },
    get hasCustomDashboards() { return this.customDashboardCount > 0; },
    get hasSharedDashboards() { return this.sharedDashboardCount > 0; },
    get totalDashboardCount() { 
      return this.defaultViewCount + this.customDashboardCount + this.sharedDashboardCount;
    },
    
    // Active Dashboards
    get activeDashboards() {
      return [
        ...this.defaultViews.filter(d => d.active),
        ...this.customDashboards.filter(d => d.active),
        ...this.sharedDashboards.filter(d => d.active)
      ];
    },
    get activeDashboardCount() { return this.activeDashboards.length; },
    get primaryDashboard() {
      return this.activeDashboards.find(d => d.isPrimary) || this.activeDashboards[0] || null;
    },
    
    // Data Sources
    get dataSources() { return normalizedDashboard.dataSources; },
    get dataSourceCount() { return this.dataSources.length; },
    get hasDataSources() { return this.dataSourceCount > 0; },
    get connectedDataSources() {
      return this.dataSources.filter(source => source.connected);
    },
    get connectedDataSourceCount() { return this.connectedDataSources.length; },
    get dataSourceConnectivity() {
      return this.dataSourceCount > 0 ? 
        (this.connectedDataSourceCount / this.dataSourceCount) * 100 : 0;
    },
    
    // Refresh Configuration
    get refreshIntervals() { return normalizedDashboard.refreshIntervals; },
    get refreshIntervalCount() { return this.refreshIntervals.length; },
    get hasCustomRefreshIntervals() { return this.refreshIntervalCount > 0; },
    get activeRefreshIntervals() {
      return this.refreshIntervals.filter(interval => interval.enabled);
    },
    get activeRefreshIntervalCount() { return this.activeRefreshIntervals.length; },
    get shortestRefreshInterval() {
      const intervals = this.activeRefreshIntervals.map(i => i.interval).filter(Boolean);
      return intervals.length > 0 ? Math.min(...intervals) : this.defaultRefreshInterval;
    },
    get longestRefreshInterval() {
      const intervals = this.activeRefreshIntervals.map(i => i.interval).filter(Boolean);
      return intervals.length > 0 ? Math.max(...intervals) : this.defaultRefreshInterval;
    },
    
    // Export Options
    get exportOptions() { return normalizedDashboard.exportOptions; },
    get exportOptionCount() { return this.exportOptions.length; },
    get hasExportOptions() { return this.exportOptionCount > 0; },
    get enabledExportOptions() { return this.exportOptions.filter(option => option.enabled); },
    get supportedExportFormats() {
      return [...new Set(this.exportOptions.map(option => option.format))];
    },
    get exportFormatCount() { return this.supportedExportFormats.length; },
    
    // Collaboration
    get collaborationSettings() { return normalizedDashboard.collaborationSettings; },
    get allowSharing() { return this.collaborationSettings.allowSharing; },
    get allowPublicDashboards() { return this.collaborationSettings.allowPublicDashboards; },
    get defaultPermissions() { return this.collaborationSettings.defaultPermissions; },
    get requireApproval() { return this.collaborationSettings.requireApproval; },
    get maxSharedUsers() { return this.collaborationSettings.maxSharedUsers; },
    get sharingEnabled() { return this.allowSharing; },
    get publicSharingEnabled() { return this.allowPublicDashboards; },
    get collaborationLevel() {
      let level = 0;
      if (this.allowSharing) level += 1;
      if (this.allowPublicDashboards) level += 1;
      if (this.defaultPermissions === 'edit') level += 1;
      if (!this.requireApproval) level += 1;
      return level; // 0-4 scale
    },
    
    // Shared Dashboard Analysis
    get sharedWithUsers() {
      return this.sharedDashboards.reduce((total, dashboard) => {
        return total + (dashboard.sharedWithUsers || []).length;
      }, 0);
    },
    get publicDashboards() {
      return this.sharedDashboards.filter(dashboard => dashboard.isPublic);
    },
    get publicDashboardCount() { return this.publicDashboards.length; },
    get hasPublicDashboards() { return this.publicDashboardCount > 0; },
    
    // Overall Dashboard Score
    get overallDashboardScore() {
      let score = 100;
      
      // Widget configuration (0-30 points)
      if (!this.hasWidgets) {
        score -= 30;
      } else {
        const utilizationPenalty = Math.max(0, (100 - this.gridUtilization) * 0.15);
        score -= utilizationPenalty;
      }
      
      // Data connectivity (0-25 points)
      if (!this.hasDataSources) {
        score -= 25;
      } else {
        const connectivityPenalty = (100 - this.dataSourceConnectivity) * 0.25;
        score -= connectivityPenalty;
      }
      
      // Dashboard variety (0-20 points)
      if (this.totalDashboardCount === 0) {
        score -= 20;
      } else if (this.totalDashboardCount < 3) {
        score -= 10;
      }
      
      // User experience (0-15 points)
      let uxPenalty = 0;
      if (!this.hasAutoRefresh) uxPenalty += 5;
      if (!this.animationsEnabled) uxPenalty += 3;
      if (!this.tooltipsEnabled) uxPenalty += 2;
      if (this.refreshIntervalMinutes > 30) uxPenalty += 5;
      score -= Math.min(15, uxPenalty);
      
      // Collaboration features (0-10 points)
      if (!this.sharingEnabled) {
        score -= 10;
      } else if (this.collaborationLevel < 2) {
        score -= 5;
      }
      
      return Math.max(0, Math.min(100, Math.round(score)));
    },
    get dashboardRating() {
      const score = this.overallDashboardScore;
      if (score >= 90) return 'excellent';
      if (score >= 75) return 'good';
      if (score >= 60) return 'average';
      if (score >= 40) return 'poor';
      return 'critical';
    },
    get needsAttention() {
      return !this.hasWidgets || !this.hasDataSources || 
             this.dataSourceConnectivity < 50 || this.totalDashboardCount === 0;
    },
    
    // Performance Analysis
    get performanceMetrics() {
      return {
        widgetCount: this.widgetCount,
        gridUtilization: this.gridUtilization,
        averageWidgetSize: this.averageWidgetSize,
        dataSourceConnectivity: this.dataSourceConnectivity,
        refreshIntervalMinutes: this.refreshIntervalMinutes,
        totalDashboards: this.totalDashboardCount
      };
    },
    get isHighPerformance() {
      return this.widgetCount <= 20 && this.gridUtilization <= 80 && 
             this.refreshIntervalMinutes >= 5;
    },
    get isOverloaded() {
      return this.widgetCount > 50 || this.gridUtilization > 95;
    },
    
    // Timestamps
    get createdAt() { return normalizedDashboard.createdAt; },
    get updatedAt() { return normalizedDashboard.updatedAt; },
    get metadata() { return normalizedDashboard.metadata; },
    
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
    get isRecent() { return this.age <= 1; },
    get isStale() { return this.daysSinceUpdate > 7; },
    
    // Validation
    get isValid() { return !!(this.id && this.userId); },
    get isComplete() { return this.isValid && this.hasWidgets && this.hasDataSources; },
    get isOperational() { return this.isComplete && this.overallDashboardScore >= 60; },
    
    // Utility Methods
    getWidgetById(widgetId) {
      return this.widgetLayout.find(widget => widget.id === widgetId);
    },
    getWidgetsByType(widgetType) {
      return this.widgetLayout.filter(widget => widget.type === widgetType);
    },
    getDashboardById(dashboardId) {
      return [...this.defaultViews, ...this.customDashboards, ...this.sharedDashboards]
        .find(dashboard => dashboard.id === dashboardId);
    },
    getDataSourceById(sourceId) {
      return this.dataSources.find(source => source.id === sourceId);
    },
    isWidgetVisible(widgetId) {
      return this.visibleWidgets.includes(widgetId);
    },
    getWidgetSettings(widgetId) {
      return this.widgetSettings.find(setting => setting.widgetId === widgetId);
    },
    
    // Debug Information
    get debugInfo() {
      return {
        id: this.id,
        userId: this.userId,
        widgetCount: this.widgetCount,
        visibleWidgetCount: this.visibleWidgetCount,
        gridUtilization: this.gridUtilization,
        dataSourceConnectivity: this.dataSourceConnectivity,
        totalDashboardCount: this.totalDashboardCount,
        overallDashboardScore: this.overallDashboardScore,
        dashboardRating: this.dashboardRating,
        needsAttention: this.needsAttention,
        isValid: this.isValid,
        isComplete: this.isComplete,
        isOperational: this.isOperational
      };
    },
    
    // Serialization
    toJSON() {
      return {
        id: this.id,
        userId: this.userId,
        widgetLayout: this.widgetLayout,
        visibleWidgets: this.visibleWidgets,
        widgetSettings: this.widgetSettings,
        userPreferences: this.userPreferences,
        defaultViews: this.defaultViews,
        customDashboards: this.customDashboards,
        sharedDashboards: this.sharedDashboards,
        refreshIntervals: this.refreshIntervals,
        dataSources: this.dataSources,
        exportOptions: this.exportOptions,
        collaborationSettings: this.collaborationSettings,
        createdAt: this.createdAt,
        updatedAt: this.updatedAt,
        metadata: this.metadata
      };
    }
  };
}

/**
 * Create empty dashboard helper for null/undefined data
 * @returns {Object} Empty dashboard helper with safe defaults
 */
function createEmptyDashboardHelper() {
  return {
    get id() { return null; },
    get userId() { return null; },
    get widgetLayout() { return []; },
    get visibleWidgets() { return []; },
    get widgetCount() { return 0; },
    get hasWidgets() { return false; },
    get userPreferences() { return { theme: 'auto' }; },
    get theme() { return 'auto'; },
    get customDashboards() { return []; },
    get dataSources() { return []; },
    get dataSourceConnectivity() { return 0; },
    get overallDashboardScore() { return 0; },
    get dashboardRating() { return 'critical'; },
    get needsAttention() { return true; },
    get isValid() { return false; },
    get isComplete() { return false; },
    get isOperational() { return false; },
    getWidgetById(widgetId) { return null; },
    getDashboardById(dashboardId) { return null; },
    get debugInfo() { return { id: null, isValid: false }; },
    toJSON() { return { id: null, isNew: true }; }
  };
}

/**
 * CRUD Operations for Dashboard
 */

export async function createDashboardConfig(configData) {
  try {
    const currentUser = await getUserData();
    if (!currentUser) {
      throw new Error('User must be authenticated to create dashboard config');
    }

    const newConfig = {
      id: crypto.randomUUID(),
      userId: currentUser.id,
      ...configData,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    if (browser && liveStore) {
      await liveStore.dashboard.create(newConfig);
    }

    dashboardStore.update(configs => [...configs, newConfig]);
    log(`[Dashboard] Created dashboard config: ${newConfig.id}`, 'info');
    return getDashboardData(newConfig);

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Dashboard] Error creating config: ${errorMessage}`, 'error');
    throw error;
  }
}

export async function addWidget(dashboardId, widgetData) {
  try {
    const widget = {
      id: crypto.randomUUID(),
      ...widgetData,
      visible: widgetData.visible !== false
    };

    dashboardStore.update(configs => 
      configs.map(config => 
        config.id === dashboardId 
          ? { 
              ...config, 
              widgetLayout: [...(config.widgetLayout || []), widget],
              visibleWidgets: [...(config.visibleWidgets || []), widget.id],
              updatedAt: new Date().toISOString()
            }
          : config
      )
    );

    log(`[Dashboard] Added widget: ${widget.title}`, 'info');
    return widget;

  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : 'Unknown error';
    log(`[Dashboard] Error adding widget: ${errorMessage}`, 'error');
    throw error;
  }
}

export default {
  store: dashboardStore,
  getDashboardData,
  createDashboardConfig,
  addWidget
};