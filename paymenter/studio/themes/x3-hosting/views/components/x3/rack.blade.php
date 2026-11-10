@php $depth = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/depth.php'); @endphp
<div class="x3-depth-stage">
    <div class="x3-depth-scene" data-x3-depth="rack" aria-hidden="true" dir="ltr">
        <div class="x3-depth-halo"></div><div class="x3-depth-word">X3</div>
        <div class="x3-depth-floor"></div><div class="x3-depth-shadow"></div>
        <div class="x3-depth-rack">
            <div class="x3-depth-face x3-depth-back"></div>
            <div class="x3-depth-face x3-depth-side"><span>X3</span><small>RACKLINE</small><i></i><i></i><i></i></div>
            <div class="x3-depth-face x3-depth-top"><span>ENGINEERED / X3</span></div>
            <div class="x3-depth-face x3-depth-front">
                <div class="x3-depth-rack-header"><span>X3<span class="x3-depth-slash"> / </span>RACKLINE</span><span>RL—03</span></div>
                <div class="x3-depth-rack-rails"><i></i><i></i></div>
                <div class="x3-depth-nodes">
                    @foreach(['01','02','03'] as $depthBay)
                    <div class="x3-depth-node"><span class="x3-depth-node-id">{{ $depthBay }}</span><div class="x3-depth-grille"></div><div class="x3-depth-node-handle"></div><div class="x3-depth-node-led"><i></i><i></i></div><span class="x3-depth-node-line"></span></div>
                    @endforeach
                </div>
                <div class="x3-depth-rack-footer"><x-logo class="x3-depth-rack-logo" /><span>COMPUTE.<br>CONNECT. CREATE.</span><span class="x3-depth-power">⏻</span></div>
            </div>
        </div>
        <span class="x3-depth-coordinate">X3 / 01.03</span><span class="x3-depth-edition">{{ $depth['edition'] }}</span>
    </div>
    <div class="x3-depth-caption"><span>{{ $depth['detail'] }} <small>{{ $depth['illustration'] }}</small></span><button type="button" class="x3-depth-motion" data-x3-motion-toggle data-pause="{{ $depth['pause'] }}" data-resume="{{ $depth['resume'] }}" aria-pressed="false" hidden>{{ $depth['pause'] }}</button></div>
</div>
