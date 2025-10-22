// Minimal error tracker utility
export function trackError (error, context = {}) {

  // Forward to console for now; replace with remote logging if needed
  // eslint-disable-next-line no-console
  console.error('ErrorTracker:', error, context);

}
