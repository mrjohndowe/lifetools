'use strict';

const language = JSON.parse(document.getElementById('lifetools-language').textContent);

function text(key, params = {}) {
    return language.messages[key].replace(/\{(\w+)\}/g, (placeholder, name) => params[name] ?? placeholder);
}

async function post(api, data) {
    const response = await fetch('index.php?api=' + api, { method: 'POST', body: data, cache: 'no-store' });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(result.error || text('lifetools_could_not_confirm_the_action'));
        error.responseReceived = response.status === 422 && typeof result.error === 'string';
        throw error;
    }
    return result;
}

const setup = document.getElementById('setup-form');
if (setup) {
    const fields = setup.elements;
    const steps = [...setup.querySelectorAll('.setup-step')];
    const next = document.getElementById('setup-next');
    const back = document.getElementById('setup-back');
    const submit = document.getElementById('setup-submit');
    const cancel = document.getElementById('setup-cancel');
    const message = document.getElementById('setup-feedback');
    const keyOutput = document.getElementById('setup-key');
    const copy = document.getElementById('copy-key');
    const draftKey = 'lifetools-setup:' + location.pathname;
    const safe = ['mode','path','mysql_host','mysql_port','mysql_database','mysql_user','login','name','zone','currency','format','theme','seed'];
    let step = 0;
    try {
        const draft = JSON.parse(sessionStorage.getItem(draftKey) || '{}');
        step = Math.min(Math.max(Number(draft.step) || 0, 0), 4);
        for (const key of safe) {
            if (Object.hasOwn(draft, key)) {
                const control = fields[key];
                if (control.type === 'checkbox') control.checked = draft[key];
                else control.value = draft[key];
            }
        }
    } catch {
        // Setup also works when browser storage is disabled.
    }
    function persist() {
        const draft = { step };
        for (const key of safe) draft[key] = fields[key].type === 'checkbox' ? fields[key].checked : fields[key].value;
        try { sessionStorage.setItem(draftKey, JSON.stringify(draft)); } catch { /* Optional draft. */ }
    }
    function render() {
        steps.forEach((section, index) => section.hidden = step !== index);
        const dual = fields.mode.value === 'dual';
        document.getElementById('mysql-options').hidden = !dual;
        document.getElementById('mysql-skipped').hidden = dual;
        document.getElementById('setup-progress').textContent = text('step_current_of_total', { current: step + 1, total: steps.length });
        setup.dataset.step = String(step + 1);
        next.hidden = step === 8;
        back.hidden = step === 0;
        submit.hidden = step !== 8;
        cancel.hidden = false;
        document.documentElement.dataset.theme = fields.theme.value;
        if (step === 8) {
            const summary = document.getElementById('setup-summary');
            summary.replaceChildren();
            const details = [
                ['storage', dual ? text('sqlite_with_mysql_backup') : text('sqlite_only')],
                ['sqlite_file', fields.path.value],
                ['administrator', fields.login.value],
                ['appearance', language.options[fields.theme.value]],
                ['time_zone', fields.zone.value],
                ['content', fields.seed.checked ? text('fictional_demo') : text('empty_workspace')],
            ];
            for (const [key, value] of details) {
                const term = document.createElement('dt');
                const description = document.createElement('dd');
                term.textContent = text(key);
                description.textContent = value;
                summary.append(term, description);
            }
        }
        persist();
    }
    function move(direction) {
        message.textContent = '';
        if (direction > 0) {
            for (const control of steps[step].querySelectorAll('input, select')) {
                if (!control.reportValidity()) return;
            }
            if (step === 4 && (!fields.key_saved.checked || !/^[A-Za-z0-9+/]{43}=$/.test(fields.key.value))) {
                message.textContent = text('enter_a_32_byte_base64_key');
                return;
            }
            if (step === 5 && fields.password.value !== fields.confirmation.value) {
                message.textContent = text('the_passwords_must_match');
                return;
            }
        }
        keyOutput.textContent = '';
        copy.hidden = true;
        step += direction;
        if (step === 3 && fields.mode.value !== 'dual') step += direction;
        render();
        const heading = steps[step].querySelector('h2');
        heading.tabIndex = -1;
        heading.focus();
    }
    next.addEventListener('click', () => move(1));
    back.addEventListener('click', () => move(-1));
    cancel.addEventListener('click', () => {
        message.textContent = text('setup_is_incomplete_lifetools_is_available');
        step = 0;
        render();
    });
    setup.noValidate = true;
    setup.addEventListener('submit', (event) => {
        if (step !== 8) {
            event.preventDefault();
            move(1);
        }
    });
    setup.addEventListener('input', () => {
        persist();
        document.documentElement.dataset.theme = fields.theme.value;
        const length = fields.password.value.length;
        document.getElementById('password-strength').textContent = length < 12 ? text('use_at_least_12_characters')
            : length < 16 ? text('meets_minimum_length') : text('long_password');
    });
    document.getElementById('generate-key').addEventListener('click', async () => {
        try {
            const result = await post('setup', new URLSearchParams({ action: 'generate_key', csrf: fields.csrf.value }));
            fields.key.value = result.key;
            fields.key_saved.checked = false;
            keyOutput.textContent = result.key;
            copy.hidden = false;
        } catch (error) {
            message.textContent = error.message;
        }
    });
    copy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(keyOutput.textContent);
            message.textContent = text('copied_store_the_key_somewhere_private');
        } catch {
            message.textContent = text('select_and_copy_the_displayed_key');
        }
    });
    document.getElementById('test-mysql').addEventListener('click', async () => {
        const status = document.getElementById('mysql-result');
        const data = new URLSearchParams({ action: 'test_mysql' });
        for (const name of ['csrf','mysql_host','mysql_port','mysql_database','mysql_user','mysql_password']) {
            data.set(name, fields[name].value);
        }
        status.textContent = text('checking_backup_connection');
        try {
            status.textContent = (await post('setup', data)).message;
        } catch (error) {
            status.textContent = text('you_can_continue_in_dual_mode', { error: error.message });
        }
    });
    render();
}

function bindStarRating(field) {
    const select = field.querySelector('select');
    const scores = [...select.options].map(option => option.value).filter(Boolean);
    const total = scores.length;
    const group = document.createElement('div');
    group.className = 'star-rating';
    group.setAttribute('role', 'radiogroup');
    group.setAttribute('aria-label', field.querySelector('label > span').textContent);
    const hint = document.createElement('small');
    hint.setAttribute('aria-live', 'polite');
    const buttons = scores.map((score) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'star-button';
        button.title = text('star_rating_score', { score, total });
        button.setAttribute('role', 'radio');
        button.setAttribute('aria-label', button.title);
        button.addEventListener('click', () => choose(select.value === score ? '' : score));
        group.append(button);
        return button;
    });
    function render() {
        const selected = select.value;
        buttons.forEach((button, index) => {
            const score = scores[index];
            const active = selected === score;
            button.textContent = Number(score) <= Number(selected) ? '★' : '☆';
            button.setAttribute('aria-checked', String(active));
            button.tabIndex = active || (!selected && index === 0) ? 0 : -1;
        });
        hint.textContent = selected ? text('star_rating_score', { score: selected, total }) : text('choose_star_rating');
    }
    function choose(score) {
        select.value = score;
        render();
        select.dispatchEvent(new Event('input', { bubbles: true }));
    }
    group.addEventListener('keydown', (event) => {
        const direction = { ArrowRight: 1, ArrowUp: 1, ArrowLeft: -1, ArrowDown: -1 }[event.key];
        if (!direction) return;
        event.preventDefault();
        const next = (buttons.indexOf(event.target) + direction + total) % total;
        choose(scores[next]);
        buttons[next].focus();
    });
    render();
    select.hidden = true;
    field.append(group, hint);
}

function bindEditor(form) {
    form.querySelectorAll('[data-star-rating]').forEach(bindStarRating);
    const pickups = form.querySelector('[data-delivery-pickups]');
    if (pickups) {
        const rows = pickups.querySelector('[data-pickup-rows]');
        const template = pickups.querySelector('template');
        const add = pickups.querySelector('[data-add-pickup]');
        const refresh = () => {
            add.disabled = rows.children.length >= 100;
            for (const button of rows.querySelectorAll('[data-remove-pickup]')) {
                button.hidden = rows.children.length === 1;
            }
        };
        add.addEventListener('click', () => {
            rows.append(template.content.cloneNode(true));
            rows.lastElementChild.querySelector('input').focus();
            refresh();
        });
        rows.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-pickup]');
            if (!remove || rows.children.length === 1) return;
            remove.closest('[data-pickup-row]').remove();
            refresh();
            add.focus();
            rows.dispatchEvent(new Event('input', { bubbles: true }));
        });
        refresh();
    }
    const { shift_start: start, shift_end: end, dash_minutes: dash } = form.elements;
    if (dash) {
        const updateDashTime = () => {
            const minutes = (Date.parse(end.value + 'Z') - Date.parse(start.value + 'Z')) / 60000;
            dash.value = minutes >= 0
                ? Math.floor(minutes / 60) + ':' + String(minutes % 60).padStart(2, '0')
                : '';
        };
        start.addEventListener('input', updateDashTime);
        end.addEventListener('input', updateDashTime);
    }
    const allDay = form.elements.all_day;
    if (allDay) {
        allDay.addEventListener('change', () => {
            for (const input of form.querySelectorAll('[data-event-time]')) {
                const prior = input.value;
                input.type = allDay.checked ? 'date' : 'datetime-local';
                if (prior) input.value = prior.slice(0, 10) + (allDay.checked ? '' : 'T09:00');
            }
        });
    }
    const mode = form.dataset.editor;
    if (!mode) return;
    const status = form.querySelector('.save-status');
    const saveButton = form.querySelector('button[type="submit"]');
    const actions = mode === 'auto' ? [...document.querySelectorAll('[data-record-action]')] : [];
    let current = 0;
    let acknowledged = 0;
    let running = false;
    let timer;
    let pending;
    function disableActions(disabled) {
        actions.forEach((action) => action.querySelector('button').disabled = disabled);
    }
    async function save() {
        clearTimeout(timer);
        if (running || (!pending && !form.checkValidity())) return;
        if (!pending) {
            const data = new FormData(form);
            const bytes = crypto.getRandomValues(new Uint8Array(16));
            const id = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
            data.set('request_id', id);
            pending = { version: current, data };
        }
        running = true;
        saveButton.disabled = true;
        if (mode === 'quick') form.querySelector('.form-grid').inert = true;
        status.textContent = text('saving');
        const submitted = pending.version;
        const attempted = current;
        try {
            const result = await post('save', pending.data);
            pending = undefined;
            form.elements.id.value = result.id;
            form.elements.revision.value = result.revision;
            actions.forEach((action) => action.elements.revision.value = result.revision);
            acknowledged = submitted;
            status.textContent = text('saved_to_sqlite');
            if (mode === 'quick' && current === submitted) {
                const link = document.createElement('a');
                link.href = 'index.php?' + new URLSearchParams({ view: 'record', kind: form.elements.kind.value, id: result.id });
                link.textContent = text('open_record');
                status.append(' ', link);
                form.querySelector('.form-grid').hidden = true;
                saveButton.hidden = true;
            }
        } catch (error) {
            if (error.responseReceived) pending = undefined;
            status.textContent = text('your_edits_are_still_here_select', { error: error.message });
        } finally {
            running = false;
            saveButton.disabled = false;
            if (mode === 'quick') form.querySelector('.form-grid').inert = false;
            disableActions(acknowledged !== current);
            if (current !== attempted || (!pending && current !== submitted && acknowledged === submitted)) {
                timer = setTimeout(save, 250);
            }
        }
    }
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        save();
    });
    if (mode === 'auto') {
        const changed = () => {
            current++;
            disableActions(true);
            status.textContent = text('unsaved_edits');
            clearTimeout(timer);
            timer = setTimeout(save, 750);
        };
        form.addEventListener('input', changed);
        window.addEventListener('online', () => {
            if (current !== acknowledged) save();
        });
        window.addEventListener('beforeunload', (event) => {
            if (current !== acknowledged) event.preventDefault();
        });
    }
}
document.querySelectorAll('.editor').forEach(bindEditor);

const quick = document.getElementById('quick-add');
if (quick) {
    const type = quick.querySelector('select');
    const body = document.getElementById('quick-body');
    let generation = 0;
    async function load() {
        const id = ++generation;
        body.textContent = text('loading');
        try {
            const response = await fetch('index.php?quick=' + encodeURIComponent(type.value));
            if (!response.ok || response.headers.get('X-LifeTools-Fragment') !== 'editor') {
                throw new Error(text('reload_the_page_and_try_again'));
            }
            const html = await response.text();
            if (id !== generation) return;
            body.innerHTML = html;
            bindEditor(body.querySelector('form'));
        } catch (error) {
            if (id === generation) body.textContent = error.message;
        }
    }
    document.querySelectorAll('[data-open="quick-add"]').forEach((button) => button.addEventListener('click', () => {
        quick.showModal();
        load();
    }));
    type.addEventListener('change', load);
    quick.addEventListener('close', () => {
        generation++;
        body.replaceChildren();
    });
}

const reveal = document.getElementById('reveal');
if (reveal) {
    const form = document.getElementById('reveal-form');
    const status = document.getElementById('reveal-status');
    const block = document.getElementById('reveal-value');
    const value = document.getElementById('secret-output');
    const copy = document.getElementById('copy-secret');
    let timer;
    let generation = 0;
    function clear() {
        generation++;
        clearTimeout(timer);
        form.elements.password.value = '';
        form.hidden = false;
        block.hidden = true;
        value.textContent = '';
        status.textContent = '';
        copy.textContent = text('copy');
    }
    document.querySelectorAll('[data-reveal]').forEach((button) => button.addEventListener('click', () => {
        clear();
        for (const key of ['id','kind','field']) form.elements[key].value = button.dataset[key];
        reveal.showModal();
        form.elements.password.focus();
    }));
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const seq = ++generation;
        const data = new FormData(form);
        form.elements.password.value = '';
        status.textContent = text('checking_password');
        try {
            const result = await post('reveal', data);
            if (seq !== generation || !reveal.open) return;
            value.textContent = result.value;
            form.hidden = true;
            block.hidden = false;
            timer = setTimeout(() => reveal.close(), result.expiresIn * 1000);
        } catch (error) {
            if (seq === generation) status.textContent = error.message;
        }
    });
    copy.addEventListener('click', async () => {
        try {
            const copied = value.textContent;
            await navigator.clipboard.writeText(copied);
            copy.textContent = text('copied');
            setTimeout(async () => {
                try {
                    if (await navigator.clipboard.readText() === copied) await navigator.clipboard.writeText('');
                } catch { /* The browser may require a new gesture. */ }
            }, 30000);
        } catch {
            copy.textContent = text('copy_unavailable');
        }
    });
    reveal.addEventListener('close', clear);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && reveal.open) reveal.close();
    });
    window.addEventListener('pagehide', clear);
}

document.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
document.querySelector('[data-maintenance]')?.addEventListener('submit', (event) => {
    if (event.currentTarget.elements.operation.value === 'restore' && !window.confirm(text('replace_the_workspace_with_this_backup'))) {
        event.preventDefault();
    }
});

document.querySelectorAll('[data-password-generator]').forEach((button) => button.addEventListener('click', () => {
    const length = Number(window.prompt(text('password_length_12_64'), '24'));
    if (!Number.isInteger(length) || length < 12 || length > 64) return;
    const choices = { l: 'abcdefghijklmnopqrstuvwxyz', u: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', n: '0123456789', s: '!@#$%^&*_-+=?' };
    const selected = [...new Set((window.prompt(text('character_sets_l_lowercase_u_uppercase'), 'luns') || '').toLowerCase())]
        .map((key) => choices[key]).filter(Boolean);
    if (!selected.length) return;
    function pick(size) {
        const bytes = new Uint32Array(1);
        const limit = 2 ** 32 - (2 ** 32 % size);
        do { crypto.getRandomValues(bytes); } while (bytes[0] >= limit);
        return bytes[0] % size;
    }
    const alphabet = selected.join('');
    const password = selected.map((set) => set[pick(set.length)]);
    while (password.length < length) password.push(alphabet[pick(alphabet.length)]);
    for (let i = length - 1; i > 0; i--) {
        const j = pick(i + 1);
        [password[i], password[j]] = [password[j], password[i]];
    }
    button.closest('.secret-field').querySelector('input[type="password"]').value = password.join('');
    button.textContent = text('generated_save_to_keep');
}));

const theme = document.querySelector('[data-theme-form]');
if (theme) {
    theme.querySelector('button').hidden = true;
    theme.elements.theme.addEventListener('change', async () => {
        try {
            const result = await post('theme', new FormData(theme));
            document.documentElement.dataset.theme = result.theme;
        } catch {
            theme.submit();
        }
    });
}

if (document.querySelector('.layout')?.dataset.mode === 'dual') {
    const token = document.querySelector('[name="csrf"]').value;
    async function sync() {
        if (!document.hidden) {
            try {
                const status = await post('sync', new URLSearchParams({ action: 'sync_tick', csrf: token }));
                const banner = document.getElementById('mirror-banner');
                banner.hidden = status.mysql === 'reachable';
                if (!banner.hidden) {
                    banner.textContent = text('mysql_backup_is_degraded_or_pending', { error: status.lastError || '' });
                }
                document.querySelectorAll('[data-health]').forEach((target) => {
                    const field = target.dataset.health;
                    const value = status[field];
                    target.textContent = field === 'mysql' ? (language.options[value] ?? value) : (value ?? '—');
                });
            } catch { /* Retry independently of editing. */ }
        }
        setTimeout(sync, 4000);
    }
    setTimeout(sync, 800);
}


const dueList = document.getElementById('due-list');
if (dueList) {
    let previous;
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const response = await fetch('index.php?api=reminders', { cache: 'no-store' });
            if (!response.ok) return;
            const result = await response.json();
            const current = JSON.stringify(result);
            if (current === previous) return;
            previous = current;
            document.getElementById('due-count').textContent = result.count;
            dueList.replaceChildren();
            if (!result.events.length) {
                const empty = document.createElement('p');
                empty.className = 'empty-inline';
                empty.textContent = text('youre_caught_up_on_reminders');
                dueList.append(empty);
            }
            for (const event of result.events) {
                const link = document.createElement('a');
                link.className = 'event-chip';
                link.href = event.url;
                const title = document.createElement('span');
                title.textContent = event.title;
                const time = document.createElement('small');
                time.textContent = event.time;
                link.append(title, time);
                dueList.append(link);
            }
        } catch {
            // Leave the existing reminders in place during an offline interval.
        }
    }, 30000);
}

