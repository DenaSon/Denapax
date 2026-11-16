// Livewire navigation updates the document without DOMContentLoaded.
// Announce the new page to keyboard/screen-reader users while leaving normal
// initial page load, form autofocus and in-page anchors alone.
let isClientNavigation = false;

document.addEventListener('livewire:navigate', () => {
    isClientNavigation = true;
});

document.addEventListener('livewire:navigated', () => {
    if (!isClientNavigation || window.location.hash) return;

    const heading = document.querySelector('main h1');
    if (!heading) return;

    heading.setAttribute('tabindex', '-1');
    heading.focus({ preventScroll: true });
});
