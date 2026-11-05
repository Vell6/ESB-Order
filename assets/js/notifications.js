/**
 * RM bu ani - Polling and Real-Time Notifications Manager
 * Compliant with PRD Section 2.4, 8.4, 8.5, 8.6, 8.7
 */

class PollingManager {
  constructor(endpoint, intervalMs, onDataCallback, options = {}) {
    this.endpoint = endpoint;
    this.baseInterval = intervalMs;
    this.currentInterval = intervalMs;
    this.onData = onDataCallback;
    this.options = options;
    this.timerId = null;
    this.isPolling = false;
    this.isRequestInProgress = false;
    this.consecutiveErrors = 0;
    this.lastDataHash = null;

    // Listen to tab visibility changes to pause/resume
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.pause();
      } else {
        this.resume();
      }
    });
  }

  start() {
    if (this.isPolling) return;
    this.isPolling = true;
    this.consecutiveErrors = 0;
    this.currentInterval = this.baseInterval;
    this.scheduleNext(100); // Immediate initial run
  }

  stop() {
    this.isPolling = false;
    if (this.timerId) {
      clearTimeout(this.timerId);
      this.timerId = null;
    }
  }

  pause() {
    if (this.timerId) {
      clearTimeout(this.timerId);
      this.timerId = null;
    }
  }

  resume() {
    if (this.isPolling && !document.hidden) {
      this.scheduleNext(200);
    }
  }

  scheduleNext(delayMs) {
    if (!this.isPolling) return;
    if (this.timerId) clearTimeout(this.timerId);

    this.timerId = setTimeout(() => {
      this.tick();
    }, delayMs);
  }

  async tick() {
    if (!this.isPolling || document.hidden || this.isRequestInProgress) {
      return;
    }

    this.isRequestInProgress = true;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000); // 8s timeout

    try {
      const baseUrl = typeof getBaseUrl === 'function' ? getBaseUrl() : '/';
      const fullUrl = this.endpoint.startsWith('http') ? this.endpoint : baseUrl + this.endpoint.replace(/^\//, '');

      const response = await fetch(fullUrl, {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      clearTimeout(timeoutId);

      if (response.ok) {
        const json = await response.json();
        this.consecutiveErrors = 0;
        this.currentInterval = this.baseInterval;

        // Compare data hash to avoid unnecessary UI redraws
        const serialized = JSON.stringify(json.data || json);
        if (serialized !== this.lastDataHash) {
          this.lastDataHash = serialized;
          if (typeof this.onData === 'function') {
            this.onData(json.data || json);
          }
        }
      } else {
        this.handleError(new Error(`Server error: ${response.status}`));
      }
    } catch (err) {
      this.handleError(err);
    } finally {
      this.isRequestInProgress = false;
      if (this.isPolling && !document.hidden) {
        this.scheduleNext(this.currentInterval);
      }
    }
  }

  handleError(err) {
    this.consecutiveErrors++;
    // Exponential backoff up to 30 seconds
    this.currentInterval = Math.min(this.baseInterval * Math.pow(1.5, this.consecutiveErrors), 30000);
    console.warn(`[Polling] ${this.endpoint} error (${this.consecutiveErrors}):`, err.message);
  }
}

// Global Polling Helper
window.createPoller = function(endpoint, intervalMs, callback, options = {}) {
  const p = new PollingManager(endpoint, intervalMs, callback, options);
  p.start();
  return p;
};
