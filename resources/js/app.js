import './bootstrap';

const updateThemeButton = () => {
    const button = document.querySelector('[data-theme-toggle]');

    if (!button) return;

    const dark = document.documentElement.classList.contains('dark');
    button.setAttribute('aria-pressed', String(dark));
    button.setAttribute('title', dark ? 'Use light mode' : 'Use dark mode');
    button.querySelector('[data-sun-icon]').classList.toggle('hidden', !dark);
    button.querySelector('[data-moon-icon]').classList.toggle('hidden', dark);
};

document.addEventListener('DOMContentLoaded', () => {
    updateThemeButton();

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        updateThemeButton();
    });
});
