const byId = (name) => document.getElementById(name);
const number = new Intl.NumberFormat('en', { maximumFractionDigits: 2 });
const clock = new Intl.DateTimeFormat('en-GB', { timeStyle: 'medium', timeZone: 'Asia/Baghdad' });
const fields = ['rx_mbps', 'tx_mbps', 'rx_pps', 'rx_drop_ps', 'cpu_pct', 'memory_pct', 'conntrack_pct',
    'rx_error_ps', 'tcp_drop_ps', 'udp_buffer_ps'];
const warnings = {
    stale: 'انقطع تحديث القياسات أو بانتظار أول عينة.',
    interface_missing: 'قراءة عدّادات واجهة الشبكة غير متاحة.',
    rx_mbps: 'تجاوز الترافيك الوارد الحد المضبوط.',
    tx_mbps: 'تجاوز الترافيك الصادر الحد المضبوط.',
    rx_pps: 'تجاوز عدد الحزم الواردة الحد المضبوط.',
    tx_pps: 'تجاوز عدد الحزم الصادرة الحد المضبوط.',
    rx_drop_ps: 'ارتفع عدد الحزم التي أسقطتها واجهة الاستقبال.',
    rx_error_ps: 'ارتفعت أخطاء الاستقبال.',
    cpu_pct: 'ارتفع استخدام المعالج.',
    memory_pct: 'ارتفع استخدام الذاكرة.',
    conntrack_pct: 'جدول تتبع الاتصالات قريب من الحد المضبوط.',
    tcp_drop_ps: 'ارتفعت إسقاطات قائمة انتظار اتصالات TCP.',
    udp_buffer_ps: 'ارتفعت أخطاء مخزن استقبال UDP IPv4.',
};
let history = [];
let timer;
let request;

function chart() {
    const canvas = byId('traffic');
    const { width, height } = canvas.getBoundingClientRect();
    const dpi = window.devicePixelRatio || 1;
    canvas.width = Math.round(width * dpi);
    canvas.height = Math.round(height * dpi);
    const context = canvas.getContext('2d');
    context.scale(dpi, dpi);
    const traces = [['rx_mbps', '#68d3ff'], ['tx_mbps', '#55dfb8']];
    const points = history.flatMap((item) => traces.map(([key]) => item[key])).filter(Number.isFinite);
    byId('chart-empty').hidden = points.length > 0;
    if (!points.length || width < 90) return;

    const max = Math.max(1, ...points) * 1.1;
    const left = 42;
    const right = width - 8;
    const bottom = height - 25;
    const top = 10;
    const start = history[0].ts;
    const duration = Math.max(1, history[history.length - 1].ts - start);
    context.font = '11px "Segoe UI", sans-serif';
    for (let tick = 0; tick <= 4; tick++) {
        const y = top + (bottom - top) * tick / 4;
        context.strokeStyle = '#29384c';
        context.beginPath();
        context.moveTo(left, y);
        context.lineTo(right, y);
        context.stroke();
        context.fillStyle = '#a6bbd2';
        context.fillText(number.format(max * (1 - tick / 4)), 1, y + 3);
    }
    for (const [key, color] of traces) {
        let linked = false;
        context.strokeStyle = color;
        context.lineWidth = 2;
        context.beginPath();
        for (const sample of history) {
            if (!Number.isFinite(sample[key])) {
                linked = false;
                continue;
            }
            const x = left + (sample.ts - start) / duration * (right - left);
            const y = bottom - sample[key] / max * (bottom - top);
            if (linked) context.lineTo(x, y);
            else context.moveTo(x, y);
            linked = true;
        }
        context.stroke();
    }
    context.fillStyle = '#a6bbd2';
    context.fillText(clock.format(start * 1000), left, height - 6);
    context.textAlign = 'right';
    context.fillText(clock.format(history[history.length - 1].ts * 1000), right, height - 6);
}

function render(data) {
    byId('node').textContent = data.node;
    byId('interface').textContent = data.interface;
    byId('demo').hidden = data.mode !== 'demo';
    byId('updated').textContent = data.latest ? clock.format(data.latest.ts * 1000) : '—';
    for (const field of fields) {
        const value = data.latest?.[field];
        byId(field).textContent = Number.isFinite(value)
            ? number.format(value) + (field.endsWith('_pct') ? '%' : '') : '—';
    }
    history = data.history;
    chart();
    const status = byId('connection');
    status.dataset.state = data.stale ? 'error' : 'ok';
    status.textContent = data.stale ? 'القياسات غير محدثة' : 'القياسات متصلة';
    const list = byId('alerts');
    list.replaceChildren();
    for (const warning of data.alerts.length ? data.alerts : ['normal']) {
        const item = document.createElement('li');
        item.textContent = warnings[warning] || 'المؤشرات ضمن الحدود المضبوطة حالياً.';
        item.dataset.warning = String(warning !== 'normal');
        list.append(item);
    }
}

async function refresh() {
    clearTimeout(timer);
    request?.abort();
    const active = new AbortController();
    request = active;
    let interval = 5;
    try {
        const windowMinutes = byId('window').value;
        const response = await fetch('/api/status?minutes=' + encodeURIComponent(windowMinutes), { signal: active.signal });
        if (!response.ok) throw new Error(String(response.status));
        const data = await response.json();
        if (active.signal.aborted) return;
        render(data);
        interval = data.interval;
    } catch (error) {
        if (active.signal.aborted) return;
        byId('connection').dataset.state = 'error';
        byId('connection').textContent = 'تعذّر الاتصال بالمراقب';
    } finally {
        if (request === active && !active.signal.aborted) timer = setTimeout(refresh, interval * 1000);
    }
}

byId('window').addEventListener('change', refresh);
new ResizeObserver(chart).observe(byId('traffic'));
refresh();
