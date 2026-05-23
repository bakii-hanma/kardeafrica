<div id="pageLoader" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white transition-opacity duration-500">
    <div class="relative flex flex-col items-center justify-center transform scale-150">
        
        <!-- Motion Lines (Impact effect) -->
        <div class="motion-lines absolute -top-12 left-1/2 -translate-x-1/2 flex gap-2 opacity-0">
             <div class="w-1 h-3 bg-gray-300 rounded-full transform -rotate-45"></div>
             <div class="w-1 h-4 bg-gray-300 rounded-full -mt-2"></div>
             <div class="w-1 h-3 bg-gray-300 rounded-full transform rotate-45"></div>
        </div>

        <!-- Hand/Assembly Container -->
        <div class="hand-container relative flex flex-col items-center pt-8 pb-32 overflow-visible">
            <!-- Ring -->
            <div class="w-8 h-8 rounded-full border-[3px] border-gray-400 bg-transparent z-50 relative box-border shadow-sm"></div>
            
            <!-- Cards Wrapper -->
            <div class="absolute top-[40px] left-1/2 -translate-x-1/2 w-0 h-0 flex justify-center">
                
                <!-- Card 1: Blue (Left Back) -->
                <div class="card-wrapper absolute" style="--rotation: -25deg; --z: 1; --tx: -8px;">
                    <div class="card-body bg-[#00439C]">
                         <div class="hole"></div>
                    </div>
                </div>

                <!-- Card 2: Green (Middle Back) -->
                <div class="card-wrapper absolute" style="--rotation: 15deg; --z: 2; --tx: 5px;">
                    <div class="card-body bg-[#1DB954]">
                         <div class="hole"></div>
                    </div>
                </div>

                <!-- Card 3: Red (Right Back) -->
                <div class="card-wrapper absolute" style="--rotation: 30deg; --z: 3; --tx: 12px;">
                    <div class="card-body bg-[#E50914]">
                         <div class="hole"></div>
                    </div>
                </div>

                <!-- Card 4: Logo (Front) -->
                <div class="card-wrapper absolute" style="--rotation: -5deg; --z: 10; --tx: 0px;">
                    <div class="card-body bg-white border border-gray-100 flex items-center justify-center shadow-md">
                        <div class="hole"></div>
                        <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Logo" class="w-6 h-6 object-contain">
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Loading Text (Hidden but structure kept for JS compatibility) -->
        <div id="loaderText" class="mt-24 text-gray-400 font-medium tracking-wider text-xs uppercase hidden">Chargement...</div>
    </div>
</div>

<style>
    .hand-container {
        animation: sway 2s ease-in-out infinite;
        transform-origin: top center;
    }

    @keyframes sway {
        0%, 100% { transform: rotate(-3deg); }
        50% { transform: rotate(3deg); }
    }

    .card-wrapper {
        top: 0;
        width: 42px;
        height: 68px;
        transform-origin: top center;
        transform: rotate(var(--rotation)) translateX(var(--tx));
        z-index: var(--z);
        transition: transform 0.3s ease;
        animation: fan 2s ease-in-out infinite;
    }

    @keyframes fan {
        0%, 100% { 
            transform: rotate(var(--rotation)) translateX(var(--tx)) translateY(0); 
        }
        50% { 
            transform: rotate(calc(var(--rotation) * 0.8)) translateX(calc(var(--tx) * 0.8)) translateY(-2px); 
        } 
    }

    .card-body {
        width: 100%;
        height: 100%;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        position: relative;
    }

    .hole {
        width: 6px;
        height: 6px;
        background-color: white; /* Matches loader bg to look like a hole */
        border-radius: 50%;
        position: absolute;
        top: 5px;
        left: 50%;
        transform: translateX(-50%);
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
    }
    
    /* Motion lines animation */
    .motion-lines {
        animation: impact 2s ease-in-out infinite;
    }
    
    @keyframes impact {
        0%, 100% { opacity: 0; transform: translate(-50%, 0) scale(0.8); }
        50% { opacity: 0.6; transform: translate(-50%, -8px) scale(1.1); }
    }
</style>
