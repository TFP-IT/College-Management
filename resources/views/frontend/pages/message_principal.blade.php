@extends('frontend.layouts.app')

@section('title', 'Message of Principal | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Message of Principal</h1>
        <p>A warm welcome from our Principal, Md. Khalilur Rahman.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            
            <div style="display: flex; gap: 40px; align-items: flex-start; flex-wrap: wrap; flex-direction: row-reverse;">
                
                <div style="flex-grow: 1;">
                    <h3 style="margin-top: 0; margin-bottom: 20px;">Welcome to Our Educational Family</h3>
                    <p><strong>Dear All,</strong></p>
                    <p>I am truly humbled to welcome you to our vibrant learning community. As Principal, I see every day as an opportunity to make a difference in the lives of our students and their families.</p>
                    <p>Imagine a place where curiosity is celebrated, where dreams are nurtured, and where every child feels seen and valued. That’s the environment we strive to create here every single day. We believe in education that goes beyond textbooks - it’s about shaping minds, building character, and preparing our students for a future that is both exciting and challenging.</p>
                    <p>At the heart of our mission is a simple yet powerful belief: every student has the potential to change the world. We don’t just teach subjects - we inspire passion, foster creativity, and build confidence. Our dedicated team works tirelessly to create an atmosphere where learning is not just a requirement but a joyful adventure.</p>
                    
                    <h3>For our students:</h3>
                    <p>I want you to know that you are more than just learners. You are innovators, dreamers, and future leaders. This campus is your space to explore, question, and grow. We are here to support you, challenge you, and celebrate your successes - big and small.</p>

                    <h3>To our parents:</h3>
                    <p>Thank you for entrusting us with the most precious gift - your children. We understand the hopes and dreams you have for them, and we share those aspirations. Together, we can create an environment where your child can flourish academically, socially, and personally.</p>
                    
                    <p>What truly defines us is our commitment to integrity, innovation, and inclusivity. We don’t just talk about these values - we live them every day. From the classroom to the playground, we create opportunities for our students to learn, grow, and become the best versions of themselves.</p>
                    
                    <p>I invite all of you to become an active part of our community. Let’s work together to create a future where every student can achieve their dreams. Together, we can build something extraordinary.</p>
                    
                    <div style="margin-top: 40px;">
                        <h3 style="margin-bottom: 5px;">Md. Khalilur Rahman</h3>
                        <p style="color: var(--gold); font-weight: 600;">Principal, Maestro Crown College</p>
                        <p style="font-style: italic; color: var(--gray-dk);">"Together, we can create a brighter tomorrow."</p>
                    </div>
                </div>
                
                <div style="flex-shrink: 0; width: 300px; padding: 24px; background: var(--off-white); border-radius: 12px; border: 1px solid var(--gray-lt); text-align: center;">
                    <div style="width: 120px; height: 120px; background: var(--navy); color: var(--gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 20px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <h3 style="margin-top: 0; font-size: 1.2rem;">Md. Khalilur Rahman</h3>
                    <p style="color: var(--gray-dk); font-size: 0.9rem; margin-bottom: 0;">Principal</p>
                </div>
                
            </div>
            
        </div>
    </div>
</div>
@endsection
