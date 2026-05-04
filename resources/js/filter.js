const filterForm = document.querySelector('[data-filter-form]');

if (filterForm) {
    const inputs = filterForm.querySelectorAll('input,select');
    inputs.forEach((input) => {
        input.addEventListener('change', () => filterForm.submit());
    });
}
