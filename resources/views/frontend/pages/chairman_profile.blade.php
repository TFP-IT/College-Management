@extends('frontend.layouts.app')

@section('title', 'Chairman\'s Profile | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Chairman's Profile</h1>
        <p>Learn more about the visionary behind Maestro Crown College.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card" style="display: flex; flex-direction: column; gap: 30px;">
            
            <div style="display: flex; gap: 30px; align-items: flex-start; flex-wrap: wrap;">
                <div style="flex-shrink: 0; width: 200px; height: 250px; background: var(--off-white); border-radius: 12px; border: 1px solid var(--gray-lt); display: flex; align-items: center; justify-content: center; font-size: 80px; color: var(--navy);">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div style="flex-grow: 1;">
                    <h2 style="margin-bottom: 5px;">Dr. Kamrul Ahsan</h2>
                    <p style="color: var(--gold); font-weight: 600; font-size: 1.1rem; margin-bottom: 20px;">Founder and Chairman, Kamrul Group</p>
                    
                    <p>Dr. Kamrul Ahsan is the Founder and Chairman of Kamrul Group, established in 2011. What began as a modest initiative has grown into a diversified, forward-looking business conglomerate, driven by a clear mission: to serve people, contribute to national development, and support Bangladesh's economic self-reliance.</p>
                </div>
            </div>

            <div>
                <p>Under his leadership, Kamrul Group has expanded strategically across multiple sectors, building a strong presence in key industries. The Group includes enterprises in agro and food production, education, financial technology, digital services, media, software development, and international payment solutions.</p>
                
                <p><strong>His portfolio includes:</strong> Kamrul Agro Food Industries Ltd, Mecca Cola Bangladesh, Malaysia University of Science and Technology Bangladesh Campus, Swiftlink Payment Services L.L.C, Maestro Soft Ltd, TFP Solutions Bangladesh Ltd, Maestro Crown College, Khobor71, Wow Play, Mulamuli.com.bd, Maestro Pay SDN BHD, Worker Welfare Watch SDN BHD, GG Consortium Bangladesh Ltd, Tap and Pay WLL Bahrain, Tap N Pay UK Ltd, and Dignite International Bangladesh Chapter.</p>
                
                <p>Dr. Ahsan has consistently focused on strategic diversification, quality assurance, operational efficiency, and responsible corporate governance. His leadership philosophy emphasises long-term planning, disciplined execution, customer-centric innovation, and effective human capital development.</p>
                
                <p>Through sustained expansion and adaptation to global economic dynamics, Kamrul Group continues to position itself as a competitive and responsible corporate entity. The Group remains committed to ethical business practices, social responsibility, and meaningful contributions to the national economy.</p>

                <div style="background: rgba(201, 162, 39, 0.1); border-left: 4px solid var(--gold); padding: 20px; border-radius: 4px; margin-top: 30px;">
                    <p style="margin: 0; font-style: italic; color: var(--navy); font-size: 1.05rem;">
                        <strong>Vision:</strong> "To build resilient institutions that create value for stakeholders, strengthen local industry, and contribute to sustainable national progress."
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection
