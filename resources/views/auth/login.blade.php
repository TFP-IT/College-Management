@extends('layouts.signin_page')

@section('content')

<style type="text/css">
/* Root Variables for Modern Theme */
:root {
    --primary: #4F46E5;
    --primary-hover: #4338CA;
    --bg-gradient-1: #0f172a;
    --bg-gradient-2: #1e1b4b;
    --glass-bg: rgba(255, 255, 255, 0.05);
    --glass-border: rgba(255, 255, 255, 0.1);
    --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
}

/* Base Body Styles for Login */
body {
    margin: 0;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: linear-gradient(135deg, var(--bg-gradient-1) 0%, var(--bg-gradient-2) 100%);
    overflow: hidden;
    color: var(--text-main);
    height: 100vh;
}

/* 3D Animated Background Elements */
.bg-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    overflow: hidden;
    z-index: -1;
    perspective: 1000px;
}

.shape {
    position: absolute;
    filter: blur(50px);
    border-radius: 50%;
    animation: float 20s infinite ease-in-out alternate;
    opacity: 0.6;
}

.shape:nth-child(1) {
    width: 400px;
    height: 400px;
    background: #4F46E5;
    top: -10%;
    left: -10%;
    animation-delay: 0s;
}

.shape:nth-child(2) {
    width: 300px;
    height: 300px;
    background: #ec4899;
    bottom: -10%;
    right: -5%;
    animation-delay: -5s;
    animation-duration: 25s;
}

.shape:nth-child(3) {
    width: 350px;
    height: 350px;
    background: #06b6d4;
    top: 40%;
    left: 50%;
    transform: translateX(-50%);
    animation-delay: -10s;
    animation-duration: 22s;
}

@keyframes float {
    0% { transform: translate(0, 0) scale(1) rotate3d(1, 1, 1, 0deg); }
    50% { transform: translate(100px, 50px) scale(1.2) rotate3d(1, 1, 1, 180deg); }
    100% { transform: translate(-50px, 100px) scale(0.9) rotate3d(1, 1, 1, 360deg); }
}

/* Floating College Icons for 3D feel */
.floating-item {
    position: absolute;
    color: rgba(255, 255, 255, 0.15);
    font-size: 3rem;
    animation: item-float 20s infinite linear;
    z-index: 0;
    filter: drop-shadow(0 4px 15px rgba(0,0,0,0.2));
}

.floating-item:nth-child(4) { top: 15%; left: 10%; font-size: 4rem; animation-duration: 25s; }
.floating-item:nth-child(5) { bottom: 20%; right: 10%; font-size: 5rem; animation-duration: 30s; animation-direction: reverse; }
.floating-item:nth-child(6) { top: 70%; left: 85%; font-size: 3.5rem; animation-duration: 22s; }
.floating-item:nth-child(7) { top: 25%; right: 15%; font-size: 2.5rem; animation-duration: 18s; animation-direction: alternate; }
.floating-item:nth-child(8) { bottom: 10%; left: 25%; font-size: 4.5rem; animation-duration: 28s; }

@keyframes item-float {
    0% { transform: rotateX(0) rotateY(0) rotateZ(0) translateY(0); }
    50% { transform: rotateX(180deg) rotateY(180deg) rotateZ(180deg) translateY(-50px); }
    100% { transform: rotateX(360deg) rotateY(360deg) rotateZ(360deg) translateY(0); }
}

/* Glassmorphism Login Card with 3D effects */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    width: 100%;
    padding: 20px;
    perspective: 1000px;
}

.glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    padding: 3rem;
    width: 100%;
    max-width: 450px;
    box-shadow: var(--glass-shadow);
    transform-style: preserve-3d;
    transition: transform 0.1s ease-out;
    z-index: 10;
}

/* Card inner elements translation for 3D depth */
.glass-card > * {
    transform: translateZ(30px);
}

.form-logo {
    text-align: center;
    margin-bottom: 2.5rem;
    transform: translateZ(50px);
}

.form-logo img {
    height: 60px;
    margin-bottom: 1rem;
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
    border-radius: 12px;
}

.form-logo h1 {
    font-size: 26px;
    font-weight: 700;
    background: linear-gradient(to right, #ffffff, #a5b4fc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
    letter-spacing: -0.5px;
}

.form-group {
    margin-bottom: 1.5rem;
    transform: translateZ(40px);
    text-align: left;
}

.form-label {
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--text-main);
    margin-bottom: 0.6rem;
    display: block;
}

.input-group {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.input-group:focus-within {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
    transform: scale(1.02);
}

.input-group-text {
    background: transparent;
    border: none;
    color: var(--text-muted);
    padding: 0.8rem 1rem;
    font-size: 1.1rem;
}

.form-control {
    background: transparent !important;
    border: none;
    color: white !important;
    padding: 0.8rem 1rem 0.8rem 0;
    box-shadow: none !important;
    width: 100%;
}

.form-control::placeholder {
    color: var(--text-muted);
}

.form-control:focus {
    background: transparent;
    color: white;
    outline: none;
}

/* Fix autofill styling in webkit */
input:-webkit-autofill,
input:-webkit-autofill:hover, 
input:-webkit-autofill:focus, 
input:-webkit-autofill:active{
    -webkit-box-shadow: 0 0 0 30px #1e1b4b inset !important;
    -webkit-text-fill-color: white !important;
    transition: background-color 5000s ease-in-out 0s;
}

.options-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    margin-bottom: 2rem;
    transform: translateZ(30px);
}

.options-row a {
    color: #a5b4fc;
    text-decoration: none;
    transition: color 0.3s ease;
    font-weight: 500;
}

.options-row a:hover {
    color: #ffffff;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
    border: none;
    border-radius: 12px;
    padding: 1rem;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    transform: translateZ(60px);
    box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.5);
    width: 100%;
    cursor: pointer;
}

.btn-primary:hover {
    transform: translateZ(70px) scale(1.05);
    box-shadow: 0 20px 35px -5px rgba(79, 70, 229, 0.6);
}

.btn-primary:active {
    transform: translateZ(40px) scale(0.98);
}

@media (max-width: 768px) {
    .glass-card {
        padding: 2rem;
        width: 90%;
        max-width: none;
    }
    .form-logo h1 {
        font-size: 22px;
    }
    .floating-item {
        opacity: 0.5; /* less distracting on mobile */
    }
    .floating-item:nth-child(4) { top: 5%; left: 5%; font-size: 3rem; }
    .floating-item:nth-child(5) { bottom: 5%; right: 5%; font-size: 3rem; }
    .floating-item:nth-child(6),
    .floating-item:nth-child(7),
    .floating-item:nth-child(8) { display: none; } /* hide some items on mobile to save space */
}
</style>

<!-- 3D Background Objects -->
<div class="bg-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
    <i class="bi bi-book floating-item"></i>
    <i class="bi bi-pen floating-item"></i>
    <i class="bi bi-mortarboard floating-item"></i>
    <i class="bi bi-calculator floating-item"></i>
    <i class="bi bi-journal-text floating-item"></i>
</div>

<div class="login-container">
    <div class="glass-card" id="tilt-card">
        <div class="form-logo">
            <img src="{{ asset('assets/images/blue_gradient_logo.png') }}" alt="logo">
            <h1>{{ get_settings('system_name') }}</h1>
        </div>
        
        <form method="post" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">{{ get_phrase('Email') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Your email address" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">{{ get_phrase('Password') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Input your password" required>
                </div>
            </div>
            
            <div class="options-row">
                <a href="{{ route('password.request') }}">{{ get_phrase('Forgot password?') }}</a>
                <a href="{{ get_settings('help_link') }}" target="_blank">{{ get_phrase('Help') }}</a>
            </div>
            
            <button type="submit" class="btn-primary">{{ get_phrase('Login') }}</button>
        </form>
    </div>
</div>

<!-- VanillaTilt JS for the 3D mouse tracking effect -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Only initialize tilt on non-touch devices for better performance and usability
        if (window.matchMedia("(pointer: fine)").matches) {
            VanillaTilt.init(document.querySelector("#tilt-card"), {
                max: 12,
                speed: 400,
                glare: true,
                "max-glare": 0.2,
                perspective: 1000,
                scale: 1.02
            });
        }
    });
</script>

@endsection
