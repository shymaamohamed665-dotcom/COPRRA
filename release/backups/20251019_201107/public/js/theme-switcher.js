/* eslint-env browser */
/* global document, localStorage */
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';

    if (currentTheme === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            let theme;
            if (document.documentElement.classList.contains('dark-mode')) {
                document.documentElement.classList.remove('dark-mode');
                theme = 'light';
            } else {
                document.documentElement.classList.add('dark-mode');
                theme = 'dark';
            }
            localStorage.setItem('theme', theme);
        });
    }
});
