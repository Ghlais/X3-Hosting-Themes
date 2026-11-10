@php
    $studioAr = str_starts_with(app()->getLocale(), 'ar');
    $studioDepth = require base_path('themes/x3-hosting/lang/' . ($studioAr ? 'ar' : 'en') . '/depth.php');
@endphp
<figure class="x3-studio-figure">
    <div class="x3-studio-stage" data-x3-depth="rack" aria-hidden="true" dir="ltr">
        <div class="x3-studio-orbit"></div><div class="x3-studio-grid"></div><div class="x3-studio-cast-shadow"></div>
        <div class="x3-studio-coordinate x3-studio-coordinate-top"><span>RACKLINE STUDIO</span><span>01 / 03</span></div>
        <div class="x3-studio-machine">
            <div class="x3-studio-face x3-studio-top"><div></div><span>X3 / RACKLINE</span></div>
            <div class="x3-studio-face x3-studio-side"><span>X3</span><i></i><i></i><i></i></div>
            <div class="x3-studio-face x3-studio-front">
                <div class="x3-studio-machine-head"><x-logo class="x3-studio-machine-logo" /><span>RACKLINE<br><b>STUDIO SERIES</b></span><i></i></div>
                <div class="x3-studio-bays">
                    @foreach(['01', '02', '03'] as $studioBay)
                    <div class="x3-studio-bay"><small>{{ $studioBay }}</small><span class="x3-studio-vents"></span><span class="x3-studio-handle"></span><span class="x3-studio-led"></span></div>
                    @endforeach
                </div>
                <div class="x3-studio-machine-foot"><span>MODULAR INFRASTRUCTURE</span><span>RL / 03</span></div>
            </div>
        </div>
        <div class="x3-studio-coordinate x3-studio-coordinate-bottom"><span>DESIGN MODEL</span><span>X3—03</span></div>
    </div>
    <figcaption class="x3-studio-stage-caption">
        <span>{{ $studioAr ? 'مجسّم توضيحي' : 'Design model' }} <bdi>RL / 03</bdi></span>
        <button type="button" class="x3-depth-motion" data-x3-motion-toggle data-pause="{{ $studioDepth['pause'] }}" data-resume="{{ $studioDepth['resume'] }}" aria-pressed="false" hidden>{{ $studioDepth['pause'] }}</button>
    </figcaption>
</figure>
