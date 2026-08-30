@extends('frontend.layouts.app')

@section('title', 'Governing Body | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Governing Body</h1>
        <p>The distinguished leadership guiding Maestro Crown College.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            <h2 style="text-align: center; margin-bottom: 40px;">Members of the Governing Body</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                
                <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 1.1rem;">Dr. Kamrul Ahsan</h3>
                    <p style="color: var(--gold); font-weight: 600; margin-bottom: 0; font-size: .9rem;">Chairman & Founder</p>
                </div>

                <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 1.1rem;">Dr. Md Saiful Kabir</h3>
                    <p style="color: var(--gray-dk); font-weight: 600; margin-bottom: 0; font-size: .9rem;">Member</p>
                </div>

                <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 1.1rem;">Mrs. Najnin Sultana Ruma</h3>
                    <p style="color: var(--gray-dk); font-weight: 600; margin-bottom: 0; font-size: .9rem;">Member</p>
                </div>

                <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 1.1rem;">Mrs. Dil Afroz</h3>
                    <p style="color: var(--gray-dk); font-weight: 600; margin-bottom: 0; font-size: .9rem;">Donor Member</p>
                </div>

                <div style="background: var(--off-white); border: 1px solid var(--gray-lt); border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <h3 style="margin-top: 0; margin-bottom: 4px; font-size: 1.1rem;">Md Khalilur Rahman</h3>
                    <p style="color: var(--gray-dk); font-weight: 600; margin-bottom: 0; font-size: .9rem;">Member Secretary / Principal</p>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
