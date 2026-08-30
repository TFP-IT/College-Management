@extends('frontend.layouts.app')

@section('title', 'Programs | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Programs</h1>
        <p>Everything you need to know about our curriculum, schedules, and academic guidelines to succeed in your journey.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        
        <div style="margin-bottom: 50px;">
            <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">Our Academic Groups</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                
                <div class="content-card" style="text-align: center; padding: 40px 20px;">
                    <div style="width: 80px; height: 80px; background: rgba(201, 162, 39, 0.1); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                    <h3>Science</h3>
                    <p>Dive into the universe of physics, chemistry, biology, and math.</p>
                </div>
                
                <div class="content-card" style="text-align: center; padding: 40px 20px;">
                    <div style="width: 80px; height: 80px; background: rgba(201, 162, 39, 0.1); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <h3>Business Studies</h3>
                    <p>Master the skills of finance, accounting, and modern management.</p>
                </div>
                
                <div class="content-card" style="text-align: center; padding: 40px 20px;">
                    <div style="width: 80px; height: 80px; background: rgba(201, 162, 39, 0.1); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-book-open-reader"></i>
                    </div>
                    <h3>Humanities</h3>
                    <p>Discover the world of art, culture, economics, and society.</p>
                </div>
                
            </div>
        </div>

        <div class="content-card">
            <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">College Rules & Guidelines</h2>
            <p style="margin-bottom: 30px;">Please read and follow the mandatory code of conduct to maintain discipline and harmony in the campus.</p>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <div style="display: flex; gap: 20px;">
                    <div style="font-size: 24px; color: var(--gold); flex-shrink: 0;"><i class="fa-solid fa-clipboard-user"></i></div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;">1. Mandatory Attendance</h4>
                        <p style="margin: 0; color: var(--gray-dk);">According to the Education Board rules, every student must maintain a minimum of 75% attendance in all subjects. Failing to do so will disqualify the student from appearing in the final Board Examinations.</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div style="font-size: 24px; color: var(--gold); flex-shrink: 0;"><i class="fa-solid fa-id-card"></i></div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;">2. Uniform & ID Card</h4>
                        <p style="margin: 0; color: var(--gray-dk);">Students must enter the college premises wearing the prescribed, neat, and clean uniform. Wearing the valid College ID Card around the neck is strictly mandatory at all times inside the campus.</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div style="font-size: 24px; color: var(--gold); flex-shrink: 0;"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;">3. Restriction on Mobile Phones</h4>
                        <p style="margin: 0; color: var(--gray-dk);">The use of mobile phones, smartwatches, or any electronic gadgets is strictly prohibited inside the classrooms, labs, and examination halls. Confiscated devices will only be returned to guardians.</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div style="font-size: 24px; color: var(--gold); flex-shrink: 0;"><i class="fa-solid fa-file-pen"></i></div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;">4. Examination Policies</h4>
                        <p style="margin: 0; color: var(--gray-dk);">Participation in all class tests, term exams, and test examinations is compulsory. Adoption of unfair means (copying/cheating) in any exam will lead to immediate expulsion and severe disciplinary action.</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div style="font-size: 24px; color: var(--gold); flex-shrink: 0;"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <h4 style="margin: 0 0 5px 0;">5. Discipline & Anti-Ragging</h4>
                        <p style="margin: 0; color: var(--gray-dk);">The college has a zero-tolerance policy against ragging, bullying, political activities, or any form of harassment. Smoking or carrying tobacco products within 100 yards of the campus is completely banned.</p>
                    </div>
                </div>

            </div>
        </div>
        
    </div>
</div>
@endsection
