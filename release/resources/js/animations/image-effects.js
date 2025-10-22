/**
 * Lightweight image effects initializer.
 * Applies a simple fade-in when images load.
 */
function initImageEffects () {

  if (typeof document === 'undefined') {

    return;

  }

  const targets = document.querySelectorAll('[data-image-effect]');
  for (const element of targets) {

    try {

      element.style.transition = 'opacity 300ms ease';
      if (element.complete) {

        element.style.opacity = '1';

      } else {

        element.style.opacity = '0.8';
        element.addEventListener(
          'load',
          () => {

            element.style.opacity = '1';

          },
          {'once': true}
        );

      }

    } catch {
      // no-op
    }

  }

}

if (globalThis.window !== undefined) {

  if (document.readyState === 'loading') {

    globalThis.addEventListener('DOMContentLoaded', initImageEffects);

  } else {

    initImageEffects();

  }

}

export {initImageEffects};
