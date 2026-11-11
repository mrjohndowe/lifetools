'use strict';

const status = document.querySelector('#status');
const tabs = document.querySelectorAll('[data-view]');
for (const tab of tabs) {
    tab.addEventListener('click', () => {
        for (const button of tabs) {
            const selected = button === tab;
            button.setAttribute('aria-pressed', String(selected));
            document.getElementById(button.dataset.view).hidden = !selected;
        }
        status.textContent = '';
    });
}

for (const form of document.querySelectorAll('form')) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const fields = new FormData(form);
        const date = fields.get('date');
        const content = String(fields.get('title') ?? fields.get('entry')).trim();
        if (!content) return;
        const item = document.createElement('li');
        item.textContent = date ? date + ' · ' + content : content;
        form.closest('section').querySelector('ul').append(item);
        form.reset();
        status.textContent = date ? 'Event added.' : 'Journal entry added.';
    });
}

const reveal = document.querySelector('#vault button');
const sample = document.querySelector('#sample');
reveal.addEventListener('click', () => {
    sample.hidden = !sample.hidden;
    reveal.setAttribute('aria-expanded', String(!sample.hidden));
    reveal.textContent = sample.hidden ? 'Reveal sample' : 'Hide sample';
});
