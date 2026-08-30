@extends('frontend.layouts.app')

@section('title', 'Admission | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Admission</h1>
        <p>Join the Maestro Crown College family. Find all the details for the admission process below.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">Required Documents & Qualifications</h2>
            <p style="margin-bottom: 30px;">Please ensure you have the following documents and qualifications ready before applying for admission at Maestro Crown College.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                
                <div style="background: rgba(79, 70, 229, 0.05); padding: 25px; border-radius: 8px; border-top: 4px solid var(--navy);">
                    <h3 style="display: flex; align-items: center; gap: 10px; margin-top: 0;"><i class="fa-solid fa-file-contract" style="color: var(--navy);"></i> Academic Documents</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> Original SSC Mark Sheet / Transcript</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> 2 Photocopies of SSC Mark Sheet</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> Original Testimonial from Headmaster</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> SSC Admit Card & Registration Card (Photocopy)</li>
                    </ul>
                </div>
                
                <div style="background: rgba(236, 72, 153, 0.05); padding: 25px; border-radius: 8px; border-top: 4px solid var(--gold);">
                    <h3 style="display: flex; align-items: center; gap: 10px; margin-top: 0;"><i class="fa-solid fa-address-card" style="color: var(--gold);"></i> Photos & ID</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> 4 Copies Passport Size Photo (Student)</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> 2 Copies Stamp Size Photo (Student)</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> 2 Copies Passport Size Photo (Parents/Guardian)</li>
                        <li style="margin-bottom: 10px; display: flex; gap: 10px;"><i class="fa-solid fa-check" style="color: #10b981; margin-top: 4px;"></i> Birth Certificate</li>
                    </ul>
                </div>
                
            </div>
            
            <div style="background: var(--off-white); border: 1px solid var(--gray-lt); padding: 25px; border-radius: 8px; margin-top: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h3 style="margin-top: 0; color: var(--navy);"><i class="fa-solid fa-graduation-cap"></i> Eligibility</h3>
                    <p style="margin: 0; color: var(--gray-dk);">* Admission depends on seat availability and interview performance.</p>
                </div>
                
                <a href="{{ route('frontend.contact') }}" class="btn btn-primary" style="font-size: 1.1rem; padding: 12px 25px; display: inline-flex; align-items: center; gap: 10px;">
                    Apply Online Now <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            
        </div>
    </div>
</div>
@endsection
