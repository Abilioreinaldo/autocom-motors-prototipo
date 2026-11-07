import './bootstrap';

// Menu mobile
document.addEventListener('click', (e) => {
    const t = e.target.closest('[data-toggle]');
    if (!t) return;
    const el = document.querySelector(t.dataset.toggle);
    el?.classList.toggle('hidden');
});

