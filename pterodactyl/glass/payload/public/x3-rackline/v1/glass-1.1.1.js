/* X3 Hosting — Rackline Glass. Console controls use the native Pterodactyl input. */
(() => {
  'use strict';
  const tabs = { '': 'الكونسول', files: 'الملفات', databases: 'قواعد البيانات', schedules: 'الجدولة',
    users: 'المستخدمون', backups: 'النسخ الاحتياطية', network: 'الشبكة', startup: 'الإقلاع',
    settings: 'الإعدادات', activity: 'النشاط' };
  const statLabels = { Address: 'عنوان الاتصال', Uptime: 'مدة التشغيل', 'CPU Load': 'المعالج',
    Memory: 'الذاكرة', Disk: 'التخزين', 'Network (Inbound)': 'الشبكة · وارد',
    'Network (Outbound)': 'الشبكة · صادر' };
  const size = (text, cpu) => {
    const m = text.trim().match(cpu ? /^([\d.]+)\s*%$/ : /^([\d.]+)\s*(B|KiB|MiB|GiB|TiB|PiB)$/i);
    if (!m) return NaN;
    const value = Number(m[1]);
    return cpu ? value : value * 1024 ** ['b','kib','mib','gib','tib','pib'].indexOf(m[2].toLowerCase());
  };
  function start() {
    const app = document.getElementById('app');
    const head = document.querySelector('meta[name="x3-rackline-brand"]');
    if (!app || !head || app.dataset.x3Rackline) return;
    const brand = head.content || 'X3 Hosting';
    app.dataset.x3Rackline = 'v1';
    const locale = window.PterodactylUser?.language || window.SiteConfiguration?.locale || document.documentElement.lang || navigator.language;
    const arabic = /^ar(?:[-_]|$)/i.test(locale || '');
    app.dataset.x3Locale = arabic ? 'ar' : 'en';
    document.documentElement.classList.add('x3-rackline-document');
    let frame = 0;
    const set = (el, key, val = '') => { if (el && el.dataset[key] !== val) el.dataset[key] = val; };
    const setAttr = (el, key, val) => { if (el && el.getAttribute(key) !== val) el.setAttribute(key, val); };
    const pathname = (a) => {
      try { const url = new URL(a.getAttribute('href'), location.origin); return url.origin === location.origin ? url.pathname : ''; }
      catch (_) { return ''; }
    };
    const title = () => {
      if (!document.title || document.title === 'Pterodactyl') document.title = brand;
      else if (document.title !== brand && !document.title.endsWith(' · ' + brand)) document.title += ' · ' + brand;
    };
    function button(label, key) {
      const element = document.createElement('button');
      element.type = 'button';
      element.textContent = label;
      set(element, key);
      return element;
    }
    function consoleControls(frameNode, pane, input, terminal) {
      if (!pane) return;
      let latest = frameNode.querySelector(':scope > [data-x3-latest]');
      if (!latest) {
        latest = button(arabic ? '↓ آخر السجل' : '↓ Latest', 'x3Latest');
        latest.addEventListener('click', () => {
          const native = frameNode.querySelector('.xterm > div:has(> svg[data-icon="arrow-down"])');
          if (native) native.click();
          else {
            const viewport = frameNode.querySelector('.xterm-viewport');
            if (viewport) viewport.scrollTop = viewport.scrollHeight;
          }
        });
        frameNode.append(latest);
      }
      if (latest.disabled !== !terminal) latest.disabled = !terminal;
      if (!input) return;
      const row = input.parentElement;
      set(row, 'x3CommandRow');
      let send = row.querySelector(':scope > [data-x3-send]');
      if (!send) {
        send = button(arabic ? 'إرسال' : 'Send', 'x3Send');
        send.addEventListener('click', () => {
          const current = row.querySelector('input[aria-label="Console command input."]');
          if (!current || current.disabled) return;
          current.focus();
          if (current.value.length) {
            current.dispatchEvent(new KeyboardEvent('keydown', {
              key: 'Enter', code: 'Enter', bubbles: true, cancelable: true,
            }));
          }
        });
        row.append(send);
      }
      if (send.disabled !== input.disabled) send.disabled = input.disabled;
      send.title = input.disabled
        ? (arabic ? 'بانتظار اتصال Wings' : 'Waiting for the Wings connection')
        : (arabic ? 'إرسال أمر السيرفر (Enter)' : 'Send server command (Enter)');
    }
    function brandFooter() {
      const creditLines = new Set();
      for (const link of app.querySelectorAll('a[href*="pterodactyl.io"],a[href*="blueprint.zip"]')) {
        const url = new URL(link.href);
        if (!['pterodactyl.io', 'www.pterodactyl.io', 'blueprint.zip', 'www.blueprint.zip'].includes(url.hostname) || url.pathname !== '/') continue;
        const line = link.closest('p');
        if (line && /©/.test(line.textContent)) creditLines.add(line);
      }
      const first = creditLines.values().next().value;
      if (!first) return;
      if (!first.parentElement.querySelector(':scope > [data-x3-footer]')) {
        const footer = document.createElement('div');
        const rights = document.createElement('span');
        const discord = document.createElement('a');
        set(footer, 'x3Footer');
        rights.textContent = 'X3-Hosting © 2020 - 2026';
        discord.href = 'https://discord.x3-hosting.xyz';
        discord.textContent = 'discord.x3-hosting.xyz';
        footer.append(rights, discord);
        first.after(footer);
      }
      for (const line of creditLines) set(line, 'x3NativeCredit');
    }
    function scan() {
      frame = 0;
      const path = location.pathname.replace(/\/+$/, '') || '/';
      const server = path.match(/^\/server\/([^/]+)(?:\/(.*))?$/);
      set(app,'x3Page',server ? (server[2] ? 'server-page' : 'console') :
        path === '/' ? 'dashboard' : path.startsWith('/auth/') ? 'auth' : 'account');
      const logo = app.querySelector('#logo > a[href="/"]');
      if (logo) {
        set(logo,'x3Brand',brand);
        setAttr(logo,'aria-label',brand + (arabic ? ' — الرئيسية' : ' — Home'));
        const bar = logo.parentElement.parentElement;
        set(bar,'x3Topbar');
        set(bar.parentElement,'x3TopbarOuter');
        const search = bar.querySelector('div.navigation-link:has(svg[data-icon="search"])');
        if (search && !search.hasAttribute('data-x3-search-trigger')) {
          set(search, 'x3SearchTrigger');
          setAttr(search, 'role', 'button');
          setAttr(search, 'tabindex', '0');
          setAttr(search, 'aria-label', arabic ? 'بحث السيرفرات' : 'Search servers');
          search.addEventListener('keydown', event => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            search.click();
          });
        }
      }
      const rows = Array.from(app.querySelectorAll('a[href^="/server/"]')).filter(a => a.querySelector(':scope > .status-bar'));
      for (const row of rows) set(row,'x3ServerRow');
      if (path === '/' && rows.length) set(rows[0].parentElement,'x3Fleet');
      brandFooter();
      if (path.startsWith('/auth/')) {
        const logo = app.querySelector('form img[src="/assets/svgs/pterodactyl.svg"]');
        if (logo) {
          set(logo.parentElement,'x3AuthBrand',brand);
          set(logo.parentElement.parentElement,'x3Auth');
          setAttr(logo,'alt',brand);
        }
      }
      if (server) {
        const base = '/server/' + server[1];
        const groups = new Map();
        for (const link of app.querySelectorAll('a[href^="/server/"]')) {
          const url = pathname(link).replace(/\/+$/, '');
          if (url !== base && !url.startsWith(base + '/')) continue;
          const key = url === base ? '' : url.slice(base.length + 1);
          if (!Object.prototype.hasOwnProperty.call(tabs,key)) continue;
          const group = groups.get(link.parentElement) || [];
          group.push([link,key]); groups.set(link.parentElement,group);
        }
        for (const [group, links] of groups) {
          if (links.length < 2 || !links.some(([,key])=>key==='')) continue;
          set(group,'x3Tabs'); set(group.parentElement,'x3TabsOuter');
          for (const [link,key] of links) {
            if (link.childElementCount || !link.textContent.trim()) continue;
            set(link,'x3Tab',tabs[key]);
            if (arabic) setAttr(link,'aria-label',tabs[key]);
          }
        }
      }
      if (server && !server[2]) {
        const command = app.querySelector('input[aria-label="Console command input."]');
        const term = app.querySelector('.xterm');
        const grid = (command || term)?.closest('.grid.grid-cols-4') || app.querySelector('.grid.grid-cols-4:has(> .grid.grid-cols-6)');
        const details = grid?.querySelector(':scope > .grid.grid-cols-6');
        if (grid && details) {
          set(grid,'x3ConsoleGrid'); set(details,'x3Details');
          const frameNode = Array.from(grid.children).find(el => el !== details);
          set(frameNode,'x3TerminalFrame');
          const pane = frameNode?.querySelector(':scope > div.relative > div:has(> div > div[id])');
          set(pane, 'x3LogPane');
          if (frameNode) consoleControls(frameNode, pane, command, term);
          const titleRow = grid.previousElementSibling;
          if (titleRow?.classList.contains('grid-cols-4') && titleRow.querySelector('h1')) set(titleRow,'x3ServerHeading');
          const charts = grid.nextElementSibling;
          if (charts?.classList.contains('md:grid-cols-3')) set(charts,'x3Charts');
          for (const box of details.children) {
            const label = box.querySelector('p');
            const kind = label?.textContent.trim();
            if (!Object.prototype.hasOwnProperty.call(statLabels,kind)) continue;
            set(box,'x3Stat',kind);
            set(label,'x3StatLabel',statLabels[kind]);
            if (arabic) setAttr(label,'aria-label',statLabels[kind]);
            const limit = label.nextElementSibling?.querySelector('.select-none');
            const all = label.nextElementSibling?.textContent || '';
            const top = limit ? size(limit.textContent.replace(/^\s*\/\s*/,''),kind==='CPU Load') : NaN;
            const value = limit ? size(all.slice(0,all.lastIndexOf(limit.textContent)),kind==='CPU Load') : NaN;
            if (['CPU Load','Memory','Disk'].includes(kind) && top>0 && Number.isFinite(value)) {
              set(box,'x3Meter','on');
              const next = Math.max(0,Math.min(100,value/top*100)).toFixed(2)+'%';
              if (box.style.getPropertyValue('--x3-meter') !== next) box.style.setProperty('--x3-meter',next);
            } else {
              set(box,'x3Meter','off');
            }
          }
        }
      }
      title();
    }
    const schedule = () => { if (!frame) frame = requestAnimationFrame(scan); };
    new MutationObserver((mutations) => {
      if (mutations.some(m => !(m.target.nodeType === 1 ? m.target : m.target.parentElement)?.closest('.xterm'))) schedule();
    }).observe(app,{childList:true,subtree:true,characterData:true,attributes:true,attributeFilter:['disabled']});
    const titleElement = document.querySelector('title');
    if (titleElement) new MutationObserver(title).observe(titleElement,{childList:true,characterData:true,subtree:true});
    window.addEventListener('popstate',schedule);
    schedule();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})();
