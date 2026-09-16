document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-reinvest-percent]');
    if (!button || button.disabled) return;
    const form = button.closest('[data-reinvest-form]');
    const input = form && form.querySelector('input[name="amount"]');
    if (!input || input.disabled) return;
    const cents = Math.round(Number(input.max) * 100);
    const percent = Number(button.dataset.reinvestPercent);
    if (!Number.isFinite(cents) || cents <= 0 || ![10, 50, 100].includes(percent)) return;
    input.value = (Math.round(cents * percent / 100) / 100).toFixed(2);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.focus();
});
