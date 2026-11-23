@php $galInitial = $mc['gallery']['initial']; @endphp
<section class="gallery section-pad" id="gallery" aria-labelledby="gallery-title" data-gallery data-initial="{{ $galInitial }}">
    <div class="container">
        <header class="sec-head sec-head--split" data-reveal>
            <div>
                <span class="eyebrow">Life at MCC</span>
                <h2 class="sec-title" id="gallery-title">Moments that make <em>memories</em></h2>
            </div>
            <p class="sec-sub">Classrooms, sports, festivals and friendships. A look at everyday life on our campus.</p>
        </header>
        @include('frontend.partials.gallery-grid', ['gallery' => $gallery, 'initial' => $galInitial, 'fullLink' => true])
    </div>
</section>
