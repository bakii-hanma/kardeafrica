{{--
    Form partagé create + edit
    Variables :
      - $card       (MerchantCard, vide pour create)
      - $categories (assoc array slug => label)
      - $merchants  (collection [{id, label, kyc_ok}])
      - $isEdit     (bool)
--}}
<style>
    .amf-wrap { padding: 24px; font-family: 'Inter','Figtree',sans-serif; max-width: 1180px; margin: 0 auto; }

    /* Hero */
    .amf-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        color: white; border-radius: 18px;
        padding: 22px 26px; margin-bottom: 18px;
        position: relative; overflow: hidden;
    }
    .amf-hero-glow {
        position: absolute; top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(94,234,212,0.18), transparent 60%);
        pointer-events: none;
    }
    .amf-hero a {
        display: inline-flex; align-items: center; gap: 5px;
        color: rgba(255,255,255,0.7);
        font-size: 12px; font-weight: 700; text-decoration: none;
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: 8px; position: relative;
    }
    .amf-hero a:hover { color: #5EEAD4; }
    .amf-hero h1 {
        margin: 0; font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 24px; font-weight: 800; letter-spacing: -0.02em;
        position: relative;
    }
    .amf-hero p {
        margin: 6px 0 0; color: rgba(255,255,255,.75);
        font-size: 13px; line-height: 1.5; max-width: 620px;
        position: relative;
    }

    /* Errors */
    .amf-errors {
        background: #FEE2E2; color: #991B1B;
        padding: 12px 14px; border-radius: 12px;
        margin-bottom: 16px; font-size: 13px;
    }
    .amf-errors strong { display: block; margin-bottom: 4px; font-weight: 800; }
    .amf-errors ul { margin: 0; padding-left: 18px; }

    /* Sections */
    .amf-section {
        background: white; border: 1px solid #E2E8F0;
        border-radius: 16px; padding: 22px;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .amf-section-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
    .amf-step {
        flex-shrink: 0;
        width: 32px; height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 14px;
    }
    .amf-section-title {
        margin: 0; font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        line-height: 1.3;
    }
    .amf-section-hint { font-size: 12px; color: #64748B; margin: 2px 0 0; }

    .amf-field { margin-bottom: 14px; }
    .amf-field:last-child { margin-bottom: 0; }
    .amf-label {
        display: block; font-size: 12px; font-weight: 700; color: #334155;
        margin-bottom: 6px;
    }
    .amf-label-req { color: #DC2626; }
    .amf-input, .amf-select, .amf-textarea {
        width: 100%; padding: 10px 12px;
        font-size: 14px; font-family: inherit; color: #0F172A;
        background: white; border: 1.5px solid #E2E8F0; border-radius: 10px;
        outline: none;
    }
    .amf-input:focus, .amf-select:focus, .amf-textarea:focus {
        border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.15);
    }
    .amf-textarea { resize: vertical; min-height: 80px; }
    .amf-error { font-size: 11px; color: #DC2626; margin-top: 4px; font-weight: 600; }
    .amf-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; }
    .amf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .amf-row { grid-template-columns: 1fr; } }

    /* Visual upload */
    .amf-upload {
        display: block; position: relative;
        border: 2px dashed #CBD5E1; border-radius: 16px;
        padding: 24px 20px; text-align: center;
        cursor: pointer; background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
        transition: all .2s;
    }
    .amf-upload:hover { border-color: #44A08D; background: #F0FDFA; }
    .amf-upload input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .amf-upload-preview {
        display: block; margin: 0 auto;
        max-width: 360px; width: 100%;
        aspect-ratio: 1.55; object-fit: cover;
        border-radius: 12px; background: #F1F5F9;
        box-shadow: 0 10px 24px -8px rgba(15,23,42,.20);
    }
    .amf-upload-ic {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px;
        background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white; border-radius: 16px; margin-bottom: 12px;
    }
    .amf-upload-text { font-size: 14px; font-weight: 800; color: #0F172A; }
    .amf-upload-hint { font-size: 11px; color: #64748B; margin-top: 4px; }

    /* Denominations */
    .amf-denoms {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 10px;
        border: 1.5px solid #E2E8F0; border-radius: 12px;
        background: #F8FAFC; min-height: 60px; align-items: center;
    }
    .amf-denom-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px 6px 12px;
        background: white; border: 1.5px solid #E2E8F0;
        border-radius: 9999px;
        font-size: 13px; font-weight: 700; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .amf-denom-chip button {
        width: 18px; height: 18px; border: 0;
        background: #FEE2E2; color: #B91C1C;
        border-radius: 50%; cursor: pointer; padding: 0;
        font-size: 14px; font-weight: 800;
    }
    .amf-denom-add { display: inline-flex; align-items: center; gap: 6px; }
    .amf-denom-add input {
        width: 130px; padding: 8px 12px;
        border: 1.5px solid #CBD5E1; border-radius: 9999px;
        font-size: 13px; background: white; outline: none;
    }
    .amf-denom-add input:focus { border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.15); }
    .amf-denom-add button {
        padding: 8px 14px;
        background: #44A08D; color: white;
        border: 0; border-radius: 9999px;
        font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .amf-denom-quick { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
    .amf-denom-quick button {
        font-size: 11px; font-weight: 700; color: #44A08D;
        background: white; border: 1px solid #BBF7D0;
        padding: 5px 10px; border-radius: 9999px; cursor: pointer;
    }

    /* Toggle */
    .amf-toggle {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px;
        background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px;
        cursor: pointer;
    }
    .amf-toggle:hover { background: #F1F5F9; }
    .amf-toggle-label { font-weight: 700; color: #0F172A; display: block; font-size: 13px; }
    .amf-toggle-hint { font-size: 11px; color: #64748B; margin-top: 2px; }
    .amf-switch {
        position: relative; width: 44px; height: 24px;
        background: #CBD5E1; border-radius: 9999px; flex-shrink: 0;
    }
    .amf-switch::after {
        content: ''; position: absolute;
        top: 2px; left: 2px; width: 20px; height: 20px;
        background: white; border-radius: 50%;
        transition: transform .15s;
        box-shadow: 0 2px 4px rgba(0,0,0,.2);
    }
    .amf-toggle input { display: none; }
    .amf-toggle input:checked ~ .amf-switch { background: #44A08D; }
    .amf-toggle input:checked ~ .amf-switch::after { transform: translateX(20px); }

    /* Submit */
    .amf-submit {
        display: flex; gap: 10px; justify-content: flex-end;
        margin-top: 18px;
    }
    .amf-btn {
        padding: 12px 24px;
        border: 0; border-radius: 12px;
        font-size: 13px; font-weight: 800;
        cursor: pointer; font-family: inherit;
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
    }
    .amf-btn--cancel { background: #F1F5F9; color: #334155; }
    .amf-btn--save {
        background: linear-gradient(135deg,#44A08D,#4ECDC4); color: white;
        box-shadow: 0 6px 16px -4px rgba(78,205,196,.45);
    }
</style>

<div class="amf-wrap"
     x-data="adminMerchantCardForm({
        initialDenoms: {{ json_encode(old('denominations', $card->denominations ?? [])) }},
        initialVisual: @js($card->visual_url ? asset($card->visual_url) : null),
        initialCustom: {{ old('allow_custom_amount', $card->allow_custom_amount) ? 'true' : 'false' }},
     })">

    <div class="amf-hero">
        <div class="amf-hero-glow"></div>
        <a href="{{ route('admin.merchant-cards.index') }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux cartes
        </a>
        <h1>{{ $isEdit ? 'Modifier la carte' : 'Nouvelle carte locale' }}</h1>
        <p>{{ $isEdit
            ? 'Mets à jour les caractéristiques de cette carte. Si tu décoches « Active » elle sera retirée de /gabon.'
            : 'Crée une carte-cadeau Carte Gabon pour un marchand approuvé. Les boutiques la vendront depuis leur espace.' }}</p>
    </div>

    @if($errors->any())
        <div class="amf-errors">
            <strong>Corrige les erreurs suivantes :</strong>
            <ul>
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.merchant-cards.update', $card) : route('admin.merchant-cards.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Marchand --}}
        <div class="amf-section">
            <div class="amf-section-head">
                <div class="amf-step">1</div>
                <div>
                    <h2 class="amf-section-title">Marchand & identité</h2>
                    <p class="amf-section-hint">Pour quel marchand cette carte est-elle créée ? La carte apparaîtra sur la fiche /gabon/marchand/{slug}.</p>
                </div>
            </div>

            <div class="amf-field">
                <label class="amf-label" for="reseller_id">Marchand <span class="amf-label-req">*</span></label>
                <select id="reseller_id" name="reseller_id" class="amf-select" required>
                    <option value="">— Choisir un marchand —</option>
                    @foreach($merchants as $m)
                        <option value="{{ $m['id'] }}" {{ (int) old('reseller_id', $card->reseller_id) === $m['id'] ? 'selected' : '' }}>
                            {{ $m['label'] }}{{ $m['kyc_ok'] ? '' : ' ⚠ KYC non approuvé' }}
                        </option>
                    @endforeach
                </select>
                <p class="amf-hint">Si le marchand n'est pas KYC approuvé, sa carte ne sera pas visible sur /gabon même si tu l'actives.</p>
                @error('reseller_id') <p class="amf-error">{{ $message }}</p> @enderror
            </div>

            <div class="amf-field">
                <label class="amf-label" for="name">Nom de la carte <span class="amf-label-req">*</span></label>
                <input type="text" id="name" name="name" class="amf-input"
                       value="{{ old('name', $card->name) }}"
                       placeholder="Ex : Carte cadeau Hôtel Le Méridien" required maxlength="120">
                @error('name') <p class="amf-error">{{ $message }}</p> @enderror
            </div>

            <div class="amf-field">
                <label class="amf-label" for="category">Catégorie <span class="amf-label-req">*</span></label>
                <select id="category" name="category" class="amf-select" required>
                    <option value="">— Choisir —</option>
                    @foreach($categories as $slug => $label)
                        <option value="{{ $slug }}" {{ old('category', $card->category) === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="amf-error">{{ $message }}</p> @enderror
            </div>

            <div class="amf-field">
                <label class="amf-label" for="description">Description</label>
                <textarea id="description" name="description" class="amf-textarea" rows="3" maxlength="2000">{{ old('description', $card->description) }}</textarea>
                @error('description') <p class="amf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Visuel --}}
        <div class="amf-section">
            <div class="amf-section-head">
                <div class="amf-step">2</div>
                <div>
                    <h2 class="amf-section-title">Visuel</h2>
                    <p class="amf-section-hint">L'image affichée sur le catalogue et au moment de l'envoi. JPG/PNG/WebP, 3 Mo max.</p>
                </div>
            </div>

            <label class="amf-upload">
                <input type="file" name="visual" accept="image/jpeg,image/png,image/webp" @change="onFileChange($event)">
                <template x-if="visualPreview">
                    <img :src="visualPreview" class="amf-upload-preview" alt="Aperçu">
                </template>
                <template x-if="!visualPreview">
                    <div>
                        <div class="amf-upload-ic">
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="amf-upload-text">Clique pour choisir un visuel</div>
                        <div class="amf-upload-hint">1550 × 1000 px recommandé · max 3 Mo</div>
                    </div>
                </template>
            </label>
            @error('visual') <p class="amf-error">{{ $message }}</p> @enderror
        </div>

        {{-- Montants --}}
        <div class="amf-section">
            <div class="amf-section-head">
                <div class="amf-step">3</div>
                <div>
                    <h2 class="amf-section-title">Montants</h2>
                    <p class="amf-section-hint">Dénominations affichées au client (FCFA).</p>
                </div>
            </div>

            <div class="amf-field">
                <label class="amf-label">Dénominations <span class="amf-label-req">*</span></label>
                <div class="amf-denoms">
                    <template x-for="(d, i) in denoms" :key="i">
                        <div class="amf-denom-chip">
                            <span x-text="formatXAF(d)"></span>
                            <input type="hidden" name="denominations[]" :value="d">
                            <button type="button" @click="removeDenom(i)">×</button>
                        </div>
                    </template>
                    <span x-show="denoms.length === 0" style="color:#94A3B8;font-size:12px;padding-left:6px;">Ajoute au moins un montant ci-dessous.</span>
                </div>
                <div class="amf-denom-add" style="margin-top:10px;">
                    <input type="number" x-model.number="newDenom" min="500" max="1000000" step="500" placeholder="Ex : 15000"
                           @keydown.enter.prevent="addDenom()">
                    <button type="button" @click="addDenom()">+ Ajouter</button>
                </div>
                <div class="amf-denom-quick">
                    <span style="font-size:11px;color:#94A3B8;align-self:center;margin-right:4px;font-weight:600;">Rapide :</span>
                    <template x-for="v in [5000,10000,15000,25000,50000,100000]" :key="v">
                        <button type="button" @click="quickAdd(v)" x-text="formatXAF(v)" :disabled="denoms.includes(v)" :style="denoms.includes(v) ? 'opacity:.4;cursor:default;' : ''"></button>
                    </template>
                </div>
                @error('denominations') <p class="amf-error">{{ $message }}</p> @enderror
                @error('denominations.*') <p class="amf-error">{{ $message }}</p> @enderror
            </div>

            <div class="amf-field">
                <label class="amf-toggle">
                    <div>
                        <span class="amf-toggle-label">Autoriser un montant libre</span>
                        <span class="amf-toggle-hint">Le client peut saisir son propre montant (ex : 7 500 F).</span>
                    </div>
                    <input type="checkbox" name="allow_custom_amount" value="1" x-model="allowCustom">
                    <span class="amf-switch"></span>
                </label>
            </div>

            <div x-show="allowCustom" x-transition class="amf-row">
                <div class="amf-field">
                    <label class="amf-label" for="min_amount">Min (FCFA)</label>
                    <input type="number" id="min_amount" name="min_amount" class="amf-input"
                           value="{{ old('min_amount', $card->min_amount) }}"
                           min="500" max="1000000" step="500">
                    @error('min_amount') <p class="amf-error">{{ $message }}</p> @enderror
                </div>
                <div class="amf-field">
                    <label class="amf-label" for="max_amount">Max (FCFA)</label>
                    <input type="number" id="max_amount" name="max_amount" class="amf-input"
                           value="{{ old('max_amount', $card->max_amount) }}"
                           min="500" max="1000000" step="500">
                    @error('max_amount') <p class="amf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Validité + activation --}}
        <div class="amf-section">
            <div class="amf-section-head">
                <div class="amf-step">4</div>
                <div>
                    <h2 class="amf-section-title">Validité & activation</h2>
                    <p class="amf-section-hint">Durée + statut de publication.</p>
                </div>
            </div>

            <div class="amf-row">
                <div class="amf-field">
                    <label class="amf-label" for="validity_months">Durée de validité (mois) <span class="amf-label-req">*</span></label>
                    <input type="number" id="validity_months" name="validity_months" class="amf-input"
                           value="{{ old('validity_months', $card->validity_months ?? 12) }}"
                           min="1" max="60" required>
                    @error('validity_months') <p class="amf-error">{{ $message }}</p> @enderror
                </div>
                <div class="amf-field" style="display:flex;align-items:flex-end;">
                    <label class="amf-toggle" style="width:100%;">
                        <div>
                            <span class="amf-toggle-label">Active immédiatement</span>
                            <span class="amf-toggle-hint">Coche pour publier directement sur /gabon.</span>
                        </div>
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $card->is_active) ? 'checked' : '' }}>
                        <span class="amf-switch"></span>
                    </label>
                </div>
            </div>

            <div class="amf-field">
                <label class="amf-label" for="terms_conditions">Conditions d'utilisation</label>
                <textarea id="terms_conditions" name="terms_conditions" class="amf-textarea" rows="4" maxlength="5000">{{ old('terms_conditions', $card->terms_conditions) }}</textarea>
                @error('terms_conditions') <p class="amf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="amf-submit">
            <a href="{{ route('admin.merchant-cards.index') }}" class="amf-btn amf-btn--cancel">Annuler</a>
            <button type="submit" class="amf-btn amf-btn--save">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $isEdit ? 'Enregistrer' : 'Créer la carte' }}
            </button>
        </div>
    </form>
</div>

<script>
    function adminMerchantCardForm(opts) {
        return {
            denoms: (opts.initialDenoms || []).map(Number).filter(n => n > 0).sort((a,b) => a-b),
            newDenom: null,
            allowCustom: !!opts.initialCustom,
            visualPreview: opts.initialVisual || null,

            formatXAF(n) { return Number(n).toLocaleString('fr-FR') + ' F'; },
            addDenom() {
                const v = parseInt(this.newDenom, 10);
                if (!v || v < 500) return;
                if (this.denoms.includes(v)) { this.newDenom = null; return; }
                if (this.denoms.length >= 10) { alert('10 max'); return; }
                this.denoms.push(v); this.denoms.sort((a,b) => a-b);
                this.newDenom = null;
            },
            quickAdd(v) {
                if (this.denoms.includes(v) || this.denoms.length >= 10) return;
                this.denoms.push(v); this.denoms.sort((a,b) => a-b);
            },
            removeDenom(i) { this.denoms.splice(i, 1); },
            onFileChange(e) {
                const f = e.target.files && e.target.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = ev => this.visualPreview = ev.target.result;
                reader.readAsDataURL(f);
            },
        };
    }
</script>
