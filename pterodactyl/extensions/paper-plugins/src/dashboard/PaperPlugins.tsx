import React, { FormEvent, useEffect, useRef, useState } from 'react';
import http from '@/api/http';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerContext } from '@/state/server';

interface Status {
    version: string | null;
    canInstall: boolean;
}

interface Project {
    id: string;
    title: string;
    author: string;
    description: string;
    downloads: number;
}

interface SearchPage {
    results: Project[];
    hasNext: boolean;
}

interface Dependency {
    projectId: string | null;
    versionId: string | null;
    fileName: string | null;
}

interface Release {
    id: string;
    name: string;
    channel: string;
    dependencies: Dependency[];
}

const copy = {
    en: {
        language: 'العربية',
        title: 'Paper Plugins',
        intro: 'Find a compatible plugin on Modrinth and install it in your server’s plugins folder.',
        game: 'Minecraft version',
        detect: 'From your server startup settings.',
        manual: 'Enter the exact Minecraft version shown by the version command in the console.',
        name: 'Search plugins',
        hint: 'Example: LuckPerms or ViaVersion',
        find: 'Search',
        popular: 'Leave the search blank to show popular plugins.',
        loading: 'Working…',
        loadFailed: 'Paper Plugins is unavailable for this server. Check your access and the server’s Paper egg.',
        failed: 'The request could not be completed.',
        denied: 'Your account needs create and update permissions to install files.',
        choose: 'Choose version',
        previous: 'Previous',
        next: 'Next',
        noResults: 'No matching plugins were found.',
        noVersions: 'This project has no installable JAR for that Minecraft version.',
        release: 'Plugin version',
        deps: 'Required dependencies reported by Modrinth',
        acknowledge: 'I will check or install these dependencies before restarting Paper.',
        dependency: 'See requirements on the project page',
        install: 'Install plugin',
        installed: 'Installed',
        restart: 'Restart the server from Console when you’re ready to load the plugin.',
        timeout: 'Check the plugins folder before retrying. The panel did not receive a final response.',
        source: 'View on Modrinth',
    },
    ar: {
        language: 'English',
        title: 'إضافات Paper',
        intro: 'ابحث عن بلوقن متوافق على Modrinth وثبّته مباشرة داخل مجلد إضافات سيرفرك.',
        game: 'إصدار ماينكرافت',
        detect: 'من إعدادات تشغيل السيرفر.',
        manual: 'اكتب رقم الإصدار بالضبط مثل ما يظهر بأمر version داخل الكونسل.',
        name: 'بحث عن بلوقن',
        hint: 'مثلاً LuckPerms أو ViaVersion',
        find: 'بحث',
        popular: 'اترك البحث فارغ حتى تظهر الإضافات الشائعة.',
        loading: 'جاري التنفيذ…',
        loadFailed: 'تبويب الإضافات غير متاح لهذا السيرفر. تحقّق من الصلاحيات ومن إعداد Paper.',
        failed: 'تعذّر تنفيذ الطلب.',
        denied: 'حتى تثبّت البلوقن، تحتاج صلاحية إنشاء الملفات وتعديلها.',
        choose: 'اختيار النسخة',
        previous: 'السابق',
        next: 'التالي',
        noResults: 'ماكو إضافات مطابقة.',
        noVersions: 'ماكو ملف JAR قابل للتثبيت لهذا الإصدار من ماينكرافت.',
        release: 'نسخة البلوقن',
        deps: 'الإضافات المطلوبة حسب Modrinth',
        acknowledge: 'أتحقّق من تثبيت الإضافات المطلوبة قبل إعادة تشغيل Paper.',
        dependency: 'راجع متطلبات الإضافة بصفحة المشروع',
        install: 'ثبّت البلوقن',
        installed: 'تم التثبيت',
        restart: 'أعد تشغيل السيرفر من الكونسل حتى يحمّل البلوقن.',
        timeout: 'راجع مجلد plugins قبل ما تعيد المحاولة. اللوحة ما استلمت النتيجة النهائية.',
        source: 'افتح في Modrinth',
    },
};

const gamePattern = /^[0-9]{1,3}\.[0-9]{1,3}(?:\.[0-9]{1,3})?$/;
const extras = {
    en: { retry: 'Try again', close: 'Back to results', showing: 'Results on this page', searchError: 'Could not load results.', working: 'Searching Modrinth…', versions: 'Loading compatible versions…', install: 'Installing plugin…' },
    ar: { retry: 'حاول مرة ثانية', close: 'ارجع للنتائج', showing: 'النتائج بهذه الصفحة', searchError: 'تعذّر تحميل النتائج.', working: 'جاري البحث في Modrinth…', versions: 'جاري تحميل الإصدارات المتوافقة…', install: 'جاري تثبيت البلوقن…' },
};
const projectUrl = (id: string) => `https://modrinth.com/project/${encodeURIComponent(id)}`;

const message = (error: unknown, fallback: string) => {
    const data = (error as { response?: { data?: { errors?: { detail?: string }[]; message?: string } } })
        ?.response?.data;
    return data?.errors?.[0]?.detail || data?.message || fallback;
};

const Catalog = ({ uuid }: { uuid: string }) => {
    const [language, setLanguage] = useState<'en' | 'ar'>(document.documentElement.lang.startsWith('ar') ? 'ar' : 'en');
    const [status, setStatus] = useState<Status>();
    const [statusError, setStatusError] = useState(false);
    const [statusAttempt, setStatusAttempt] = useState(0);
    const [game, setGame] = useState('');
    const [query, setQuery] = useState('');
    const [submitted, setSubmitted] = useState({ game: '', query: '', page: 1 });
    const [results, setResults] = useState<SearchPage>();
    const [project, setProject] = useState<Project>();
    const [releases, setReleases] = useState<Release[]>([]);
    const [releaseId, setReleaseId] = useState('');
    const [accept, setAccept] = useState(false);
    const [operation, setOperation] = useState<'working' | 'versions' | 'install'>();
    const busy = operation !== undefined;
    const [error, setError] = useState('');
    const [installed, setInstalled] = useState('');
    const selection = useRef<HTMLFormElement>(null);
    const t = copy[language];
    const ui = extras[language];
    const api = `/api/client/extensions/xthreepaper/servers/${uuid}`;
    const release = releases.find((option) => option.id === releaseId);

    useEffect(() => {
        let active = true;
        http.get<Status>(`${api}/status`).then(
            ({ data }) => {
                if (active) {
                    setStatus(data);
                    setGame(data.version || '');
                }
            },
            () => {
                if (active) setStatusError(true);
            }
        );
        return () => {
            active = false;
        };
    }, [api, statusAttempt]);

    useEffect(() => {
        if (project) selection.current?.focus();
    }, [project]);

    const resetSelection = () => {
        setProject(undefined);
        setReleases([]);
        setReleaseId('');
        setAccept(false);
        setInstalled('');
    };

    const run = async (kind: NonNullable<typeof operation>, action: () => Promise<void>, fallback = t.failed) => {
        setOperation(kind);
        setError('');
        try {
            await action();
        } catch (err) {
            setError(message(err, fallback));
        } finally {
            setOperation(undefined);
        }
    };

    const search = (criteria: typeof submitted) => {
        void run('working', async () => {
            const { data } = await http.get<SearchPage>(`${api}/search`, {
                params: { game: criteria.game, q: criteria.query, page: criteria.page },
            });
            resetSelection();
            setSubmitted(criteria);
            setResults(data);
        }, ui.searchError);
    };

    const open = (selected: Project) => {
        void run('versions', async () => {
            const { data } = await http.get<Release[]>(`${api}/projects/${selected.id}/versions`, {
                params: { game: submitted.game },
            });
            setProject(selected);
            setReleases(data);
            setAccept(false);
            setInstalled('');
            setReleaseId((data.find((version) => version.channel === 'release') || data[0])?.id || '');
        });
    };

    const install = (event: FormEvent) => {
        event.preventDefault();
        if (!project || !release || busy || !status?.canInstall || (release.dependencies.length > 0 && !accept)) {
            return;
        }
        void run('install', async () => {
            const { data } = await http.post<{ file: string }>(
                `${api}/install`,
                { projectId: project.id, versionId: release.id, game: submitted.game, acceptDependencies: accept },
                { timeout: 90000 }
            );
            setInstalled(data.file);
        }, t.timeout);
    };

    return (
        <section className="x3paper" lang={language} dir={language === 'ar' ? 'rtl' : 'ltr'} aria-busy={busy}>
            <header className="x3paper__hero">
                <div>
                    <h1>{t.title}</h1>
                    <p>{t.intro}</p>
                </div>
                <button type="button" onClick={() => setLanguage(language === 'en' ? 'ar' : 'en')}>
                    {t.language}
                </button>
            </header>

            {statusError && <div className="x3paper__notice"><p role="alert">{t.loadFailed}</p>
                <button type="button" onClick={() => { setStatusError(false); setStatusAttempt((attempt) => attempt + 1); }}>{ui.retry}</button>
            </div>}
            {!status && !statusError && <p role="status">{t.loading}</p>}
            {status && (
                <form className="x3paper__search" onSubmit={(event) => {
                    event.preventDefault();
                    if (!busy && gamePattern.test(game)) search({ game, query: query.trim(), page: 1 });
                }}>
                    <label>
                        <span>{t.game}</span>
                        <input dir="ltr" value={game} placeholder="1.21.8" required maxLength={11} readOnly={!!status.version}
                            disabled={busy} onChange={(event) => {
                                setGame(event.target.value.trim());
                                setResults(undefined);
                                setError('');
                                resetSelection();
                            }} />
                        <small>{status.version ? t.detect : t.manual}</small>
                    </label>
                    <label>
                        <span>{t.name}</span>
                        <input type="search" value={query} placeholder={t.hint} disabled={busy} maxLength={100}
                            onChange={(event) => setQuery(event.target.value)} />
                        <small>{t.popular}</small>
                    </label>
                    <button className="x3paper__primary" type="submit" disabled={busy || !gamePattern.test(game)}>{t.find}</button>
                </form>
            )}

            {status && !status.canInstall && <p className="x3paper__notice">{t.denied}</p>}
            {error && <p role="alert" className="x3paper__notice">{error}</p>}
            {operation && <p role="status" className="x3paper__working">{ui[operation]}</p>}

            {project && (
                <form ref={selection} tabIndex={-1} className="x3paper__selection" onSubmit={install}>
                    <div className="x3paper__title">
                        <h2 dir="auto">{project.title}</h2>
                        <a href={projectUrl(project.id)} target="_blank" rel="noopener noreferrer">{t.source} ↗</a>
                    </div>
                    {!release && <p>{t.noVersions}</p>}
                    {release && (
                        <>
                            <label>
                                <span>{t.release} · {submitted.game}</span>
                                <select dir="ltr" disabled={busy || !!installed} value={releaseId} onChange={(event) => {
                                    setReleaseId(event.target.value);
                                    setAccept(false);
                                }}>
                                    {releases.map((option) => (
                                        <option key={option.id} value={option.id}>{option.name} ({option.channel})</option>
                                    ))}
                                </select>
                            </label>
                            {!!release.dependencies.length && (
                                <div className="x3paper__notice">
                                    <strong>{t.deps}</strong>
                                    <ul>
                                        {release.dependencies.map((dep, index) => (
                                            <li key={index}>
                                                {dep.projectId ? (
                                                    <a href={projectUrl(dep.projectId)} target="_blank" rel="noopener noreferrer">
                                                        {dep.fileName || `Modrinth · ${dep.projectId}`} ↗
                                                    </a>
                                                ) : dep.fileName || t.dependency}
                                            </li>
                                        ))}
                                    </ul>
                                    <label className="x3paper__check">
                                        <input type="checkbox" checked={accept} disabled={busy || !!installed}
                                            onChange={(event) => setAccept(event.target.checked)} />
                                        <span>{t.acknowledge}</span>
                                    </label>
                                </div>
                            )}
                            <button className="x3paper__primary" type="submit"
                                disabled={busy || !!installed || !status?.canInstall || (release.dependencies.length > 0 && !accept)}>
                                {t.install}
                            </button>
                        </>
                    )}
                    {installed && (
                        <div role="status" className="x3paper__notice x3paper__success">
                            <strong>{t.installed}</strong>
                            <code dir="ltr">{installed}</code>
                            <p>{t.restart}</p>
                        </div>
                    )}
                    <a href="#x3-paper-results" onClick={(event) => {
                        if (busy) { event.preventDefault(); return; }
                        resetSelection();
                        document.getElementById('x3-paper-results')?.focus();
                    }} aria-disabled={busy || undefined}>{ui.close}</a>
                </form>
            )}

            {results && (
                <>
                    {!results.results.length && <p role="status">{t.noResults}</p>}
                    <div id="x3-paper-results" className="x3paper__results" tabIndex={-1}>
                    {!!results.results.length && <p>{ui.showing} · {results.results.length} · <bdi>{submitted.game}</bdi></p>}
                    <div className="x3paper__grid">
                        {results.results.map((item) => (
                            <article key={item.id}>
                                <h2 dir="auto">{item.title}</h2>
                                <small dir="auto">{item.author} · {item.downloads.toLocaleString(language)} ↓</small>
                                <p dir="auto">{item.description}</p>
                                <button type="button" disabled={busy} onClick={() => open(item)}>{t.choose}</button>
                            </article>
                        ))}
                    </div>
                    </div>
                    {(submitted.page > 1 || results.hasNext) && (
                        <nav className="x3paper__pages">
                            <button type="button" disabled={busy || submitted.page === 1}
                                onClick={() => search({ ...submitted, page: submitted.page - 1 })}>{t.previous}</button>
                            <span>{submitted.page}</span>
                            <button type="button" disabled={busy || !results.hasNext}
                                onClick={() => search({ ...submitted, page: submitted.page + 1 })}>{t.next}</button>
                        </nav>
                    )}
                </>
            )}
        </section>
    );
};

const PaperPlugins = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    return <ServerContentBlock title="Paper Plugins">{uuid && <Catalog key={uuid} uuid={uuid} />}</ServerContentBlock>;
};

export default PaperPlugins;
