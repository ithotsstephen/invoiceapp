document.addEventListener('DOMContentLoaded', () => {
  const path = location.pathname;
  document.querySelectorAll('.nav a').forEach(a => {
    try {
      const aPath = new URL(a.href, window.location.origin).pathname;
      if (aPath === path) a.style.outline = '2px solid var(--primary)';
    } catch(e) {}
  });
});
