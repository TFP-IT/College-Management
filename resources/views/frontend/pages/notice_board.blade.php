@extends('frontend.layouts.app')

@section('title', 'Notice Board | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Notice Board</h1>
        <p>Latest announcements, updates, and news from Maestro Crown College.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--gold); padding-bottom: 15px; margin-bottom: 25px;">
                <h2 style="color: var(--navy); margin: 0;">Latest Notices</h2>
                <span style="background: rgba(201, 162, 39, 0.1); color: var(--gold); padding: 5px 15px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">
                    Updated Recently
                </span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <div style="border: 1px solid var(--gray-lt); border-radius: 8px; padding: 20px; display: flex; gap: 20px; transition: box-shadow 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)'" onmouseout="this.style.boxShadow='none'">
                    <div style="flex-shrink: 0; width: 70px; height: 70px; background: var(--navy); color: white; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 1.5rem; font-weight: 700; line-height: 1;">15</span>
                        <span style="font-size: 0.9rem; text-transform: uppercase;">Mar</span>
                    </div>
                    <div style="flex-grow: 1;">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.2rem;">
                            <a href="#" style="color: var(--navy); text-decoration: none; transition: color 0.3s ease;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--navy)'">Routine of Test Examination - 2026: Class XII students</a>
                        </h3>
                        <p style="margin: 0 0 15px 0; color: var(--gray-dk);">The routine for the upcoming Test Examination for Class XII students has been published. All students are advised to download the routine and prepare accordingly.</p>
                        <a href="#" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem; display: inline-block;">View Details</a>
                    </div>
                </div>

                <div style="border: 1px solid var(--gray-lt); border-radius: 8px; padding: 20px; display: flex; gap: 20px; transition: box-shadow 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)'" onmouseout="this.style.boxShadow='none'">
                    <div style="flex-shrink: 0; width: 70px; height: 70px; background: var(--gray-lt); color: var(--navy); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <span style="font-size: 1.5rem; font-weight: 700; line-height: 1;">10</span>
                        <span style="font-size: 0.9rem; text-transform: uppercase;">Mar</span>
                    </div>
                    <div style="flex-grow: 1;">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.2rem;">
                            <a href="#" style="color: var(--navy); text-decoration: none; transition: color 0.3s ease;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--navy)'">HSC Admission 2026 is now open</a>
                        </h3>
                        <p style="margin: 0 0 15px 0; color: var(--gray-dk);">Admission for the HSC batch of 2026 is currently ongoing. Interested candidates are requested to apply through the online portal or visit the college campus.</p>
                        <a href="{{ route('frontend.admission') }}" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem; display: inline-block;">Apply Now</a>
                    </div>
                </div>

            </div>
            
        </div>
    </div>
</div>
@endsection
