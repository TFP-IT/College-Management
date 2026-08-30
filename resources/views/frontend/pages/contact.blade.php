@extends('frontend.layouts.app')

@section('title', 'Contact Us | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>Get in touch with us for any inquiries or information.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
                
                <div>
                    <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">Our Address</h2>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.1); color: var(--navy); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0;">Location</h4>
                            <p style="margin: 0; color: var(--gray-dk); line-height: 1.5;">107/2, Dogormura, Radio Colony,<br>Dhaka Aricha Highway, Savar,<br>Bangladesh, 1343</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.1); color: var(--navy); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0;">Phone</h4>
                            <p style="margin: 0;"><a href="tel:+8801309136876" style="color: var(--gray-dk); text-decoration: none;">+880 1309-136876</a></p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.1); color: var(--navy); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 5px 0;">Email</h4>
                            <p style="margin: 0;"><a href="mailto:admin@maestrocrown.edu.bd" style="color: var(--gray-dk); text-decoration: none;">admin@maestrocrown.edu.bd</a></p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">Find Us on Google Maps</h2>
                    
                    <div style="width: 100%; height: 300px; background: var(--gray-lt); border-radius: 8px; overflow: hidden; border: 1px solid var(--gray-lt);">
                        <!-- Embed Google Maps here. Using a placeholder iframe for now -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3648.51410769363!2d90.257007!3d23.871363!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755e9c0b1160a2b%3A0xc3c942cd894e7561!2sMaestro%20Crown%20College!5e0!3m2!1sen!2sbd!4v1700000000000!5m2!1sen!2sbd" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
</div>
@endsection
