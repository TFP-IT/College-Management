@php $school = $mc['school']; $college = $mc['college']; @endphp
<section class="paths section-pad" id="admission" aria-labelledby="paths-title">
    <div class="container">
        <header class="sec-head sec-head--center" data-reveal>
            <span class="eyebrow">Admission {{ $mc['session'] }}</span>
            <h2 class="sec-title" id="paths-title">Choose your <em>path</em></h2>
            <p class="sec-sub">One institution, two sections. See how admission works for your child, or for you.</p>
        </header>

        <div class="paths-tabs" role="tablist" aria-label="Admission section" data-tabs data-reveal>
            <span class="paths-pill" data-tabs-pill aria-hidden="true"></span>
            <button role="tab" id="tab-school" aria-controls="panel-school" aria-selected="true" class="is-active" data-tab="school">
                <i class="fa-solid fa-shapes"></i> School <small>Play Group – Class 10</small>
            </button>
            <button role="tab" id="tab-college" aria-controls="panel-college" aria-selected="false" tabindex="-1" data-tab="college">
                <i class="fa-solid fa-graduation-cap"></i> College <small>HSC · Class XI</small>
            </button>
        </div>

        {{-- School panel --}}
        <div class="paths-panel is-active" role="tabpanel" id="panel-school" aria-labelledby="tab-school" data-panel="school">
            <div class="paths-intro">
                <div>
                    <span class="eyebrow eyebrow--plain">{{ $school['eyebrow'] }}</span>
                    <h3 class="paths-title">{{ $school['title'] }}</h3>
                    <p class="paths-body">{{ $school['body'] }}</p>
                </div>
                <a href="{{ $schoolHref }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg" data-magnetic><i class="fa-brands fa-google-play"></i> Apply in the app</a>
            </div>
            <div class="stage-grid">
                @foreach ($school['stages'] as $i => $st)
                    <article class="stage-card" data-panel-item>
                        <span class="stage-num">0{{ $i + 1 }}</span>
                        <span class="stage-icon"><i class="fa-solid {{ $st['icon'] }}"></i></span>
                        <h4>{{ $st['title'] }}</h4>
                        <p class="stage-classes">{{ $st['classes'] }}</p>
                        <p>{{ $st['desc'] }}</p>
                    </article>
                @endforeach
            </div>
            <div class="paths-steps" data-panel-item>
                <span class="paths-steps-label">How to apply</span>
                @foreach ($school['steps'] as $i => $step)
                    <div class="paths-step">
                        <span class="paths-step-n">{{ $i + 1 }}</span>
                        <div><strong>{{ $step['title'] }}</strong><span>{{ $step['desc'] }}</span></div>
                    </div>
                @endforeach
                <a href="{{ $contact['phone_href'] }}" class="paths-help"><i class="fa-solid fa-headset"></i><span><small>Need help choosing a class?</small><strong>{{ $contact['phone'] }}</strong></span></a>
            </div>
        </div>

        {{-- College panel --}}
        <div class="paths-panel" role="tabpanel" id="panel-college" aria-labelledby="tab-college" data-panel="college" hidden>
            <div class="paths-intro">
                <div>
                    <span class="eyebrow eyebrow--plain">{{ $college['eyebrow'] }}</span>
                    <h3 class="paths-title">{{ $college['title'] }}</h3>
                    <p class="paths-body">{{ $college['body'] }}</p>
                </div>
                <a href="{{ $collegeHref }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg" data-magnetic><i class="fa-brands fa-google-play"></i> Apply in the app</a>
            </div>
            <div class="stage-grid">
                @foreach ($college['groups'] as $g)
                    <article class="stage-card group-card" data-panel-item>
                        <span class="stage-icon"><i class="fa-solid {{ $g['icon'] }}"></i></span>
                        <h4>{{ $g['title'] }}</h4>
                        <p>{{ $g['desc'] }}</p>
                        <ul class="chips">
                            @foreach ($g['subjects'] as $sub)<li>{{ $sub }}</li>@endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
            <div class="college-facts" data-panel-item>
                <div class="college-fact">
                    <span class="paths-steps-label">Eligibility</span>
                    <ul class="ticks">
                        @foreach ($college['eligibility'] as $e)<li><i class="fa-solid fa-circle-check"></i>{{ $e }}</li>@endforeach
                    </ul>
                </div>
                <div class="college-fact">
                    <span class="paths-steps-label">How to apply</span>
                    <ul class="ticks">
                        @foreach ($college['steps'] as $st)<li><i class="fa-solid fa-circle-check"></i>{{ $st }}</li>@endforeach
                    </ul>
                    <p class="fee-note"><i class="fa-solid fa-award"></i> {{ $college['scholarship'] }}</p>
                </div>
            </div>
        </div>
    </div>
</section>
