<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kardafrica - Cartes numériques en un clic !')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <style>
        /* Couleurs inspirées du logo */
        .bg-kardafrica-primary { 
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%); 
        }
        .bg-kardafrica-secondary { background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%); }
        .bg-kardafrica-accent { background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 50%, #FF6B6B 100%); }
        .text-kardafrica-primary { color: #44A08D; }
        .text-kardafrica-secondary { color: #FF6B6B; }
        .border-kardafrica-primary { border-color: #44A08D; }
        .hover-kardafrica { transition: all 0.3s ease; }
        .hover-kardafrica:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        
        /* Top Bar Styles */
        .top-bar {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .top-bar a:hover {
            color: #4ECDC4;
            transform: translateY(-1px);
        }
        
        .top-bar .social-link {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .top-bar .social-link:hover {
            background: rgba(78, 205, 196, 0.2);
            transform: translateY(-2px) scale(1.1);
        }
        
        /* Enhanced Navbar Styles */
        .navbar-glass {
            background: rgba(78, 205, 196, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-glass::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            opacity: 0.9;
            z-index: -1;
        }
        
        /* Dropdown Menu Styles */
        .group:hover .group-hover\:opacity-100 {
            opacity: 1;
        }
        
        .group:hover .group-hover\:visible {
            visibility: visible;
        }
        
        .group:hover .group-hover\:translate-y-0 {
            transform: translateY(0);
        }
        
        .group:hover .group-hover\:rotate-180 {
            transform: rotate(180deg);
        }
        
        /* Navbar hover effects */
        .navbar-glass a:hover,
        .navbar-glass button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            transform: translateY(-1px);
        }
        
        /* Cart notification animation */
        #cartCount {
            animation: bounceIn 0.3s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        /* Animation du logo */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-animation { animation: float 3s ease-in-out infinite; }
        
        /* Loader animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        .loader-spin { animation: spin 2s linear infinite; }
        .loader-pulse { animation: pulse 2s ease-in-out infinite; }
        .loader-fadeout { animation: fadeOut 0.5s ease-out forwards; }
        
        /* Loader styles */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 50%, #FF6B6B 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .loader-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .loader-logo img {
            width: 80px;
            height: 80px;
        }
        
        /* Styles pour les cartes */
        .card-gradient { background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%); }
        .card-shadow { box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        
        /* Advanced Carousel Styles */
        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .carousel-slide.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .carousel-slide.prev {
            transform: translateX(-100%);
        }
        
        .carousel-slide.next {
            transform: translateX(100%);
        }
        
        /* Parallax Background Effect */
        .carousel-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            transform: scale(1.1);
            transition: transform 0.8s ease;
        }
        
        .carousel-slide.active::before {
            transform: scale(1);
        }
        
        /* Dynamic Background Images */
        .carousel-slide.netflix {
            background-image: url('/assets/banner/Banner-Netflix---Kardafrica.jpg');
        }
        .carousel-slide.spotify {
            background-image: url('/assets/banner/Banner-Spotify---Kardafrica.jpg');
        }
        .carousel-slide.apple {
            background-image: url('/assets/banner/Banner-Apple---Kardafrica.jpg');
        }
        .carousel-slide.uber {
            background-image: url('/assets/banner/Banner-Uber--Kardafrica.jpg');
        }
        
        /* Animated Overlay */
        .carousel-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.7) 100%);
            opacity: 0;
            transition: opacity 0.6s ease;
        }
        
        .carousel-slide.active .carousel-overlay {
            opacity: 1;
        }
        
        /* Animated Content */
        .carousel-content {
            position: relative;
            z-index: 10;
            transform: translateY(50px);
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            transition-delay: 0.3s;
        }
        
        .carousel-slide.active .carousel-content {
            transform: translateY(0);
            opacity: 1;
        }
        
        .carousel-title {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.6s ease;
            transition-delay: 0.5s;
        }
        
        .carousel-slide.active .carousel-title {
            transform: translateY(0);
            opacity: 1;
        }
        
        .carousel-subtitle {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.6s ease;
            transition-delay: 0.7s;
        }
        
        .carousel-slide.active .carousel-subtitle {
            transform: translateY(0);
            opacity: 1;
        }
        
        .carousel-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #4ECDC4, #44A08D);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transform: translateY(20px) scale(0.9);
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transition-delay: 0.9s;
            position: relative;
            overflow: hidden;
        }
        
        .carousel-slide.active .carousel-button {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        
        .carousel-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .carousel-button:hover::before {
            left: 100%;
        }
        
        .carousel-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        
        /* Enhanced Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 20;
        }
        
        .carousel-nav:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-nav.prev {
            left: 20px;
        }
        
        .carousel-nav.next {
            right: 20px;
        }
        
        .carousel-nav svg {
            width: 24px;
            height: 24px;
            color: white;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        /* Enhanced Dots */
        .carousel-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 20;
        }
        
        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            border: 2px solid rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .carousel-dot.active {
            background: white;
            transform: scale(1.2);
            box-shadow: 0 0 20px rgba(255,255,255,0.5);
        }
        
        .carousel-dot:hover {
            background: rgba(255,255,255,0.6);
            transform: scale(1.1);
        }
        
        /* Progress Bar */
        .carousel-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #4ECDC4, #44A08D);
            width: 0%;
            transition: width 5s linear;
            z-index: 20;
        }
        
        .carousel-slide.active .carousel-progress {
            width: 100%;
        }
        
        /* Floating Particles Effect */
        .carousel-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 5;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            animation: float-particles 8s linear infinite;
        }
        
        @keyframes float-particles {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
                 /* Responsive Design */
         @media (max-width: 768px) {
             .carousel-title {
                 font-size: 2.5rem;
             }
             
             .carousel-subtitle {
                 font-size: 1.2rem;
             }
             
             .carousel-nav {
                 width: 50px;
                 height: 50px;
             }
             
             .carousel-nav.prev {
                 left: 15px;
             }
             
             .carousel-nav.next {
                 right: 15px;
             }
         }

         /* Section Animations */
         .section-animate {
             opacity: 0;
             transform: translateY(50px);
             transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
         }
         
         .section-animate.animate {
             opacity: 1;
             transform: translateY(0);
         }
         
         .card-hover-effect {
             transition: all 0.3s ease;
             position: relative;
             overflow: hidden;
         }
         
         .card-hover-effect::before {
             content: '';
             position: absolute;
             top: 0;
             left: -100%;
             width: 100%;
             height: 100%;
             background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
             transition: left 0.5s;
         }
         
         .card-hover-effect:hover::before {
             left: 100%;
         }
         
         .card-hover-effect:hover {
             transform: translateY(-8px) scale(1.02);
             box-shadow: 0 20px 40px rgba(0,0,0,0.15);
         }
         
         /* Enhanced Hero Section */
         .hero-text-animate {
             opacity: 0;
             transform: translateY(30px);
             animation: slideInUp 1s ease-out forwards;
         }
         
         .hero-text-animate:nth-child(1) { animation-delay: 0.2s; }
         .hero-text-animate:nth-child(2) { animation-delay: 0.4s; }
         .hero-text-animate:nth-child(3) { animation-delay: 0.6s; }
         .hero-text-animate:nth-child(4) { animation-delay: 0.8s; }
         
         @keyframes slideInUp {
             to {
                 opacity: 1;
                 transform: translateY(0);
             }
         }
         
         /* Brand Grid Animation */
         .brand-grid-item {
             opacity: 0;
             transform: scale(0.8);
             transition: all 0.5s ease;
             position: relative;
             overflow: hidden;
         }
         
         .brand-grid-item.animate {
             opacity: 1;
             transform: scale(1);
         }
         
         .brand-grid-item:hover {
             transform: scale(1.05) translateY(-5px);
             background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
             color: white;
             box-shadow: 0 15px 35px rgba(0,0,0,0.2);
         }
         
         .brand-grid-item .brand-icon {
             transition: all 0.3s ease;
         }
         
         .brand-grid-item:hover .brand-icon {
             transform: scale(1.2) rotate(5deg);
             filter: brightness(1.2);
         }
         
         /* Specific brand colors on hover */
         .brand-grid-item[data-brand="itunes"]:hover {
             background: linear-gradient(135deg, #333 0%, #000 100%);
         }
         
         .brand-grid-item[data-brand="psn"]:hover {
             background: linear-gradient(135deg, #0070f3 0%, #003d82 100%);
         }
         
         .brand-grid-item[data-brand="netflix"]:hover {
             background: linear-gradient(135deg, #e50914 0%, #b20710 100%);
         }
         
         .brand-grid-item[data-brand="google"]:hover {
             background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
         }
         
         .brand-grid-item[data-brand="spotify"]:hover {
             background: linear-gradient(135deg, #1db954 0%, #1ed760 100%);
         }
         
         .brand-grid-item[data-brand="amazon"]:hover {
             background: linear-gradient(135deg, #ff9900 0%, #e68900 100%);
         }
         
         .brand-grid-item[data-brand="xbox"]:hover {
             background: linear-gradient(135deg, #107c10 0%, #0e6b0e 100%);
         }
         
         .brand-grid-item[data-brand="steam"]:hover {
             background: linear-gradient(135deg, #1b2838 0%, #171a21 100%);
         }
         
         .brand-grid-item[data-brand="uber"]:hover {
             background: linear-gradient(135deg, #000 0%, #333 100%);
         }
         
         .brand-grid-item[data-brand="roblox"]:hover {
             background: linear-gradient(135deg, #00a2ff 0%, #0066cc 100%);
         }
         
         /* Panier Dropdown */
         .cart-dropdown {
             position: absolute;
             top: 100%;
             right: 0;
             background: white !important;
             border-radius: 20px;
             box-shadow: 0 25px 50px rgba(0,0,0,0.2);
             width: 350px;
             opacity: 0;
             visibility: hidden;
             transform: translateY(-15px) scale(0.95);
             transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
             z-index: 9999 !important;
             border: 1px solid rgba(78, 205, 196, 0.1);
             backdrop-filter: blur(10px);
             -webkit-backdrop-filter: blur(10px);
         }
         
         .cart-dropdown.show {
             opacity: 1 !important;
             visibility: visible !important;
             transform: translateY(0) scale(1) !important;
             display: block !important;
         }


         
         /* Animation pour les items du panier */
         .cart-item {
             background: white;
             border-radius: 12px;
             border: 1px solid rgba(0,0,0,0.05);
             margin-bottom: 8px;
             transition: all 0.3s ease;
         }
         
         .cart-item:hover {
             box-shadow: 0 4px 20px rgba(0,0,0,0.1);
             transform: translateY(-2px);
         }
         
         /* Chatbot */
         .chatbot-container {
             position: fixed;
             bottom: 20px;
             right: 20px;
             z-index: 1000;
         }
         
                 .chatbot-toggle {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            box-shadow: 0 8px 32px rgba(78, 205, 196, 0.4);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .chatbot-toggle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            border-radius: 50%;
            opacity: 0;
            transition: all 0.3s ease;
        }
         
                 .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(78, 205, 196, 0.6);
        }
        
        .chatbot-toggle:hover::before {
            opacity: 1;
        }
        
        .chatbot-toggle svg {
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .chatbot-toggle:hover svg {
            transform: scale(1.1);
        }
         
         @keyframes pulse {
             0% { box-shadow: 0 8px 20px rgba(0,0,0,0.2), 0 0 0 0 rgba(78, 205, 196, 0.4); }
             70% { box-shadow: 0 8px 20px rgba(0,0,0,0.2), 0 0 0 10px rgba(78, 205, 196, 0); }
             100% { box-shadow: 0 8px 20px rgba(0,0,0,0.2), 0 0 0 0 rgba(78, 205, 196, 0); }
         }
         
         .chatbot-window {
             position: absolute;
             bottom: 70px;
             right: 0;
             width: 350px;
             height: 500px;
             background: white;
             border-radius: 16px;
             box-shadow: 0 20px 40px rgba(0,0,0,0.15);
             opacity: 0;
             visibility: hidden;
             transform: translateY(20px);
             transition: all 0.3s ease;
         }
         
         .chatbot-window.show {
             opacity: 1;
             visibility: visible;
             transform: translateY(0);
         }
         
         /* Enhanced Section Styles */
         .section-title {
             background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
             background-clip: text;
             text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
         }
         
         .section-bg-pattern {
             position: relative;
             overflow: hidden;
         }
         
         .section-bg-pattern::before {
             content: '';
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: 
                 radial-gradient(circle at 20% 50%, rgba(78, 205, 196, 0.1) 0%, transparent 50%),
                 radial-gradient(circle at 80% 20%, rgba(255, 107, 107, 0.1) 0%, transparent 50%),
                 radial-gradient(circle at 40% 80%, rgba(68, 160, 141, 0.1) 0%, transparent 50%);
             pointer-events: none;
         }
         
         /* Modal Styles */
         .modal-overlay {
             position: fixed;
             top: 0;
             left: 0;
             right: 0;
             bottom: 0;
             background: rgba(0, 0, 0, 0.5);
             backdrop-filter: blur(5px);
             z-index: 9999;
             display: flex;
             align-items: center;
             justify-content: center;
             opacity: 0;
             visibility: hidden;
             transition: all 0.3s ease;
         }
         
         .modal-overlay.show {
             opacity: 1;
             visibility: visible;
         }
         
         .modal-content {
             background: white;
             border-radius: 20px;
             max-width: 450px;
             width: 90%;
             max-height: 90vh;
             overflow-y: auto;
             transform: scale(0.9) translateY(20px);
             transition: all 0.3s ease;
             box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
         }
         
         .modal-overlay.show .modal-content {
             transform: scale(1) translateY(0);
         }
         
         .modal-header {
             background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
             color: white;
             padding: 20px;
             border-radius: 20px 20px 0 0;
             text-align: center;
         }
         
         .modal-tabs {
             display: flex;
             background: #f8f9fa;
         }
         
         .modal-tab {
             flex: 1;
             padding: 15px;
             text-align: center;
             cursor: pointer;
             transition: all 0.3s ease;
             border-bottom: 3px solid transparent;
             font-weight: 500;
         }
         
         .modal-tab.active {
             background: white;
             color: #44A08D;
             border-bottom-color: #44A08D;
         }
         
         .modal-tab:hover {
             background: #e9ecef;
         }
         
         .modal-tab.active:hover {
             background: white;
         }
         
         .modal-body {
             padding: 30px;
         }
         
         .tab-content {
             display: none;
         }
         
         .tab-content.active {
             display: block;
         }
         
         .form-group {
             margin-bottom: 20px;
         }
         
         .form-label {
             display: block;
             margin-bottom: 8px;
             font-weight: 500;
             color: #374151;
         }
         
         .form-input {
             width: 100%;
             padding: 12px 16px;
             border: 2px solid #e5e7eb;
             border-radius: 10px;
             font-size: 16px;
             transition: all 0.3s ease;
             background: #fafafa;
         }
         
         .form-input:focus {
             outline: none;
             border-color: #4ECDC4;
             background: white;
             box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
         }
         
         .form-button {
             width: 100%;
             padding: 12px;
             background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
             color: white;
             border: none;
             border-radius: 10px;
             font-size: 16px;
             font-weight: 600;
             cursor: pointer;
             transition: all 0.3s ease;
             text-transform: uppercase;
             letter-spacing: 0.5px;
         }
         
         .form-button:hover {
             transform: translateY(-2px);
             box-shadow: 0 10px 20px rgba(78, 205, 196, 0.3);
         }
         
         .form-button:active {
             transform: translateY(0);
         }
         
         .form-footer {
             text-align: center;
             margin-top: 20px;
             padding-top: 20px;
             border-top: 1px solid #e5e7eb;
         }
         
         .form-link {
             color: #44A08D;
             text-decoration: none;
             font-weight: 500;
         }
         
         .form-link:hover {
             text-decoration: underline;
         }
         
         .close-modal {
             position: absolute;
             top: 15px;
             right: 15px;
             background: rgba(255, 255, 255, 0.2);
             border: none;
             color: white;
             width: 35px;
             height: 35px;
             border-radius: 50%;
             cursor: pointer;
             display: flex;
             align-items: center;
             justify-content: center;
             transition: all 0.3s ease;
         }
         
         .close-modal:hover {
             background: rgba(255, 255, 255, 0.3);
             transform: scale(1.1);
         }

         /* Styles pour le menu mobile et overlay */
         #mobileMenuOverlay {
             background: rgba(0, 0, 0, 0.6) !important;
             backdrop-filter: blur(4px);
             -webkit-backdrop-filter: blur(4px);
             z-index: 999;
         }

         #mobileMenuOverlay.show {
             opacity: 1 !important;
             visibility: visible !important;
         }

         #mobileMenu {
             background: #ffffff !important;
             background-color: #ffffff !important;
             box-shadow: -10px 0 25px rgba(0, 0, 0, 0.15);
             z-index: 1000;
             border-left: 1px solid #e5e7eb;
             opacity: 1 !important;
         }

         #mobileMenu.show {
             transform: translateX(0) !important;
         }

         /* Forcer le fond blanc pour tous les éléments de la sidebar */
         #mobileMenu > * {
             background-color: transparent;
         }

         #mobileMenu .sidebar-content {
             background: #ffffff !important;
             background-color: #ffffff !important;
         }

         /* Header de la sidebar avec dégradé solide */
         #mobileMenu .sidebar-header {
             background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%) !important;
             opacity: 1 !important;
         }

         /* Animation pour les éléments de la sidebar */
         #mobileMenu .sidebar-item {
             opacity: 0;
             transform: translateX(20px);
             transition: all 0.3s ease;
         }

         #mobileMenu.show .sidebar-item {
             opacity: 1;
             transform: translateX(0);
         }

         #mobileMenu.show .sidebar-item:nth-child(1) { transition-delay: 0.1s; }
         #mobileMenu.show .sidebar-item:nth-child(2) { transition-delay: 0.15s; }
         #mobileMenu.show .sidebar-item:nth-child(3) { transition-delay: 0.2s; }
         #mobileMenu.show .sidebar-item:nth-child(4) { transition-delay: 0.25s; }
         #mobileMenu.show .sidebar-item:nth-child(5) { transition-delay: 0.3s; }
         #mobileMenu.show .sidebar-item:nth-child(6) { transition-delay: 0.35s; }

         /* Empêcher le scroll du body quand la sidebar est ouverte */
         body.mobile-menu-open {
             overflow: hidden;
             position: fixed;
             width: 100%;
         }

         /* Styles pour la sidebar panier mobile */
         #mobileCartOverlay {
             background: rgba(0, 0, 0, 0.6) !important;
             backdrop-filter: blur(4px);
             -webkit-backdrop-filter: blur(4px);
             z-index: 999;
         }

         #mobileCartOverlay.show {
             opacity: 1 !important;
             visibility: visible !important;
         }

         #mobileCartSidebar {
             background: #ffffff !important;
             background-color: #ffffff !important;
             box-shadow: -10px 0 25px rgba(0, 0, 0, 0.15);
             z-index: 1000;
             border-left: 1px solid #e5e7eb;
             opacity: 1 !important;
         }

         #mobileCartSidebar.show {
             transform: translateX(0) !important;
         }

         /* Animation pour les éléments de la sidebar panier */
         #mobileCartSidebar .sidebar-item {
             opacity: 0;
             transform: translateX(20px);
             transition: all 0.3s ease;
         }

         #mobileCartSidebar.show .sidebar-item {
             opacity: 1;
             transform: translateX(0);
         }

         #mobileCartSidebar.show .sidebar-item:nth-child(1) { transition-delay: 0.1s; }
         #mobileCartSidebar.show .sidebar-item:nth-child(2) { transition-delay: 0.15s; }
         #mobileCartSidebar.show .sidebar-item:nth-child(3) { transition-delay: 0.2s; }

         /* Empêcher le scroll du body quand la sidebar panier est ouverte */
         body.mobile-cart-open {
             overflow: hidden;
             position: fixed;
             width: 100%;
         }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased" style="padding-top: 120px;">
    <!-- Page Loader -->
    <div id="pageLoader" class="page-loader">
        <div class="loader-content">
            <div class="loader-logo">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" 
                     alt="Kardafrica Logo" 
                     class="loader-pulse">
            </div>
            <h2 class="text-2xl font-bold mb-2">Kardafrica</h2>
            <p id="loaderText" class="text-lg opacity-80">Chargement en cours...</p>
            <div class="mt-6 flex justify-center space-x-2">
                <div class="w-3 h-3 bg-white rounded-full loader-pulse" style="animation-delay: 0s;"></div>
                <div class="w-3 h-3 bg-white rounded-full loader-pulse" style="animation-delay: 0.2s;"></div>
                <div class="w-3 h-3 bg-white rounded-full loader-pulse" style="animation-delay: 0.4s;"></div>
            </div>
        </div>
    </div>
    <!-- Top Bar -->
    <div class="top-bar text-white py-3 fixed top-0 w-full z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center text-sm">
                <!-- Informations de contact -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="mailto:hello@kardafrica.com" class="flex items-center space-x-2 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>hello@kardafrica.com</span>
                    </a>
                    <a href="tel:+221XXXXXXXXX" class="flex items-center space-x-2 transition-all duration-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>+221 XX XXX XX XX</span>
                    </a>
                    <div class="flex items-center space-x-1 text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Service client 24/7</span>
                    </div>
                </div>
                
                <!-- Menu navigation secondaire -->
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:flex items-center space-x-4">
                        <a href="{{ route('about') }}" class="transition-all duration-300 hover:text-kardafrica-primary">À propos</a>
                        <a href="{{ route('contact') }}" class="transition-all duration-300 hover:text-kardafrica-primary">Nous contacter</a>
                        <a href="{{ route('support') }}" class="transition-all duration-300 hover:text-kardafrica-primary">Support</a>
                    </div>
                    
                    <!-- Réseaux sociaux -->
                    <div class="flex items-center space-x-2 ml-4 pl-4 border-l border-gray-600">
                        <a href="#" class="social-link" title="Facebook">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar-glass fixed top-10 w-full z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3">
                        <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" 
                             alt="Kardafrica Logo" 
                             class="h-12 w-12 float-animation">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Kardafrica</h1>
                            <p class="text-sm text-gray-100">Cartes numériques en un clic !</p>
                        </div>
                    </a>
                </div>
                
                <!-- Menu de navigation -->
                <div class="hidden md:flex space-x-2 items-center">
                    <a href="{{ route('home') }}" class="text-white hover:text-gray-200 hover:bg-white/10 px-4 py-3 rounded-xl text-base font-medium transition-all duration-300">
                        🏠 Accueil
                    </a>
                    
                    <!-- Menu déroulant Cartes -->
                    <div class="relative group">
                        <button class="text-white hover:text-gray-200 hover:bg-white/10 px-4 py-3 rounded-xl text-base font-medium transition-all duration-300 flex items-center space-x-2">
                            <span>🎯 Cartes</span>
                            <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div class="absolute left-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 z-50">
                            <div class="py-3">
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">🎮</span>
                                    <div>
                                        <div class="font-semibold">Gaming</div>
                                        <div class="text-sm text-gray-500">PlayStation, Xbox, Steam...</div>
                                    </div>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">🎬</span>
                                    <div>
                                        <div class="font-semibold">Streaming</div>
                                        <div class="text-sm text-gray-500">Netflix, Spotify, Disney+...</div>
                                    </div>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">🍎</span>
                                    <div>
                                        <div class="font-semibold">Apple Store</div>
                                        <div class="text-sm text-gray-500">App Store, iTunes...</div>
                                    </div>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">🛍️</span>
                                    <div>
                                        <div class="font-semibold">Shopping</div>
                                        <div class="text-sm text-gray-500">Nike, Zalando, Amazon...</div>
                                    </div>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">✈️</span>
                                    <div>
                                        <div class="font-semibold">Voyage</div>
                                        <div class="text-sm text-gray-500">Uber, Airbnb, Deliveroo...</div>
                                    </div>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-kardafrica-primary transition-all duration-200">
                                    <span class="text-xl">🚀</span>
                                    <div>
                                        <div class="font-semibold">Crypto</div>
                                        <div class="text-sm text-gray-500">Bitcoin, Binance, NordVPN...</div>
                                    </div>
                                </a>
                                <div class="border-t border-gray-100 mt-2 pt-2">
                                    <a href="{{ route('boutique') }}" class="flex items-center space-x-3 px-4 py-3 text-kardafrica-primary hover:bg-gray-50 transition-all duration-200 font-semibold">
                                        <span class="text-xl">🔥</span>
                                        <div>Voir toutes les cartes</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panier -->
                    <div class="relative">
                        <button id="cartBtn" class="text-white hover:text-gray-200 hover:bg-white/10 p-3 rounded-xl transition-all duration-300 relative group">
                            <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span id="cartCount" class="absolute -top-2 -right-2 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold shadow-lg ring-2 ring-white transition-all duration-300 group-hover:scale-110">0</span>
                        </button>
                        
                        <!-- Dropdown du panier -->
                        <div id="cartDropdown" class="cart-dropdown">
                            <div class="p-4 border-b border-gray-100" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);">
                                <h3 class="font-bold flex items-center space-x-3" style="color: white;">
                                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-lg">Mon Panier</span>
                                </h3>
                            </div>
                            <div class="p-4 max-h-72 overflow-y-auto" id="cartItems">
                                <div class="text-center py-8">
                                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 font-medium mb-2">Votre panier est vide</p>
                                    <p class="text-sm text-gray-400">Découvrez nos cartes numériques</p>
                                </div>
                            </div>
                            <div class="p-4 border-t border-gray-100" style="background: linear-gradient(to right, #f9fafb, #f3f4f6);">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="font-semibold" style="color: #374151;">Total:</span>
                                    <div class="flex items-center space-x-2">
                                        <span id="cartTotal" class="text-2xl font-bold" style="color: #44A08D;">0 FCFA</span>
                                        <div style="background: rgba(78, 205, 196, 0.1);" class="p-1 rounded-full">
                                            <svg class="w-4 h-4" fill="none" stroke="#44A08D" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Boutons en row -->
                                <div class="flex space-x-2 mb-3">
                                    <button class="flex-1 border-2 border-gray-300 text-gray-700 py-2 px-4 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 flex items-center justify-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span>Voir panier</span>
                                    </button>
                                    <button class="flex-1 text-white py-2 px-4 rounded-xl font-semibold hover-kardafrica shadow-lg flex items-center justify-center space-x-2" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);">
                                        <svg class="w-4 h-4" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                        <span>Commander</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="#" class="bg-kardafrica-secondary text-white px-6 py-3 rounded-xl text-base font-medium hover-kardafrica shadow-lg">
                        👤 Connexion
                    </a>
                </div>
                
                <!-- Menu mobile -->
                <div class="md:hidden flex items-center space-x-2">
                    <!-- Panier mobile -->
                    <button id="cartBtnMobile" class="text-white hover:text-gray-200 hover:bg-white/10 p-2 rounded-xl transition-all duration-300 relative group">
                        <svg class="w-6 h-6 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <span id="cartCountMobile" class="absolute -top-2 -right-2 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold shadow-lg ring-2 ring-white transition-all duration-300 group-hover:scale-110">0</span>
                    </button>
                    
                    <!-- Menu burger -->
                    <button id="mobileMenuBtn" class="text-white hover:text-gray-200 hover:bg-white/10 p-2 rounded-xl transition-all duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="min-h-screen pt-20">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mx-4 my-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mx-4 my-4">
                {{ session('error') }}
            </div>
        @endif
        
        @yield('content')
    </main>



    <!-- Chatbot -->
    <div class="chatbot-container">
        <div id="chatbotWindow" class="chatbot-window">
            <div class="bg-kardafrica-primary text-white p-4 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold">Assistant Kardafrica</h3>
                    <button id="closeChatbot" class="text-white hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-4 h-96 overflow-y-auto" id="chatMessages">
                <div class="mb-4">
                    <div class="bg-gray-100 p-3 rounded-lg">
                        <p class="text-sm">Bonjour ! Je suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?</p>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200">
                <div class="flex space-x-2">
                    <input type="text" id="chatInput" placeholder="Tapez votre message..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-kardafrica-primary">
                    <button id="sendMessage" class="bg-kardafrica-primary text-white px-4 py-2 rounded-lg hover-kardafrica">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <button id="chatbotToggle" class="chatbot-toggle">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM8 11h1m3 0h1m3 0h1"></path>
            </svg>
        </button>
    </div>

    <!-- Overlay pour la sidebar mobile -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-40 opacity-0 invisible transition-all duration-300 md:hidden backdrop-blur-sm"></div>
    
    <!-- Overlay pour la sidebar panier mobile -->
    <div id="mobileCartOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-40 opacity-0 invisible transition-all duration-300 md:hidden backdrop-blur-sm"></div>
    
    <!-- Mobile Sidebar -->
    <div id="mobileMenu" class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out md:hidden border-l border-gray-200" style="background-color: #ffffff !important;">
        <!-- Sidebar Header -->
        <div class="sidebar-header bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Logo" class="w-10 h-10">
                    <div>
                        <h2 class="font-bold text-lg">Kardafrica</h2>
                        <p class="text-sm opacity-90">Menu Navigation</p>
                    </div>
                </div>
                <button id="closeMobileMenu" class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Sidebar Content -->
        <div class="sidebar-content flex-1 overflow-y-auto px-4 py-6 space-y-4 h-[calc(100vh-200px)]" style="background-color: #ffffff !important;">
            <a href="{{ route('home') }}" class="sidebar-item flex items-center space-x-3 text-gray-700 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-3 rounded-xl transition-all duration-200">
                            <span class="text-xl">🏠</span>
                            <span class="font-medium">Accueil</span>
                        </a>
                        
            <!-- Menu cartes avec dropdown -->
            <div class="sidebar-item space-y-2">
                <button id="mobileCardsBtn" class="w-full flex items-center justify-between text-gray-700 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-3 rounded-xl transition-all duration-200">
                    <div class="flex items-center space-x-3">
                                <span class="text-xl">🎯</span>
                                <span class="font-medium">Cartes</span>
                            </div>
                    <svg id="mobileCardsIcon" class="w-5 h-5 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="mobileCardsSubmenu" class="ml-8 space-y-1 max-h-0 overflow-hidden transition-all duration-300">
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">🎮</span>
                                    <span>Gaming</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">🎬</span>
                                    <span>Streaming</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">🍎</span>
                                    <span>Apple Store</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">🛍️</span>
                                    <span>Shopping</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">✈️</span>
                                    <span>Voyage</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-gray-600 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200">
                                    <span class="text-lg">🚀</span>
                                    <span>Crypto</span>
                                </a>
                                <a href="{{ route('boutique') }}" class="flex items-center space-x-3 text-kardafrica-primary hover:bg-gray-50 px-4 py-2 rounded-lg transition-all duration-200 font-semibold">
                                    <span class="text-lg">🔥</span>
                                    <span>Voir toutes les cartes</span>
                                </a>
                            </div>
                        </div>
                        
            <a href="{{ route('about') }}" class="sidebar-item flex items-center space-x-3 text-gray-700 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-3 rounded-xl transition-all duration-200">
                            <span class="text-xl">ℹ️</span>
                            <span class="font-medium">À propos</span>
                        </a>
                        
            <a href="{{ route('contact') }}" class="sidebar-item flex items-center space-x-3 text-gray-700 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-3 rounded-xl transition-all duration-200">
                <span class="text-xl">📞</span>
                <span class="font-medium">Contact</span>
            </a>
            
            <a href="{{ route('support') }}" class="sidebar-item flex items-center space-x-3 text-gray-700 hover:text-kardafrica-primary hover:bg-gray-50 px-4 py-3 rounded-xl transition-all duration-200">
                <span class="text-xl">🛠️</span>
                <span class="font-medium">Support</span>
            </a>
            
            <!-- Informations de contact dans la sidebar -->
            <div class="border-t border-gray-200 pt-4 mt-6">
                <div class="px-4 py-3 bg-gray-50 rounded-xl">
                    <h3 class="font-semibold text-gray-900 mb-3 text-sm">Contact Rapide</h3>
                    <div class="space-y-2 text-sm">
                        <a href="mailto:hello@kardafrica.com" class="flex items-center space-x-2 text-gray-600 hover:text-kardafrica-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>hello@kardafrica.com</span>
                        </a>
                        <a href="tel:+221XXXXXXXXX" class="flex items-center space-x-2 text-gray-600 hover:text-kardafrica-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span>+221 XX XXX XX XX</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Sidebar Footer -->
        <div class="border-t border-gray-200 p-4" style="background-color: #ffffff !important;">
            <button id="mobileAuthBtn" class="w-full flex items-center justify-center space-x-3 bg-kardafrica-secondary text-white px-6 py-3 rounded-xl font-medium hover-kardafrica shadow-lg transition-all duration-300">
                <span class="text-xl">👤</span>
                <span>Connexion</span>
            </button>
            </div>
            </div>
    
    <!-- Mobile Cart Sidebar -->
    <div id="mobileCartSidebar" class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out md:hidden border-l border-gray-200" style="background-color: #ffffff !important;">
        <!-- Cart Sidebar Header -->
        <div class="sidebar-header bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
        </div>
                    <div>
                        <h2 class="font-bold text-lg">Mon Panier</h2>
                        <p class="text-sm opacity-90">Vos cartes sélectionnées</p>
        </div>
            </div>
                <button id="closeMobileCart" class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
            </button>
        </div>
    </div>

        <!-- Cart Sidebar Content -->
        <div class="sidebar-content flex-1 overflow-y-auto px-4 py-6 h-[calc(100vh-220px)]" style="background-color: #ffffff !important;">
            <div id="mobileCartItems">
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                </div>
                    <p class="text-gray-500 font-medium mb-2">Votre panier est vide</p>
                    <p class="text-sm text-gray-400">Découvrez nos cartes numériques</p>
            </div>
                    </div>
                </div>
        
        <!-- Cart Sidebar Footer -->
        <div class="border-t border-gray-200 p-4" style="background-color: #ffffff !important;">
            <div class="flex justify-between items-center mb-4">
                <span class="font-semibold text-gray-900">Total:</span>
                <div class="flex items-center space-x-2">
                    <span id="mobileCartTotal" class="text-2xl font-bold text-kardafrica-primary">0 FCFA</span>
                    <div class="bg-kardafrica-primary/10 p-1 rounded-full">
                        <svg class="w-4 h-4 text-kardafrica-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                </div>
            </div>
        </div>
            
            <!-- Boutons d'action -->
            <div class="space-y-3">
                <button class="w-full border-2 border-gray-300 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
                    <span>Voir détails</span>
                </button>
                <button class="w-full bg-kardafrica-primary text-white py-3 px-4 rounded-xl font-semibold hover-kardafrica shadow-lg flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span>Commander maintenant</span>
        </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Kardafrica</h3>
                    <p class="text-gray-300">La plateforme de référence pour les cartes numériques en Afrique.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Liens utiles</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#" class="hover:text-white">À propos</a></li>
                        <li><a href="#" class="hover:text-white">Contact</a></li>
                        <li><a href="#" class="hover:text-white">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <p class="text-gray-300">Email: hello@kardafrica.com</p>
                    <p class="text-gray-300">Téléphone: +221 XX XXX XX XX</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Kardafrica. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript pour le loader -->
    <script>
        // Messages de chargement dynamiques
        const loadingMessages = [
            'Chargement en cours...',
            'Préparation de votre expérience...',
            'Connexion à la marketplace...',
            'Chargement des cartes numériques...',
            'Initialisation de Kardafrica...',
            'Presque prêt...'
        ];
        
        function showLoader(message = null) {
            const loader = document.getElementById('pageLoader');
            const loaderText = document.getElementById('loaderText');
            
            if (message) {
                loaderText.textContent = message;
            } else {
                loaderText.textContent = loadingMessages[Math.floor(Math.random() * loadingMessages.length)];
            }
            
            loader.style.display = 'flex';
            loader.classList.remove('loader-fadeout');
            loader.style.opacity = '1';
        }
        
        function hideLoader() {
            const loader = document.getElementById('pageLoader');
            setTimeout(function() {
                loader.classList.add('loader-fadeout');
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 500);
            }, 2000); // Augmenté à 2 secondes
        }
        
        // Afficher le loader au début
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('pageLoader');
            
            // Masquer le loader après le chargement complet
            window.addEventListener('load', function() {
                hideLoader();
            });
            
            // Afficher le loader lors de la navigation
            const links = document.querySelectorAll('a:not([href^="#"]):not([href^="mailto"]):not([href^="tel"]):not([target="_blank"])');
            links.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Vérifier si c'est un lien interne
                    if (link.hostname === window.location.hostname) {
                        // Ajouter un délai pour s'assurer que le loader est visible
                        e.preventDefault();
                        
                        if (link.textContent.includes('Marketplace') || link.textContent.includes('Boutique')) {
                            showLoader('Chargement de la marketplace...');
                        } else if (link.textContent.includes('Mes Cartes')) {
                            showLoader('Chargement de vos cartes...');
                        } else if (link.textContent.includes('Voir') || link.textContent.includes('Détails')) {
                            showLoader('Chargement des détails...');
                        } else if (link.textContent.includes('Accueil')) {
                            showLoader('Retour à l\'accueil...');
                        } else {
                            showLoader();
                        }
                        
                        // Naviguer après un court délai
                        setTimeout(function() {
                            window.location.href = link.href;
                        }, 300);
                    }
                });
            });
            
            // Gérer les soumissions de formulaires
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (form.querySelector('input[name="search"]')) {
                        showLoader('Recherche en cours...');
                    } else {
                        showLoader('Traitement en cours...');
                    }
                });
            });
        });
        
        // Masquer le loader en cas d'erreur de navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                const loader = document.getElementById('pageLoader');
                loader.style.display = 'none';
            }
        });
        
        // Enhanced Navbar Scroll Effect
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('nav');
            
            function handleScroll() {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;
                
                if (scrolled > 50) {
                    navbar.style.background = 'rgba(78, 205, 196, 0.98)';
                    navbar.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.2)';
                } else {
                    navbar.style.background = 'rgba(78, 205, 196, 0.95)';
                    navbar.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.1)';
                }
            }
            
            window.addEventListener('scroll', handleScroll);
        });

        // Enhanced Carousel functionality with animations and particles
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('bannerCarousel');
            if (carousel) {
                const slides = carousel.querySelectorAll('.carousel-slide');
                const dots = carousel.querySelectorAll('.carousel-dot');
                const nextBtn = carousel.querySelector('#nextBtn');
                const prevBtn = carousel.querySelector('#prevBtn');
                let currentSlide = 0;
                let autoSlideInterval;
                let isTransitioning = false;
                
                // Create floating particles for each slide
                function createParticles(slideIndex) {
                    const particleContainer = document.getElementById(`particles-${slideIndex}`);
                    if (!particleContainer) return;
                    
                    particleContainer.innerHTML = '';
                    
                    for (let i = 0; i < 20; i++) {
                        const particle = document.createElement('div');
                        particle.className = 'particle';
                        particle.style.left = Math.random() * 100 + '%';
                        particle.style.animationDelay = Math.random() * 8 + 's';
                        particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                        particleContainer.appendChild(particle);
                    }
                }
                
                // Initialize particles for all slides
                slides.forEach((slide, index) => {
                    createParticles(index);
                });
                
                function showSlide(index, direction = 'next') {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    
                    const currentSlideElement = slides[currentSlide];
                    const nextSlideElement = slides[index];
                    
                    // Reset progress bars
                    slides.forEach(slide => {
                        const progressBar = slide.querySelector('.carousel-progress');
                        if (progressBar) {
                            progressBar.style.width = '0%';
                        }
                    });
                    
                    // Set initial positions
                    nextSlideElement.style.transform = direction === 'next' ? 'translateX(100%)' : 'translateX(-100%)';
                    nextSlideElement.style.opacity = '0';
                    
                    // Remove active class from all slides
                    slides.forEach(slide => {
                        slide.classList.remove('active');
                    });
                    
                    // Add active class to next slide
                    nextSlideElement.classList.add('active');
                    
                    // Animate the transition
                    requestAnimationFrame(() => {
                        currentSlideElement.style.transform = direction === 'next' ? 'translateX(-100%)' : 'translateX(100%)';
                        currentSlideElement.style.opacity = '0';
                        
                        nextSlideElement.style.transform = 'translateX(0)';
                        nextSlideElement.style.opacity = '1';
                    });
                    
                    // Update dots
                    dots.forEach(dot => dot.classList.remove('active'));
                    dots[index].classList.add('active');
                    
                    // Reset current slide
                    currentSlide = index;
                    
                    // Start progress bar animation
                    setTimeout(() => {
                        const progressBar = nextSlideElement.querySelector('.carousel-progress');
                        if (progressBar) {
                            progressBar.style.width = '100%';
                        }
                    }, 100);
                    
                    // Reset transition flag
                    setTimeout(() => {
                        isTransitioning = false;
                        
                        // Reset previous slide position
                        setTimeout(() => {
                            slides.forEach((slide, i) => {
                                if (i !== currentSlide) {
                                    slide.style.transform = 'translateX(100%)';
                                    slide.style.opacity = '0';
                                }
                            });
                        }, 100);
                    }, 800);
                }
                
                function nextSlide() {
                    const next = (currentSlide + 1) % slides.length;
                    showSlide(next, 'next');
                }
                
                function prevSlide() {
                    const prev = (currentSlide - 1 + slides.length) % slides.length;
                    showSlide(prev, 'prev');
                }
                
                function startAutoSlide() {
                    autoSlideInterval = setInterval(nextSlide, 5000);
                }
                
                function stopAutoSlide() {
                    clearInterval(autoSlideInterval);
                }
                
                // Event listeners
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        stopAutoSlide();
                        nextSlide();
                        startAutoSlide();
                    });
                }
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        stopAutoSlide();
                        prevSlide();
                        startAutoSlide();
                    });
                }
                
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        if (index !== currentSlide) {
                            stopAutoSlide();
                            const direction = index > currentSlide ? 'next' : 'prev';
                            showSlide(index, direction);
                            startAutoSlide();
                        }
                    });
                });
                
                // Pause on hover
                carousel.addEventListener('mouseenter', stopAutoSlide);
                carousel.addEventListener('mouseleave', startAutoSlide);
                
                // Touch/swipe support
                let touchStartX = 0;
                let touchEndX = 0;
                
                carousel.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                });
                
                carousel.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                });
                
                function handleSwipe() {
                    const swipeThreshold = 50;
                    const diff = touchStartX - touchEndX;
                    
                    if (Math.abs(diff) > swipeThreshold) {
                        stopAutoSlide();
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }
                        startAutoSlide();
                    }
                }
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (carousel.matches(':hover')) {
                        if (e.key === 'ArrowRight') {
                            stopAutoSlide();
                            nextSlide();
                            startAutoSlide();
                        } else if (e.key === 'ArrowLeft') {
                            stopAutoSlide();
                            prevSlide();
                            startAutoSlide();
                        }
                    }
                });
                
                // Initialize
                showSlide(0, 'next');
                startAutoSlide();
            }
        });
        
        // Cart and Chatbot functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar functionality
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const closeMobileMenu = document.getElementById('closeMobileMenu');
            const mobileCardsBtn = document.getElementById('mobileCardsBtn');
            const mobileCardsSubmenu = document.getElementById('mobileCardsSubmenu');
            const mobileCardsIcon = document.getElementById('mobileCardsIcon');
            const mobileAuthBtn = document.getElementById('mobileAuthBtn');
            
            function openMobileSidebar() {
                if (mobileMenu && mobileMenuOverlay) {
                    // Empêcher le scroll du body
                    document.body.classList.add('mobile-menu-open');
                    
                    // Afficher l'overlay avec animation
                    mobileMenuOverlay.classList.add('show');
                    mobileMenuOverlay.style.display = 'block';
                    
                    // Afficher la sidebar avec animation
                    mobileMenu.classList.add('show');
                    
                    // Animation avec délai pour les éléments
                    setTimeout(() => {
                        mobileMenuOverlay.style.opacity = '1';
                        mobileMenuOverlay.style.visibility = 'visible';
                    }, 10);
                }
            }
            
            function closeMobileSidebar() {
                if (mobileMenu && mobileMenuOverlay) {
                    // Réactiver le scroll du body
                    document.body.classList.remove('mobile-menu-open');
                    
                    // Masquer la sidebar
                    mobileMenu.classList.remove('show');
                    
                    // Masquer l'overlay avec transition
                    mobileMenuOverlay.classList.remove('show');
                    mobileMenuOverlay.style.opacity = '0';
                    mobileMenuOverlay.style.visibility = 'hidden';
                    
                    // Masquer complètement après l'animation
                    setTimeout(() => {
                        mobileMenuOverlay.style.display = 'none';
                    }, 300);
                    
                    // Reset cards submenu
                    if (mobileCardsSubmenu && mobileCardsIcon) {
                        mobileCardsSubmenu.style.maxHeight = '0px';
                        mobileCardsIcon.style.transform = 'rotate(0deg)';
                    }
                }
            }
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openMobileSidebar();
                });
            }
            
            if (closeMobileMenu) {
                closeMobileMenu.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            }
            
            // Fermer en cliquant sur l'overlay
            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            }
            
            // Mobile cards dropdown functionality
            if (mobileCardsBtn && mobileCardsSubmenu && mobileCardsIcon) {
                mobileCardsBtn.addEventListener('click', function() {
                    const isOpen = mobileCardsSubmenu.style.maxHeight && mobileCardsSubmenu.style.maxHeight !== '0px';
                    if (isOpen) {
                        mobileCardsSubmenu.style.maxHeight = '0px';
                        mobileCardsIcon.style.transform = 'rotate(0deg)';
                    } else {
                        mobileCardsSubmenu.style.maxHeight = mobileCardsSubmenu.scrollHeight + 'px';
                        mobileCardsIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }
            
            // Mobile auth button functionality
            if (mobileAuthBtn) {
                mobileAuthBtn.addEventListener('click', function() {
                    openAuthModal();
                    // Fermer la sidebar après avoir ouvert le modal
                    closeMobileSidebar();
                });
            }
            
            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileSidebar();
                }
            });
            
            // Support du swipe pour fermer la sidebar
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            if (mobileMenu) {
                mobileMenu.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                });
                
                mobileMenu.addEventListener('touchmove', function(e) {
                    if (!isDragging) return;
                    
                    currentX = e.touches[0].clientX;
                    const diffX = currentX - startX;
                    
                    // Permettre uniquement le swipe vers la droite
                    if (diffX > 0) {
                        const translateValue = Math.min(diffX, 320); // 320px = largeur de la sidebar
                        mobileMenu.style.transform = `translateX(${translateValue}px)`;
                        
                        // Ajuster l'opacité de l'overlay
                        const opacity = Math.max(0, 1 - (diffX / 320));
                        if (mobileMenuOverlay) {
                            mobileMenuOverlay.style.opacity = opacity;
                        }
                    }
                });
                
                mobileMenu.addEventListener('touchend', function(e) {
                    if (!isDragging) return;
                    isDragging = false;
                    
                    const diffX = currentX - startX;
                    
                    // Si le swipe dépasse 100px, fermer la sidebar
                    if (diffX > 100) {
                        closeMobileSidebar();
                    } else {
                        // Sinon, remettre en position
                        mobileMenu.style.transform = 'translateX(0)';
                        if (mobileMenuOverlay) {
                            mobileMenuOverlay.style.opacity = '1';
                        }
                    }
                });
            }
            
            // Cart functionality
            const cartBtn = document.getElementById('cartBtn');
            const cartBtnMobile = document.getElementById('cartBtnMobile');
            const cartDropdown = document.getElementById('cartDropdown');
            const cartCount = document.getElementById('cartCount');
            const cartCountMobile = document.getElementById('cartCountMobile');
            
            // Éléments de la sidebar panier mobile
            const mobileCartSidebar = document.getElementById('mobileCartSidebar');
            const mobileCartOverlay = document.getElementById('mobileCartOverlay');
            const closeMobileCart = document.getElementById('closeMobileCart');
            const mobileCartItems = document.getElementById('mobileCartItems');
            const mobileCartTotal = document.getElementById('mobileCartTotal');
            
            let cart = JSON.parse(localStorage.getItem('cart')) || [];



            // Fonction pour détecter si on est en mobile
            function isMobile() {
                return window.innerWidth <= 768;
            }

            // Fonction pour ouvrir la sidebar panier mobile
            function openMobileCartSidebar() {
                if (mobileCartSidebar && mobileCartOverlay) {
                    // Empêcher le scroll du body
                    document.body.classList.add('mobile-cart-open');
                    
                    // Afficher l'overlay avec animation
                    mobileCartOverlay.classList.add('show');
                    mobileCartOverlay.style.display = 'block';
                    
                    // Afficher la sidebar avec animation
                    mobileCartSidebar.classList.add('show');
                    
                    // Animation avec délai pour les éléments
                    setTimeout(() => {
                        mobileCartOverlay.style.opacity = '1';
                        mobileCartOverlay.style.visibility = 'visible';
                    }, 10);
                }
            }
            
            // Fonction pour fermer la sidebar panier mobile
            function closeMobileCartSidebar() {
                if (mobileCartSidebar && mobileCartOverlay) {
                    // Réactiver le scroll du body
                    document.body.classList.remove('mobile-cart-open');
                    
                    // Masquer la sidebar
                    mobileCartSidebar.classList.remove('show');
                    
                    // Masquer l'overlay avec transition
                    mobileCartOverlay.classList.remove('show');
                    mobileCartOverlay.style.opacity = '0';
                    mobileCartOverlay.style.visibility = 'hidden';
                    
                    // Masquer complètement après l'animation
                    setTimeout(() => {
                        mobileCartOverlay.style.display = 'none';
                    }, 300);
                }
            }
            
            function updateCartDisplay() {
                const count = cart.length;
                if (cartCount) cartCount.textContent = count;
                if (cartCountMobile) cartCountMobile.textContent = count;
                
                // Contenu des items (même pour desktop et mobile)
                const emptyCartHtml = `
                            <div class="text-center py-8">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                        <p class="text-gray-500 font-medium mb-2">Votre panier est vide</p>
                        <p class="text-sm text-gray-400">Découvrez nos cartes numériques</p>
                            </div>
                        `;
                
                const cartItemsHtml = cart.map(item => `
                    <div class="cart-item flex justify-between items-center p-3 mb-2 bg-white rounded-lg border border-gray-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);">
                                        <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                <span class="text-sm font-medium text-gray-900">${item.name}</span>
                                <p class="text-xs text-gray-500">Carte numérique</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                            <span class="text-sm font-bold text-kardafrica-primary">${new Intl.NumberFormat('fr-FR').format(item.price)} FCFA</span>
                            <button class="p-1 hover:bg-red-50 rounded transition-all duration-200 text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                
                // Update cart items display desktop
                const cartItems = document.getElementById('cartItems');
                if (cartItems) {
                    cartItems.innerHTML = cart.length === 0 ? emptyCartHtml : cartItemsHtml;
                }
                
                // Update cart items display mobile
                if (mobileCartItems) {
                    mobileCartItems.innerHTML = cart.length === 0 ? emptyCartHtml : cartItemsHtml;
                    }
                    
                    // Update total
                    const total = cart.reduce((sum, item) => sum + parseFloat(item.price), 0);
                const totalText = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
                
                    const cartTotal = document.getElementById('cartTotal');
                if (cartTotal) cartTotal.textContent = totalText;
                
                if (mobileCartTotal) mobileCartTotal.textContent = totalText;
            }
            
            if (cartBtn) {
                cartBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isMobile()) {
                        // En mobile, ouvrir la sidebar
                        openMobileCartSidebar();
                    } else {
                        // En desktop, ouvrir le dropdown
                        if (cartDropdown) {
                            cartDropdown.classList.toggle('show');
                        }
                    }
                });
            }
            
            if (cartBtnMobile) {
                cartBtnMobile.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Toujours ouvrir la sidebar pour le bouton mobile
                    openMobileCartSidebar();
                });
            }

            // Event listeners pour fermer la sidebar panier mobile
            if (closeMobileCart) {
                closeMobileCart.addEventListener('click', function() {
                    closeMobileCartSidebar();
                });
            }
            
            // Fermer en cliquant sur l'overlay
            if (mobileCartOverlay) {
                mobileCartOverlay.addEventListener('click', function() {
                    closeMobileCartSidebar();
                });
            }
            
            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileCartSidebar && mobileCartSidebar.classList.contains('show')) {
                    closeMobileCartSidebar();
                }
            });

            // Support du swipe pour fermer la sidebar panier
            let cartStartX = 0;
            let cartCurrentX = 0;
            let cartIsDragging = false;
            
            if (mobileCartSidebar) {
                mobileCartSidebar.addEventListener('touchstart', function(e) {
                    cartStartX = e.touches[0].clientX;
                    cartIsDragging = true;
                });
                
                mobileCartSidebar.addEventListener('touchmove', function(e) {
                    if (!cartIsDragging) return;
                    
                    cartCurrentX = e.touches[0].clientX;
                    const diffX = cartCurrentX - cartStartX;
                    
                    // Permettre uniquement le swipe vers la droite
                    if (diffX > 0) {
                        const translateValue = Math.min(diffX, 320); // 320px = largeur de la sidebar
                        mobileCartSidebar.style.transform = `translateX(${translateValue}px)`;
                        
                        // Ajuster l'opacité de l'overlay
                        const opacity = Math.max(0, 1 - (diffX / 320));
                        if (mobileCartOverlay) {
                            mobileCartOverlay.style.opacity = opacity;
                        }
                    }
                });
                
                mobileCartSidebar.addEventListener('touchend', function(e) {
                    if (!cartIsDragging) return;
                    cartIsDragging = false;
                    
                    const diffX = cartCurrentX - cartStartX;
                    
                    // Si le swipe dépasse 100px, fermer la sidebar
                    if (diffX > 100) {
                        closeMobileCartSidebar();
                    } else {
                        // Sinon, remettre en position
                        mobileCartSidebar.style.transform = 'translateX(0)';
                        if (mobileCartOverlay) {
                            mobileCartOverlay.style.opacity = '1';
                        }
                    }
                });
            }
            
            // Close cart when clicking outside (desktop dropdown only)
            document.addEventListener('click', function(e) {
                if (cartDropdown && cartBtn && cartBtnMobile) {
                    if (!cartBtn.contains(e.target) && !cartBtnMobile.contains(e.target) && !cartDropdown.contains(e.target)) {
                        // Fermer seulement si on n'est pas en mobile (puisque mobile utilise la sidebar)
                        if (!isMobile()) {
                        cartDropdown.classList.remove('show');
                        }
                    }
                }
            });


            
            // Initialize cart display
            updateCartDisplay();
            
            // Section animations on scroll
            const sections = document.querySelectorAll('.section-animate');
            const brandItems = document.querySelectorAll('.brand-grid-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            sections.forEach(section => {
                observer.observe(section);
            });
            
            // Animate brand items with delay
            const brandObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = Array.from(brandItems).indexOf(entry.target);
                        setTimeout(() => {
                            entry.target.classList.add('animate');
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            brandItems.forEach(item => {
                brandObserver.observe(item);
            });
            
            // Chatbot functionality
            const chatbotToggle = document.getElementById('chatbotToggle');
            const chatbotWindow = document.getElementById('chatbotWindow');
            const closeChatbot = document.getElementById('closeChatbot');
            const chatInput = document.getElementById('chatInput');
            const sendMessage = document.getElementById('sendMessage');
            const chatMessages = document.getElementById('chatMessages');
            
            function openChatbot() {
                chatbotWindow.classList.add('show');
            }
            
            function closeChatbotWindow() {
                chatbotWindow.classList.remove('show');
            }
            
            function addMessage(message, isUser = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `mb-4 ${isUser ? 'text-right' : ''}`;
                messageDiv.innerHTML = `
                    <div class="${isUser ? 'bg-kardafrica-primary text-white ml-8' : 'bg-gray-100 mr-8'} p-3 rounded-lg">
                        <p class="text-sm">${message}</p>
                    </div>
                `;
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function sendBotMessage() {
                const userMessage = chatInput.value.trim();
                if (userMessage) {
                    addMessage(userMessage, true);
                    chatInput.value = '';
                    
                    // Simulate bot response
                    setTimeout(() => {
                        const responses = [
                            "Merci pour votre message ! Un agent va vous répondre sous peu.",
                            "Je peux vous aider à trouver la carte parfaite pour vous.",
                            "Avez-vous des questions sur nos cartes numériques ?",
                            "Notre équipe est là pour vous accompagner dans vos achats.",
                            "Consultez notre marketplace pour découvrir toutes nos cartes disponibles !"
                        ];
                        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                        addMessage(randomResponse);
                    }, 1000);
                }
            }
            
            chatbotToggle.addEventListener('click', openChatbot);
            closeChatbot.addEventListener('click', closeChatbotWindow);
            sendMessage.addEventListener('click', sendBotMessage);
            
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendBotMessage();
                }
            });
        });
    </script>

    <!-- Modal de Connexion/Inscription -->
    <div id="authModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close-modal" id="closeAuthModal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h2 class="text-2xl font-bold mb-2">Bienvenue sur Kardafrica</h2>
                <p class="text-sm opacity-90">Votre marketplace de cartes numériques</p>
            </div>
            
            <div class="modal-tabs">
                <div class="modal-tab active" data-tab="login">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Connexion
                </div>
                <div class="modal-tab" data-tab="register">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Inscription
                </div>
            </div>
            
            <div class="modal-body">
                <!-- Formulaire de Connexion -->
                <div id="login-content" class="tab-content active">
                    <form id="loginForm" action="#" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="login-email">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Adresse e-mail
                            </label>
                            <input type="email" id="login-email" name="email" class="form-input" placeholder="votre.email@exemple.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="login-password">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Mot de passe
                            </label>
                            <input type="password" id="login-password" name="password" class="form-input" placeholder="••••••••" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="flex items-center">
                                <input type="checkbox" name="remember" class="mr-2 rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="text-sm text-gray-600">Se souvenir de moi</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="form-button">Se connecter</button>
                    </form>
                    
                    <div class="form-footer">
                        <a href="{{ route('password.request') }}" class="form-link">Mot de passe oublié ?</a>
                    </div>
                </div>
                
                <!-- Formulaire d'Inscription -->
                <div id="register-content" class="tab-content">
                    <form id="registerForm" action="#" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="register-name">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Nom complet
                            </label>
                            <input type="text" id="register-name" name="name" class="form-input" placeholder="Votre nom complet" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="register-email">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Adresse e-mail
                            </label>
                            <input type="email" id="register-email" name="email" class="form-input" placeholder="votre.email@exemple.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="register-password">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Mot de passe
                            </label>
                            <input type="password" id="register-password" name="password" class="form-input" placeholder="••••••••" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="register-password-confirm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Confirmer le mot de passe
                            </label>
                            <input type="password" id="register-password-confirm" name="password_confirmation" class="form-input" placeholder="••••••••" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="flex items-center">
                                <input type="checkbox" name="terms" class="mr-2 rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary" required>
                                <span class="text-sm text-gray-600">J'accepte les <a href="#" class="form-link">conditions d'utilisation</a></span>
                            </label>
                        </div>
                        
                        <button type="submit" class="form-button">Créer mon compte</button>
                    </form>
                    
                    <div class="form-footer">
                        <p class="text-sm text-gray-600">
                            Déjà un compte ? 
                            <a href="#" class="form-link" data-switch-tab="login">Se connecter</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script pour la fonctionnalité du modal d'authentification -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const authModal = document.getElementById('authModal');
            const closeAuthModal = document.getElementById('closeAuthModal');
            const tabs = document.querySelectorAll('.modal-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Fonction pour ouvrir le modal
            function openAuthModal() {
                if (authModal) {
                authModal.classList.add('show');
                document.body.style.overflow = 'hidden';
                }
            }
            
            // Fonction pour fermer le modal
            function closeAuthModalFunc() {
                if (authModal) {
                authModal.classList.remove('show');
                document.body.style.overflow = 'auto';
                }
            }
            
            // Fonction pour changer d'onglet
            function switchTab(tabName) {
                // Retirer la classe active de tous les onglets
                tabs.forEach(tab => tab.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Ajouter la classe active au bon onglet
                const targetTab = document.querySelector(`[data-tab="${tabName}"]`);
                const targetContent = document.getElementById(`${tabName}-content`);
                if (targetTab) targetTab.classList.add('active');
                if (targetContent) targetContent.classList.add('active');
            }
            
            // Gérer le clic sur les onglets
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
            
            // Gérer le lien de basculement dans le formulaire
            document.querySelectorAll('[data-switch-tab]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-switch-tab');
                    switchTab(tabName);
                });
            });
            
            // Ouvrir le modal quand on clique sur le bouton connexion
            document.addEventListener('click', function(e) {
                // Vérifier si c'est un bouton de connexion (desktop ou mobile)
                if ((e.target.textContent && e.target.textContent.includes('👤 Connexion')) ||
                    (e.target.closest('button') && e.target.closest('button').textContent.includes('👤 Connexion')) ||
                    (e.target.closest('a') && e.target.closest('a').textContent.includes('👤 Connexion')) ||
                    (e.target.textContent && e.target.textContent.includes('Se connecter'))) {
                    e.preventDefault();
                    openAuthModal();
                }
            });
            
            // Fermer le modal
            if (closeAuthModal) {
            closeAuthModal.addEventListener('click', closeAuthModalFunc);
            }
            
            // Fermer le modal en cliquant à l'extérieur
            if (authModal) {
            authModal.addEventListener('click', function(e) {
                if (e.target === authModal) {
                    closeAuthModalFunc();
                }
            });
            }
            
            // Fermer le modal avec la touche Échap
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && authModal && authModal.classList.contains('show')) {
                    closeAuthModalFunc();
                }
            });
            
            // Gérer la soumission des formulaires
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Simuler une connexion réussie
                alert('Connexion réussie ! (simulation)');
                closeAuthModalFunc();
            });
            }
            
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Vérifier que les mots de passe correspondent
                const password = document.getElementById('register-password').value;
                const confirmPassword = document.getElementById('register-password-confirm').value;
                
                if (password !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas !');
                    return;
                }
                
                // Simuler une inscription réussie
                alert('Inscription réussie ! (simulation)');
                closeAuthModalFunc();
            });
            }

            // Exposer la fonction globalement pour le menu mobile
            window.openAuthModal = openAuthModal;
        });
    </script>
</body>
</html> 