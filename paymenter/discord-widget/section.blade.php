    <section id="x3-discord" class="container" aria-labelledby="x3-discord-title" style="margin-block: 3rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,19rem),1fr));align-items:center;gap:1.5rem;padding:clamp(1rem,4vw,2rem);border:1px solid var(--st-line);border-radius:16px;background:linear-gradient(150deg,var(--st-panel),var(--st-low))">
            <div>
                <h2 id="x3-discord-title">{{ $studioLang === 'ar' ? 'مجتمع الاستضافة على ديسكورد' : 'Hosting community on Discord' }}</h2>
                <p>{{ $studioLang === 'ar' ? 'تابع أخبار الاستضافة وتواصل مع المجتمع.' : 'Follow hosting updates and chat with the community.' }}</p>
                <a class="x3-cta" href="INVITE_PLACEHOLDER" target="_blank" rel="noopener noreferrer">{{ $studioLang === 'ar' ? 'انضم إلى ديسكورد' : 'Join Discord' }}</a>
            </div>
            <iframe src="https://discord.com/widget?id=GUILD_ID_PLACEHOLDER&amp;theme=dark"
                title="{{ $studioLang === 'ar' ? 'ويدجت ديسكورد' : 'Discord community widget' }}"
                width="350" height="500" loading="lazy" dir="ltr"
                sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts"
                style="display:block;width:100%;max-width:350px;border:0;border-radius:12px;justify-self:center"></iframe>
        </div>
    </section>
