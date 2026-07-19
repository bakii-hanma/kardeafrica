@extends('layouts.app')

@section('title', 'Vérification du paiement - KardAfrica')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" x-data="paymentVerification()">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 text-center">
            <!-- Loading State -->
            <div x-show="status === 'verifying'">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Vérification du paiement</h2>
                <p class="text-gray-500 text-sm">Nous vérifions le statut de votre transaction auprès de E-Billing...</p>
            </div>

            <!-- Success State -->
            <div x-show="status === 'success'" style="display: none;">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Paiement validé !</h2>
                <p class="text-gray-500 text-sm mb-6">Votre commande a été traitée avec succès.</p>
                <p class="text-sm text-gray-400">Redirection en cours...</p>
            </div>

            <!-- Error State -->
            <div x-show="status === 'error'" style="display: none;">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Échec du paiement</h2>
                <p class="text-gray-500 text-sm mb-6" x-text="errorMessage || 'Le paiement n\'a pas pu être validé.'"></p>
                
                <div class="space-y-3">
                    <a href="{{ route('checkout.index') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Réessayer le paiement
                    </a>
                    <a href="{{ route('cart.index') }}" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Retour au panier
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentVerification', () => ({
            status: 'verifying', // verifying, success, error
            errorMessage: '',
            
            init() {
                this.verifyPayment();
            },

            async verifyPayment() {
                try {
                    const response = await fetch('{{ route("payment.finalize") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ref: @json($ref)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.status = 'success';
                        @if(!empty($purchaseValue) && !empty($purchaseOrderId))
                        // Meta Pixel — Purchase (l'événement n°1). eventID = order_<id>
                        // pour que Meta déduplique si la page est rechargée.
                        if (window.fbq) {
                            fbq('track', 'Purchase', {
                                value: {{ $purchaseValue }},
                                currency: '{{ $purchaseCurrency }}',
                                content_type: 'product'
                            }, { eventID: 'order_{{ $purchaseOrderId }}' });
                        }
                        @endif
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        this.status = 'error';
                        this.errorMessage = data.message;
                    }
                } catch (error) {
                    console.error('Error verifying payment:', error);
                    this.status = 'error';
                    this.errorMessage = 'Une erreur technique est survenue.';
                }
            }
        }));
    });
</script>
@endpush
@endsection
