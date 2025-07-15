@extends('layouts.app')

@section('title', $card->name . ' - Kardafrica')

@section('content')
<style>
    /* Design épuré et dynamique */
    .clean-container {
        background: linear-gradient(135deg, #ffffff 0%, #fafbff 100%);
        min-height: 100vh;
        position: relative;
        overflow: hidden;
    }
    
    .clean-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 10%, rgba(78, 205, 196, 0.03) 0%, transparent 50%),
            radial-gradient(circle at 80% 90%, rgba(68, 160, 141, 0.05) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
    }
    
    .content-wrapper {
        position: relative;
        z-index: 1;
    }
    
    /* Animations d'entrée */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .animate-fadeInLeft {
        animation: fadeInLeft 0.8s ease-out;
    }
    
    .animate-fadeInRight {
        animation: fadeInRight 0.8s ease-out;
    }
    
    .animate-scaleIn {
        animation: scaleIn 0.6s ease-out;
    }
    
    .breadcrumb-clean {
        color: #64748b;
        font-size: 14px;
        margin-bottom: 32px;
        padding: 16px 24px;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .breadcrumb-clean:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .breadcrumb-clean a {
        color: #64748b;
        text-decoration: none;
        transition: all 0.3s ease;
        padding: 4px 8px;
        border-radius: 20px;
    }
    
    .breadcrumb-clean a:hover {
        color: #44A08D;
        background: rgba(68, 160, 141, 0.1);
        transform: scale(1.05);
    }
    
    .product-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        margin-bottom: 64px;
    }
    
    @media (max-width: 1024px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
    }
    
    .image-section {
        position: relative;
    }
    
    .card-image-clean {
        width: 100%;
        height: 500px;
        object-fit: cover;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.1),
            0 1px 0px rgba(255, 255, 255, 0.6) inset;
        transition: all 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .card-image-clean::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s ease;
    }
    
    .card-image-clean:hover {
        transform: translateY(-10px) rotateY(5deg);
        box-shadow: 
            0 30px 60px rgba(0, 0, 0, 0.15),
            0 1px 0px rgba(255, 255, 255, 0.6) inset;
    }
    
    .card-image-clean:hover::before {
        left: 100%;
    }
    
    .card-placeholder-clean {
        width: 100%;
        height: 500px;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.1),
            0 1px 0px rgba(255, 255, 255, 0.6) inset;
        transition: all 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .card-placeholder-clean::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
        animation: shimmer 3s infinite;
    }
    
    .card-placeholder-clean:hover {
        transform: translateY(-10px);
        box-shadow: 
            0 30px 60px rgba(0, 0, 0, 0.15),
            0 1px 0px rgba(255, 255, 255, 0.6) inset;
    }
    
    .status-badge-clean {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #64748b;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        z-index: 10;
    }
    
    .status-badge-clean:hover {
        transform: scale(1.1) translateY(-2px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    
    .status-active {
        color: #059669;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #86efac;
        box-shadow: 0 8px 20px rgba(5, 150, 105, 0.2);
    }
    
    .status-expired {
        color: #dc2626;
        background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
        border-color: #fca5a5;
        box-shadow: 0 8px 20px rgba(220, 38, 38, 0.2);
    }
    
    .info-section {
        padding: 24px 0;
    }
    
    .product-title {
        font-size: 40px;
        font-weight: 700;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        line-height: 1.2;
        transition: all 0.3s ease;
    }
    
    .product-title:hover {
        transform: translateX(5px);
    }
    
    .product-brand {
        color: #64748b;
        font-size: 18px;
        margin-bottom: 40px;
        padding: 8px 16px;
        background: rgba(100, 116, 139, 0.1);
        border-radius: 20px;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .product-brand:hover {
        background: rgba(68, 160, 141, 0.1);
        color: #44A08D;
        transform: scale(1.05);
    }
    
    .price-section {
        margin-bottom: 50px;
        position: relative;
    }
    
    .price-value {
        font-size: 48px;
        font-weight: 800;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .price-value::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        border-radius: 2px;
        transition: width 0.3s ease;
    }
    
    .price-value:hover::after {
        width: 120px;
    }
    
    .price-currency {
        color: #64748b;
        font-size: 16px;
        font-weight: 500;
    }
    
    .description-clean {
        margin-bottom: 50px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .description-clean::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
    }
    
    .description-clean:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }
    
    .description-title {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .description-title::before {
        content: '📝';
        font-size: 20px;
    }
    
    .description-text {
        color: #475569;
        line-height: 1.7;
        font-size: 15px;
    }
    
    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 50px;
    }
    
    @media (max-width: 640px) {
        .details-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .detail-item {
        padding: 25px;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(15px);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .detail-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .detail-item:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .detail-item:hover::before {
        transform: scaleX(1);
    }
    
    .detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
    }
    
    .detail-value {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }
    
    .detail-value.code {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        background: #f1f5f9;
        padding: 8px 12px;
        border-radius: 8px;
        margin-top: 8px;
    }
    
    .balance-progress {
        margin-top: 12px;
        height: 6px;
        background: #f1f5f9;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    
    .balance-fill {
        height: 100%;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        border-radius: 10px;
        transition: width 1.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .balance-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
        animation: shimmer 2s infinite;
    }
    
    .actions-section {
        display: flex;
        gap: 20px;
        margin-bottom: 50px;
    }
    
    @media (max-width: 640px) {
        .actions-section {
            flex-direction: column;
        }
    }
    
    .btn-primary {
        flex: 1;
        padding: 18px 32px;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(68, 160, 141, 0.3);
    }
    
    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(68, 160, 141, 0.4);
    }
    
    .btn-primary:hover::before {
        left: 100%;
    }
    
    .btn-secondary {
        flex: 1;
        padding: 18px 32px;
        background: rgba(255, 255, 255, 0.9);
        color: #64748b;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(15px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }
    
    .btn-secondary:hover {
        transform: translateY(-3px);
        background: rgba(255, 255, 255, 1);
        color: #44A08D;
        border-color: rgba(68, 160, 141, 0.3);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .features-list {
        display: flex;
        gap: 30px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }
    
    @media (max-width: 640px) {
        .features-list {
            flex-direction: column;
            gap: 20px;
        }
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 8px 12px;
        border-radius: 12px;
    }
    
    .feature-item:hover {
        color: #44A08D;
        background: rgba(68, 160, 141, 0.1);
        transform: scale(1.05);
    }
    
    .feature-icon {
        width: 20px;
        height: 20px;
        color: #44A08D;
        transition: all 0.3s ease;
    }
    
    .feature-item:hover .feature-icon {
        transform: scale(1.2);
    }
    
    .navigation-clean {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 30px;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 640px) {
        .navigation-clean {
            flex-direction: column;
            gap: 20px;
        }
    }
    
    .nav-btn {
        padding: 16px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        overflow: hidden;
    }
    
    .nav-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.4s ease;
    }
    
    .nav-btn:hover::before {
        left: 100%;
    }
    
    .nav-btn-back {
        background: rgba(248, 250, 252, 0.9);
        color: #64748b;
        border: 1px solid rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(10px);
    }
    
    .nav-btn-back:hover {
        background: rgba(241, 245, 249, 1);
        color: #475569;
        transform: translateX(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .nav-btn-forward {
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(68, 160, 141, 0.3);
    }
    
    .nav-btn-forward:hover {
        transform: translateX(3px);
        box-shadow: 0 12px 30px rgba(68, 160, 141, 0.4);
    }
    
    .nav-icon {
        width: 18px;
        height: 18px;
        transition: transform 0.3s ease;
    }
    
    .nav-btn:hover .nav-icon {
        transform: scale(1.2);
    }
    
    /* Animation de démarrage */
    .page-element {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .page-element:nth-child(1) { animation-delay: 0.1s; }
    .page-element:nth-child(2) { animation-delay: 0.2s; }
    .page-element:nth-child(3) { animation-delay: 0.3s; }
    .page-element:nth-child(4) { animation-delay: 0.4s; }
    .page-element:nth-child(5) { animation-delay: 0.5s; }
</style>

<div class="clean-container">
    <div class="content-wrapper">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <!-- Breadcrumb -->
            <nav class="breadcrumb-clean page-element">
                <a href="{{ route('home') }}">🏠 Accueil</a>
                <span class="mx-1">·</span>
                <a href="{{ route('boutique') }}">🛍️ Marketplace</a>
                <span class="mx-1">·</span>
                <span style="color: #44A08D; font-weight: 600;">{{ $card->name }}</span>
            </nav>

            <!-- Contenu principal -->
            <div class="product-grid">
                <!-- Section Image -->
                <div class="image-section page-element animate-fadeInLeft">
                    @if($card->image)
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="card-image-clean">
                    @else
                        <div class="card-placeholder-clean">
                            <span style="font-size: 64px; color: #cbd5e1; font-weight: bold;">{{ strtoupper(substr($card->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    
                    <!-- Badge de statut -->
                    <div class="status-badge-clean {{ $card->status === 'active' ? 'status-active' : ($card->status === 'expired' ? 'status-expired' : '') }}">
                        @if($card->status === 'active')
                            ✅ Actif
                        @elseif($card->status === 'expired')
                            ⏰ Expiré
                        @else
                            ⏸️ Inactif
                        @endif
                    </div>
                </div>

                <!-- Section Informations -->
                <div class="info-section page-element animate-fadeInRight">
                    <!-- Titre et marque -->
                    <h1 class="product-title">{{ $card->name }}</h1>
                    @if($card->brand)
                        <p class="product-brand">🏢 {{ $card->brand }}</p>
                    @endif

                    <!-- Prix -->
                    <div class="price-section">
                        <div class="price-value">{{ $card->formatted_value }}</div>
                        <div class="price-currency">{{ $card->currency }}</div>
                    </div>

                    <!-- Description -->
                    @if($card->description)
                        <div class="description-clean">
                            <h3 class="description-title">Description</h3>
                            <p class="description-text">{{ $card->description }}</p>
                        </div>
                    @endif

                    <!-- Détails -->
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">🎯 Type</div>
                            <div class="detail-value">{{ ucfirst($card->type) }}</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">🔢 Code</div>
                            <div class="detail-value code">{{ $card->code }}</div>
                        </div>
                        
                        @if($card->expiry_date)
                            <div class="detail-item">
                                <div class="detail-label">📅 Expiration</div>
                                <div class="detail-value">{{ $card->expiry_date->format('d/m/Y') }}</div>
                            </div>
                        @endif
                        
                        <div class="detail-item">
                            <div class="detail-label">💰 Solde</div>
                            <div class="detail-value" style="color: #44A08D;">{{ $card->formatted_balance }}</div>
                            <div class="balance-progress">
                                <div class="balance-fill" style="width: {{ ($card->balance / $card->value) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="actions-section">
                        <button class="btn-primary">🛒 Acheter maintenant</button>
                        <button class="btn-secondary">❤️ Ajouter aux favoris</button>
                    </div>

                    <!-- Fonctionnalités -->
                    <div class="features-list">
                        <div class="feature-item">
                            <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Livraison instantanée
                        </div>
                        <div class="feature-item">
                            <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            Paiement sécurisé
                        </div>
                        <div class="feature-item">
                            <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                            </svg>
                            Support 24/7
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="navigation-clean page-element">
                <a href="{{ route('boutique') }}" class="nav-btn nav-btn-back">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Retour à la marketplace
                </a>
                
                <a href="{{ route('cards.index') }}" class="nav-btn nav-btn-forward">
                    Mes cartes
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes de détails au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = '0s';
                entry.target.classList.add('animate-scaleIn');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.detail-item').forEach(item => {
        observer.observe(item);
    });

    // Effet de parallax subtil sur le mouvement de la souris
    document.addEventListener('mousemove', function(e) {
        const x = (e.clientX / window.innerWidth) * 100;
        const y = (e.clientY / window.innerHeight) * 100;
        
        const container = document.querySelector('.clean-container::before');
        if (container) {
            container.style.transform = `translate(${x * 0.02}px, ${y * 0.02}px)`;
        }
    });

    // Animation de la barre de progression avec délai
    setTimeout(() => {
        const progressBar = document.querySelector('.balance-fill');
        if (progressBar) {
            progressBar.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
        }
    }, 1000);
});
</script>
@endsection 