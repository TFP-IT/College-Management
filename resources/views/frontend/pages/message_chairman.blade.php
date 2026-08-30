@extends('frontend.layouts.app')

@section('title', 'Message of Chairman | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Message of Chairman</h1>
        <p>A message from our Founder and Chairman, Dr. Kamrul Ahsan.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            
            <div style="display: flex; gap: 40px; align-items: flex-start; flex-wrap: wrap;">
                
                <div style="flex-grow: 1;">
                    <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text);">
                        <i class="fa-solid fa-quote-left" style="color: var(--gold); font-size: 1.5rem; margin-right: 10px;"></i>
                        From an early age, while many aspired to become doctors, engineers, or business leaders, my ambition was different. I wanted to become a teacher. Shaping minds and guiding young people is one of the most powerful ways to build a nation.
                    </p>
                    
                    <p>It is from this conviction that Maestro Crown College was established. The word “Maestro” represents mastery and excellence. The word “Crown” symbolises honour and distinction. Our vision is simple yet profound: teachers who possess true expertise in their subjects earn the highest respect and serve as guiding lights in their students' lives.</p>
                    
                    <p>At Maestro Crown College, we have assembled a dynamic team of talented, dedicated educators, many of whom graduated from leading public universities. Beyond academic instruction, our focus extends to character formation, ethical values, discipline, and responsible citizenship. Education must shape both intellect and integrity.</p>
                    
                    <p>We are committed to providing a modern learning environment supported by strong infrastructure and contemporary facilities. At the same time, we maintain a firm stance on academic integrity. We believe education must be transparent, disciplined, and free from corruption. Our institution stands against practices that undermine merit and fairness, and we remain unwavering in our commitment to discipline and accountability.</p>
                    
                    <p>Maestro Crown College continues its journey with determination and purpose, striving to prepare students who are confident, principled, and capable of contributing meaningfully to society and the nation.</p>
                    
                    <div style="margin-top: 40px;">
                        <h3 style="margin-bottom: 5px;">Dr. Kamrul Ahsan</h3>
                        <p style="color: var(--gold); font-weight: 600;">Chairman and Founder<br>Maestro Crown College</p>
                    </div>
                </div>
                
                <div style="flex-shrink: 0; width: 300px; padding: 24px; background: var(--off-white); border-radius: 12px; border: 1px solid var(--gray-lt); text-align: center;">
                    <div style="width: 120px; height: 120px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h3 style="margin-top: 0; font-size: 1.2rem;">Dr. Kamrul Ahsan</h3>
                    <p style="color: var(--gray-dk); font-size: 0.9rem; margin-bottom: 0;">Founder of Kamrul Group and Maestro Crown College</p>
                </div>
                
            </div>
            
        </div>
    </div>
</div>
@endsection
