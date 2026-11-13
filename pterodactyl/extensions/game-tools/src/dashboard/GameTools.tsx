import React, { FormEvent, useEffect, useState } from 'react';
import http from '@/api/http';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';

type Runtime = 'node' | 'python';
type Access = { game: string | null; settings: boolean; players: boolean; delegate: boolean; runtime: Runtime | null };
type RuntimeReport = {
    runtime: Runtime;
    autoUpdate: boolean | null;
    files: {
        kind: 'entry' | 'manifest' | 'lockfile';
        path: string | null;
        state: 'present' | 'missing' | 'unconfigured' | 'unavailable';
    }[];
};
type Field = { key: string; value: string; managed: boolean; min?: number; max?: number; text?: number; options?: string[] };
type Settings = { path: string; revision: string; canSave: boolean; fields: Field[] };
type Players = { ready: boolean; updated: number | null; enabled: boolean; online: string[]; allowlist: string[]; banned: string[]; receipts: Record<string, boolean> };
type Moderator = { id: number; username: string; enabled: boolean };

const language = {
    en: {
        name: 'Game Tools', settings: 'Game settings', players: 'Paper players', moderators: 'Moderator access',
        refresh: 'Refresh', unavailable: 'Game Tools are unavailable for this server or account.',
        loading: 'Loading…', review: 'Review changes', save: 'Confirm and save', cancel: 'Back to editing',
        old: 'Current', next: 'New', managed: 'Managed by the egg', readOnly: 'Your file permissions allow viewing only.',
        restart: 'Saved. Restart the game server when ready to apply these settings.',
        noChanges: 'No changes to save.', online: 'Online', allowlist: 'Whitelist', banned: 'Banned',
        enable: 'Enable whitelist', disable: 'Disable whitelist', player: 'Minecraft player name',
        allow: 'Add to whitelist', unallow: 'Remove from whitelist', ban: 'Ban', pardon: 'Pardon', kick: 'Kick',
        run: 'Apply action', empty: 'No players in this list.',
        confirm: 'Confirm this player action?',
        bridge: 'The Paper bridge is offline or missing. Install X3Players.jar in plugins and restart Paper.',
        pending: 'Waiting for Paper to confirm…', applied: 'Paper confirmed the action.',
        rejected: 'Paper did not reach the requested result. Check the player name and game console.',
        unknown: 'Paper did not confirm in time. Refresh the list before trying again.',
        delegate: 'Grant existing subusers access to this player center. Add new subusers from the Users tab.',
        noSubs: 'There are no subusers on this server.', grant: 'Grant', revoke: 'Revoke',
        failed: 'The request failed. Refresh and try again.',
        stale: 'The file changed since you opened it. Refresh settings before saving.',
        invalid: 'Check the setting values. A gamemode looks like grandlarc 1.',
        missing: 'The configuration file is missing. Complete the game installation first.',
        complex: 'This file contains ambiguous settings. Check it in the file manager.',
        wings: 'Wings could not finish the request. Check the current file and its .x3-previous backup before retrying a save.',
        gamemode: 'That .amx file is missing from /gamemodes.',
        runtime: 'Application checks', entry: 'Startup file', manifest: 'Dependency file', lockfile: 'npm lockfile',
        autoUpdate: 'Git auto-update on startup', enabled: 'Enabled', disabled: 'Disabled', unset: 'Not reported',
        present: 'Found', missingFile: 'Not found', unconfigured: 'No standard path configured', unavailableFile: 'Could not check',
        filter: 'Filter player lists', noMatches: 'No matching players.', updated: 'Last updated',
        discard: 'Discard unsaved changes and reload the settings?',
        restartBeforeSave: 'These changes take effect after the game server restarts.',
        overview: 'Review game settings, manage players, or check application startup files.',
    },
    ar: {
        name: 'أدوات الألعاب', settings: 'إعدادات اللعبة', players: 'لاعبو Paper', moderators: 'صلاحيات المشرفين',
        refresh: 'تحديث', unavailable: 'أدوات الألعاب غير متاحة لهذا السيرفر أو الحساب.',
        loading: 'جاري التحميل…', review: 'راجع التغييرات', save: 'أكد واحفظ', cancel: 'ارجع للتعديل',
        old: 'الحالي', next: 'الجديد', managed: 'يتحكّم به قالب السيرفر', readOnly: 'صلاحيات ملفاتك تسمح بالعرض فقط.',
        restart: 'تم الحفظ. أعد تشغيل سيرفر اللعبة بالوقت المناسب حتى تتطبق الإعدادات.',
        noChanges: 'ماكو تغييرات للحفظ.', online: 'المتصلون', allowlist: 'القائمة البيضاء', banned: 'المحظورون',
        enable: 'شغّل القائمة البيضاء', disable: 'أوقف القائمة البيضاء', player: 'اسم لاعب ماينكرافت',
        allow: 'أضف للقائمة البيضاء', unallow: 'احذف من القائمة البيضاء', ban: 'احظر', pardon: 'ارفع الحظر', kick: 'اطرد',
        run: 'نفّذ الإجراء', empty: 'ماكو لاعبين بهاي القائمة.',
        confirm: 'متأكد تريد تنفّذ هذا الإجراء؟',
        bridge: 'ربط Paper غير متصل. ضع X3Players.jar بمجلد plugins وأعد تشغيل Paper.',
        pending: 'بانتظار تأكيد Paper…', applied: 'Paper أكّد تنفيذ الإجراء.',
        rejected: 'Paper ما وصل للنتيجة المطلوبة. راجع اسم اللاعب والكونسل.',
        unknown: 'Paper ما أكّد الإجراء بالوقت المحدد. حدّث القائمة قبل إعادة المحاولة.',
        delegate: 'امنح مستخدمي السيرفر الحاليين صلاحية إدارة اللاعبين. تضيف مستخدمين جدد من تبويب Users.',
        noSubs: 'ماكو مستخدمين إضافيين بهذا السيرفر.', grant: 'امنح', revoke: 'ألغِ الصلاحية',
        failed: 'تعذّر تنفيذ الطلب. حدّث الصفحة وحاول مرة ثانية.',
        stale: 'الملف تغيّر بعد ما فتحته. حدّث الإعدادات قبل الحفظ.',
        invalid: 'راجع القيم. صيغة الـGamemode تكون مثل grandlarc 1.',
        missing: 'ملف الإعدادات مفقود. كمّل تثبيت اللعبة أولاً.',
        complex: 'الملف يحتوي إعدادات متعارضة أو صيغة خاصة. راجعه من مدير الملفات.',
        wings: 'Wings ما كمّل الطلب. إذا كنت تحفظ، راجع الملف ونسخة x3-previous قبل ما تعيد المحاولة.',
        gamemode: 'ملف .amx غير موجود بمجلد /gamemodes.',
        runtime: 'فحص التطبيق', entry: 'ملف التشغيل', manifest: 'ملف الاعتماديات', lockfile: 'ملف قفل npm',
        autoUpdate: 'تحديث Git عند التشغيل', enabled: 'مفعّل', disabled: 'متوقف', unset: 'غير محدد',
        present: 'موجود', missingFile: 'غير موجود', unconfigured: 'المسار القياسي غير محدد', unavailableFile: 'تعذّر الفحص',
        filter: 'فلتر قوائم اللاعبين', noMatches: 'ماكو أسماء مطابقة.', updated: 'آخر تحديث',
        discard: 'تلغي التعديلات غير المحفوظة وتعيد تحميل الإعدادات؟',
        restartBeforeSave: 'التعديلات تتطبق بعد إعادة تشغيل سيرفر اللعبة.',
        overview: 'راجع إعدادات اللعبة، أدِر اللاعبين، أو افحص ملفات تشغيل التطبيق.',
    },
};

const names: Record<string, [string, string]> = {
    difficulty: ['Difficulty', 'الصعوبة'],
    gamemode: ['Game mode', 'وضع اللعب'],
    'max-players': ['Maximum players', 'الحد الأقصى للاعبين'],
    'view-distance': ['View distance', 'مسافة الرؤية'],
    'simulation-distance': ['Simulation distance', 'مسافة المحاكاة'],
    pvp: ['Player vs player', 'القتال بين اللاعبين'],
    hostname: ['Server name', 'اسم السيرفر'],
    maxplayers: ['Maximum players', 'الحد الأقصى للاعبين'],
    gamemode0: ['Gamemode and rounds', 'اسم الـGamemode وعدد الجولات'],
    servername: ['Server name', 'اسم السيرفر'],
};

const errorLabels: Record<string, 'stale' | 'invalid' | 'missing' | 'complex' | 'wings' | 'gamemode' | 'bridge'> = {
    STALE_CONFIG: 'stale', INVALID_FIELD: 'invalid', INVALID_GAMEMODE: 'invalid', PLAYER_REQUIRED: 'invalid',
    CONFIG_MISSING: 'missing', INVALID_CONFIG: 'complex', DUPLICATE_FIELD: 'complex',
    COMPLEX_PROPERTIES: 'complex', MISSING_XML_FIELD: 'complex', FILE_LIMIT: 'complex', UNSAFE_FILE: 'complex',
    WINGS_UNAVAILABLE: 'wings', BACKUP_FAILED: 'wings', SAVE_UNVERIFIED: 'wings',
    GAMEMODE_MISSING: 'gamemode', BRIDGE_OFFLINE: 'bridge',
};

function GameToolsView({ uuid }: { uuid: string }) {
    const [ar, setAr] = useState(() => localStorage.getItem('x3-game-tools-language') === 'ar');
    const t = language[ar ? 'ar' : 'en'];
    const base = '/api/client/extensions/xthreegames/servers/' + uuid;
    const [access, setAccess] = useState<Access>();
    const [settings, setSettings] = useState<Settings>();
    const [runtime, setRuntime] = useState<RuntimeReport>();
    const [players, setPlayers] = useState<Players>();
    const [moderators, setModerators] = useState<Moderator[]>();
    const [draft, setDraft] = useState<Record<string, string>>({});
    const [review, setReview] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string>();
    const [note, setNote] = useState<'restart' | 'noChanges' | 'pending' | 'applied' | 'rejected' | 'unknown'>();
    const [pending, setPending] = useState<string>();
    const [player, setPlayer] = useState('');
    const [playerFilter, setPlayerFilter] = useState('');
    const [action, setAction] = useState<'allow' | 'unallow' | 'ban' | 'pardon' | 'kick'>('allow');

    const fail = (reason: unknown) => {
        const message = (reason as { response?: { data?: { errors?: { detail?: string }[]; message?: string } } })?.response?.data;
        setError(message?.errors?.[0]?.detail || message?.message || 'FAILED');
    };
    const getSettings = async () => {
        const { data } = await http.get<Settings>(base + '/settings');
        setSettings(data);
        setDraft({});
        setReview(false);
    };
    const getPlayers = async () => {
        const { data } = await http.get<Players>(base + '/players');
        setPlayers(data);
    };
    const getRuntime = async () => {
        const { data } = await http.get<RuntimeReport>(base + '/runtime');
        setRuntime(data);
    };
    const getModerators = async () => {
        const { data } = await http.get<Moderator[]>(base + '/moderators');
        setModerators(data);
    };
    const perform = async (fn: () => Promise<void>) => {
        setBusy(true);
        setError(undefined);
        setNote(undefined);
        try { await fn(); } catch (reason) { fail(reason); } finally { setBusy(false); }
    };

    useEffect(() => {
        let active = true;
        http.get<Access>(base).then(({ data }) => {
            if (!active) return;
            setAccess(data);
            if (data.settings) getSettings().catch(fail);
            if (data.runtime) getRuntime().catch(fail);
            if (data.players) getPlayers().catch(fail);
            if (data.delegate) getModerators().catch(fail);
        }).catch((reason) => { if (active) fail(reason); });
        return () => { active = false; };
    }, [base]);

    useEffect(() => {
        if (!pending) return;
        let active = true;
        let fetching = false;
        let attempt = 0;
        const check = async () => {
            if (fetching) return;
            fetching = true;
            try {
                const { data } = await http.get<Players>(base + '/players');
                if (!active) return;
                setPlayers(data);
                if (Object.prototype.hasOwnProperty.call(data.receipts, pending)) {
                    setNote(data.receipts[pending] ? 'applied' : 'rejected');
                    setPending(undefined);
                }
            } catch {
                // The command may already have reached Paper. Never resend it automatically.
            } finally {
                fetching = false;
            }
        };
        const timer = window.setInterval(() => {
            if (++attempt > 10) {
                setNote('unknown');
                setPending(undefined);
                window.clearInterval(timer);
            } else {
                void check();
            }
        }, 900);
        return () => { active = false; window.clearInterval(timer); };
    }, [base, pending]);

    useEffect(() => {
        if (!access?.players || pending) return;
        let active = true;
        const timer = window.setInterval(() => {
            if (document.hidden) return;
            http.get<Players>(base + '/players').then(({ data }) => {
                if (active) setPlayers(data);
            }).catch((reason) => {
                if (!active) return;
                setPlayers(undefined);
                fail(reason);
            });
        }, 12000);
        return () => { active = false; window.clearInterval(timer); };
    }, [access?.players, base, pending]);

    const changeList = (settings?.fields ?? []).filter((field) => !field.managed && draft[field.key] !== undefined && draft[field.key] !== field.value);
    const label = (key: string) => names[key]?.[ar ? 1 : 0] ?? key;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!settings || !changeList.length) return;
        if (!review) { setReview(true); return; }
        void perform(async () => {
            const changes: Record<string, string> = {};
            changeList.forEach((field) => { changes[field.key] = draft[field.key]; });
            const { data } = await http.post<{ restartRequired: boolean }>(base + '/settings', { revision: settings.revision, changes });
            setReview(false);
            setNote(data.restartRequired ? 'restart' : 'noChanges');
            await getSettings();
        });
    };
    const moderate = (mode: string, target?: string) => {
        if (['ban', 'kick', 'whitelist-on', 'whitelist-off'].indexOf(mode) !== -1 && !window.confirm(t.confirm)) return;
        void perform(async () => {
            const { data } = await http.post<{ receipt: string }>(base + '/players', { action: mode, player: target });
            setPending(data.receipt);
            setNote('pending');
        });
    };

    return (
        <ServerContentBlock title={t.name}>
            <div className='x3-games' lang={ar ? 'ar' : 'en'} dir={ar ? 'rtl' : 'ltr'} aria-busy={busy}>
                <header><div><h1>{t.name}</h1><p>{t.overview}</p></div><button type='button' onClick={() => {
                    localStorage.setItem('x3-game-tools-language', ar ? 'en' : 'ar'); setAr(!ar);
                }}>{ar ? 'English' : 'العربية'}</button></header>
                {!access && !error && <p>{t.loading}</p>}
                {access && !access.settings && !access.players && !access.runtime && <p>{t.unavailable}</p>}
                {error && <p role='alert' className='x3-games-error'>{t[errorLabels[error] ?? 'failed']}</p>}
                {note && <p role='status' className='x3-games-status' data-state={note}>{t[note]}</p>}
                {access?.runtime && <section>
                    <header><h2>{t.runtime} · {access.runtime === 'node' ? 'Node.js' : 'Python'}</h2>
                        <button type='button' disabled={busy} onClick={() => void perform(getRuntime)}>{t.refresh}</button></header>
                    {runtime && <>
                        <p>{t.autoUpdate} · {runtime.autoUpdate === null ? t.unset : runtime.autoUpdate ? t.enabled : t.disabled}</p>
                        <ul className='x3-games-review'>
                            {runtime.files.map((file) => <li key={file.kind}>
                                <strong>{t[file.kind]}</strong>
                                {file.path && <div><code dir='ltr'>{file.path}</code></div>}
                                <span className='x3-games-state' data-state={file.state}>{t[file.state === 'missing' ? 'missingFile' : file.state === 'unavailable' ? 'unavailableFile' : file.state]}</span>
                            </li>)}
                        </ul>
                    </>}
                </section>}
                {access?.settings && <section>
                    <header><h2>{t.settings}</h2><button type='button' disabled={busy} onClick={() => {
                        if (!changeList.length || window.confirm(t.discard)) void perform(getSettings);
                    }}>{t.refresh}</button></header>
                    {settings && <form onSubmit={submit}>
                        <p><code dir='ltr'>{settings.path}</code></p>
                        {!settings.canSave && <p>{t.readOnly}</p>}
                        {review && <p className='x3-games-status'>{t.restartBeforeSave}</p>}
                        {review ? <ul className='x3-games-review'>
                            {changeList.map((field) => <li key={field.key}>
                                <strong>{label(field.key)}</strong><div>{t.old} <code dir='auto'>{field.value || '—'}</code></div>
                                <div>{t.next} <code dir='auto'>{draft[field.key]}</code></div>
                            </li>)}
                        </ul> : <div className='x3-games-fields'>
                            {settings.fields.map((field) => <label key={field.key}>
                                {label(field.key)} {field.managed && <small>{t.managed}</small>}
                                {field.options ? <select dir='ltr' disabled={!settings.canSave || field.managed || busy}
                                    value={draft[field.key] ?? field.value} onChange={(event) => setDraft({ ...draft, [field.key]: event.target.value })}>
                                    {field.options.indexOf(field.value) === -1 && <option value={field.value}>{field.value || '—'}</option>}
                                    {field.options.map((value) => <option key={value} value={value}>{value}</option>)}
                                </select> : <input type={field.min ? 'number' : 'text'} dir={field.min ? 'ltr' : 'auto'} min={field.min} max={field.max}
                                    maxLength={field.text} disabled={!settings.canSave || field.managed || busy} value={draft[field.key] ?? field.value}
                                    onChange={(event) => setDraft({ ...draft, [field.key]: event.target.value })} />}
                            </label>)}
                        </div>}
                        {settings.canSave && <div className='x3-games-row'>
                            {review && <button type='button' disabled={busy} onClick={() => setReview(false)}>{t.cancel}</button>}
                            <button className='x3-games-primary' type='submit' disabled={!changeList.length || busy}>{review ? t.save : t.review}</button>
                        </div>}
                    </form>}
                </section>}
                {access?.players && <section>
                    <header><h2>{t.players}</h2><button type='button' disabled={busy || !!pending} onClick={() => void perform(getPlayers)}>{t.refresh}</button></header>
                    {players && !players.ready && <p>{t.bridge}</p>}
                    {players?.ready && <>
                        <div className='x3-games-row'>
                            <input type='search' aria-label={t.filter} placeholder={t.filter} value={playerFilter}
                                onChange={(event) => setPlayerFilter(event.target.value)} />
                            {players.updated !== null && <small>{t.updated} · {new Date(players.updated * 1000).toLocaleTimeString(ar ? 'ar' : 'en')}</small>}
                        </div>
                        <div className='x3-games-row'>
                            <button type='button' disabled={busy || !!pending} onClick={() => moderate(players.enabled ? 'whitelist-off' : 'whitelist-on')}>
                                {players.enabled ? t.disable : t.enable}
                            </button>
                        </div>
                        <div className='x3-games-fields'>
                            {(['online', 'allowlist', 'banned'] as const).map((key) => {
                                const filtered = players[key].filter((playerName) => playerName.toLowerCase().indexOf(playerFilter.trim().toLowerCase()) !== -1);
                                return <div key={key} className='x3-games-player-list'>
                                <h3>{t[key]} ({players[key].length})</h3>
                                {filtered.length ? <ul className='x3-games-names'>{filtered.map((playerName) => <li key={playerName}>
                                    <button type='button' dir='ltr' onClick={() => setPlayer(playerName)}>{playerName}</button>
                                </li>)}</ul> : <p>{players[key].length ? t.noMatches : t.empty}</p>}
                            </div>;
                            })}
                        </div>
                        <form className='x3-games-row' onSubmit={(event) => { event.preventDefault(); moderate(action, player); }}>
                            <input dir='ltr' aria-label={t.player} placeholder={t.player} value={player} maxLength={16}
                                required pattern='[A-Za-z0-9_]{1,16}' onChange={(event) => setPlayer(event.target.value)} />
                            <select aria-label={t.run} value={action} onChange={(event) => setAction(event.target.value as typeof action)}>
                                {(['allow', 'unallow', 'ban', 'pardon', 'kick'] as const).map((value) => <option key={value} value={value}>{t[value]}</option>)}
                            </select>
                            <button className='x3-games-primary' type='submit' disabled={busy || !!pending}>{t.run}</button>
                        </form>
                    </>}
                </section>}
                {access?.delegate && <section>
                    <header><h2>{t.moderators}</h2><button type='button' disabled={busy} onClick={() => void perform(getModerators)}>{t.refresh}</button></header>
                    <p>{t.delegate}</p>
                    {moderators?.length === 0 && <p>{t.noSubs}</p>}
                    {moderators?.map((moderator) => <div className='x3-games-row' key={moderator.id}>
                        <bdi>{moderator.username}</bdi>
                        <button type='button' disabled={busy} onClick={() => void perform(async () => {
                            await http.put(base + '/moderators/' + moderator.id, { enabled: !moderator.enabled });
                            await getModerators();
                        })}>{moderator.enabled ? t.revoke : t.grant}</button>
                    </div>)}
                </section>}
            </div>
        </ServerContentBlock>
    );
}

export default function GameTools() {
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    return uuid ? <GameToolsView key={uuid} uuid={uuid} /> : null;
}
