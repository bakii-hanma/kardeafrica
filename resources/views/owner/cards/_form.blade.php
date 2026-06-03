{{--
    Formulaire propriétaire : créer / éditer une carte locale.
    Variables :
      - $card       (MerchantCard, vide pour create)
      - $categories (assoc array slug => label)
      - $isEdit     (bool)
--}}
<style>
    .ocf-wrap { padding: 0; font-family: 'Inter','Figtree',sans-serif; max-width: 980px; margin: 0 auto; }

    .ocf-errors {
        background: #FEE2E2; color: #991B1B;
        padding: 12px 14px; border-radius: 12px;
        margin-bottom: 16px; font-size: 13px;
    }
    .ocf-errors strong { display: block; margin-bottom: 4px; font-weight: 800; }
    .ocf-errors ul { margin: 0; padding-left: 18px; }

    .ocf-section {
        background: white; border: 1px solid #E2E8F0;
        border-radius: 16px; padding: 22px;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .ocf-section-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
    .ocf-step {
        flex-shrink: 0; width: 32px; height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 14px;
    }
    .ocf-section-title { margin: 0; font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A; line-height: 1.3; }
    .ocf-section-hint { font-size: 12px; color: #64748B; margin: 2px 0 0; }

    .ocf-field { margin-bottom: 14px; }
    .ocf-field:last-child { margin-bottom: 0; }
    .ocf-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .ocf-label-req { color: #DC2626; }
    .ocf-input, .ocf-select, .ocf-textarea {
        width: 100%; padding: 10px 12px;
        font-size: 14px; font-family: inherit; color: #0F172A;
        background: white; border: 1.5px solid #E2E8F0; border-radius: 10px;
        outline: none;
    }
    .ocf-input:focus, .ocf-select:focus, .ocf-textarea:focus {
        border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.15);
    }
    .ocf-textarea { resize: vertical; min-height: 80px; }
    .ocf-error { font-size: 11px; color: #DC2626; margin-top: 4px; font-weight: 600; }
    .ocf-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; }
    .ocf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .ocf-row { grid-template-columns: 1fr; } }

    .ocf-upload {
        display: block; position: relative;
        border: 2px dashed #CBD5E1; border-radius: 16px;
        padding: 24px 20px; text-align: center;
        cursor: pointer; background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
        transition: all .2s;
    }
    .ocf-upload:hover { border-color: #44A08D; background: #F0FDFA; }
    .ocf-upload input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .ocf-upload-preview {
        display: block; margin: 0 auto;
        max-width: 360px; width: 100%;
        aspect-ratio: 1.55; object-fit: cover;
        border-radius: 12px; background: #F1F5F9;
    }
    .ocf-upload-ic {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px;
        background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white; border-radius: 16px; margin-bottom: 12px;
    }
    .ocf-upload-text { font-size: 14px; font-weight: 800; color: #0F172A; }
    .ocf-upload-hint { font-size: 11px; color: #64748B; margin-top: 4px; }

    .ocf-denoms {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 10px;
        border: 1.5px solid #E2E8F0; border-radius: 12px;
        background: #F8FAFC; min-height: 60px; align-items: center;
    }
    .ocf-denom-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px 6px 12px;
        background: white; border: 1.5px solid #E2E8F0;
        border-radius: 9999px;
        font-size: 13px; font-weight: 700; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .ocf-denom-chip button {
        width: 18px; height: 18px; border: 0;
        background: #FEE2E2; color: #B91C1C;
        border-radius: 50%; cursor: pointer; padding: 0;
        font-size: 14px; font-weight: 800;
    }
    .ocf-denom-add { display: inline-flex; align-items: center; gap: 6px; }
    .ocf-denom-add input {
        width: 130px; padding: 8px 12px;
        border: 1.5px solid #CBD5E1; border-radius: 9999px;
        font-size: 13px; background: white; outline: none;
    }
    .ocf-denom-add button {
        padding: 8px 14px;
        background: #44A08D; color: white;
        border: 0; border-radius: 9999px;
        font-size: 12px; font-weight: 700; cursor: pointer;
    }
    .ocf-denom-quick { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
    .ocf-denom-quick button {
        font-size: 11px; font-weight: 700; color: #44A08D;
        background: white; border: 1px solid #BBF7D0;
        padding: 5px 10px; border-radius: 9999px; cursor: pointer;
    }

    .ocf-toggle {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px;
        background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px;
        cursor: pointer;
    }
    .ocf-toggle-label { font-weight: 700; color: #0F172A; display: block; font-size: 13px; }
    .ocf-toggle-hint { font-size: 11px; color: #64748B; margin-top: 2px; }
    .ocf-switch {
        position: relative; width: 44px; height: 24px;
        background: #CBD5E1; border-radius: 9999px; flex-shrink: 0;
    }
    .ocf-switch::after {
        content: ''; position: absolute;
        top: 2px; left: 2px; width: 20px; height: 20px;
        background: white; border-radius: 50%;
        transition: transform .15s;
        box-shadow: 0 2px 4px rgba(0,0,0,.2);
    }
    .ocf-toggle input { display: none; }
    .ocf-toggle input:checked ~ .ocf-switch { background: #44A08D; }
    .ocf-toggle input:checked ~ .ocf-switch::after { transform: translateX(20px); }

    .ocf-submit { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
    .ocf-btn {
        padding: 12px 24px;
        border: 0; border-radius: 12px;
        font-size: 13px; font-weight: 800;
        cursor: pointer; font-family: inherit;
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none;
    }
    .ocf-btn--cancel { background: #F1F5F9; color: #334155; }
    .ocf-btn--save {
        background: linear-gradient(135deg,#44A08D,#4ECDC4); color: white;
        box-shadow: 0 6px 16px -4px rgba(78,205,196,.45);
    }
    .ocf-info-card {
        background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
        border: 1px solid #BFDBFE; border-radius: 14px;
        padding: 14px 16px; font-size: 12px; color: #1E3A8A;
        margin-bottom: 14px;
    }
    .ocf-info-card strong { display: block; margin-bottom: 4px; font-weight: 800; font-size: 13px; }
</style>

<div class="ocf-wrap"
     x-data="ownerCardForm({
        initialDenoms: {{ json_encode(old('denominations', $card->denominations ?? [])) }},
        initialVisual: @js($card->visual_url ? asset($card->visual_url) : null),
        initialCustom: {{ old('allow_custom_amount', $card->allow_custom_amount) ? 'true' : 'false' }},
     })">

    @if(!$isEdit)
        <div class="ocf-info-card">
            <strong>📋 Ta carte sera en brouillon</strong>
            Une fois créée, elle reste invisible sur Kardafrica. Notre équipe la valide rapidement
            (commissions admin/vendeur fixées à ce moment-là), puis elle est publiée et achetable
            par les clients.
        </div>
    @endif

    @if($errors->any())
        <div class="ocf-errors">
            <strong>Corrige les erreurs suivantes :</strong>
            <ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('owner.card.update', $card) : route('owner.card.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Identité --}}
        <div class="ocf-section">
            <div class="ocf-section-head">
                <div class="ocf-step">1</div>
                <div>
                    <h2 class="ocf-section-title">Identité de la carte</h2>
                    <p class="ocf-section-hint">Le nom et la catégorie qui s'afficheront aux clients sur Kardafrica.</p>
                </div>
            </div>

            <div class="ocf-field">
                <label class="ocf-label" for="name">Nom de la carte <span class="ocf-label-req">*</span></label>
                <input type="text" id="name" name="name" class="ocf-input"
                       value="{{ old('name', $card->name) }}"
                       placeholder="Ex : Carte cadeau 10 000 FCFA" required maxlength="120">
                @error('name') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>

            <div class="ocf-field">
                <label class="ocf-label" for="category">Catégorie <span class="ocf-label-req">*</span></label>
                <select id="category" name="category" class="ocf-select" required>
                    <option value="">— Choisir —</option>
                    @foreach($categories as $slug => $label)
                        <option value="{{ $slug }}" {{ old('category', $card->category) === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>

            <div class="ocf-field">
                <label class="ocf-label" for="description">Description</label>
                <textarea id="description" name="description" class="ocf-textarea" rows="3" maxlength="2000">{{ old('description', $card->description) }}</textarea>
                @error('description') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Visuel --}}
        <div class="ocf-section">
            <div class="ocf-section-head">
                <div class="ocf-step">2</div>
                <div>
                    <h2 class="ocf-section-title">Visuel</h2>
                    <p class="ocf-section-hint">L'image affichée sur le catalogue. JPG/PNG/WebP, 3 Mo max.</p>
                </div>
            </div>

            <label class="ocf-upload">
                <input type="file" name="visual" accept="image/jpeg,image/png,image/webp" @change="onFileChange($event)">
                <template x-if="visualPreview">
                    <img :src="visualPreview" class="ocf-upload-preview" alt="Aperçu">
                </template>
                <template x-if="!visualPreview">
                    <div>
                        <div class="ocf-upload-ic">
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="ocf-upload-text">Choisir un visuel</div>
                        <div class="ocf-upload-hint">1550 × 1000 px recommandé · max 3 Mo</div>
                    </div>
                </template>
            </label>
            @error('visual') <p class="ocf-error">{{ $message }}</p> @enderror
        </div>

        {{-- Montants --}}
        <div class="ocf-section">
            <div class="ocf-section-head">
                <div class="ocf-step">3</div>
                <div>
                    <h2 class="ocf-section-title">Montants</h2>
                    <p class="ocf-section-hint">Dénominations affichées au client (FCFA).</p>
                </div>
            </div>

            <div class="ocf-field">
                <label class="ocf-label">Dénominations <span class="ocf-label-req">*</span></label>
                <div class="ocf-denoms">
                    <template x-for="(d, i) in denoms" :key="i">
                        <div class="ocf-denom-chip">
                            <span x-text="formatXAF(d)"></span>
                            <input type="hidden" name="denominations[]" :value="d">
                            <button type="button" @click="removeDenom(i)">×</button>
                        </div>
                    </template>
                    <span x-show="denoms.length === 0" style="color:#94A3B8;font-size:12px;padding-left:6px;">Ajoute au moins un montant ci-dessous.</span>
                </div>
                <div class="ocf-denom-add" style="margin-top:10px;">
                    <input type="number" x-model.number="newDenom" min="500" max="1000000" step="500" placeholder="Ex : 15000"
                           @keydown.enter.prevent="addDenom()">
                    <button type="button" @click="addDenom()">+ Ajouter</button>
                </div>
                <div class="ocf-denom-quick">
                    <span style="font-size:11px;color:#94A3B8;align-self:center;margin-right:4px;font-weight:600;">Rapide :</span>
                    <template x-for="v in [5000,10000,15000,25000,50000,100000]" :key="v">
                        <button type="button" @click="quickAdd(v)" x-text="formatXAF(v)" :disabled="denoms.includes(v)" :style="denoms.includes(v) ? 'opacity:.4;cursor:default;' : ''"></button>
                    </template>
                </div>
                @error('denominations') <p class="ocf-error">{{ $message }}</p> @enderror
                @error('denominations.*') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>

            <div class="ocf-field">
                <label class="ocf-toggle">
                    <div>
                        <span class="ocf-toggle-label">Autoriser un montant libre</span>
                        <span class="ocf-toggle-hint">Le client peut saisir son propre montant (ex : 7 500 F).</span>
                    </div>
                    <input type="checkbox" name="allow_custom_amount" value="1" x-model="allowCustom">
                    <span class="ocf-switch"></span>
                </label>
            </div>

            <div x-show="allowCustom" x-transition class="ocf-row">
                <div class="ocf-field">
                    <label class="ocf-label" for="min_amount">Min (FCFA)</label>
                    <input type="number" id="min_amount" name="min_amount" class="ocf-input"
                           value="{{ old('min_amount', $card->min_amount) }}"
                           min="500" max="1000000" step="500">
                    @error('min_amount') <p class="ocf-error">{{ $message }}</p> @enderror
                </div>
                <div class="ocf-field">
                    <label class="ocf-label" for="max_amount">Max (FCFA)</label>
                    <input type="number" id="max_amount" name="max_amount" class="ocf-input"
                           value="{{ old('max_amount', $card->max_amount) }}"
                           min="500" max="1000000" step="500">
                    @error('max_amount') <p class="ocf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Validité --}}
        <div class="ocf-section">
            <div class="ocf-section-head">
                <div class="ocf-step">4</div>
                <div>
                    <h2 class="ocf-section-title">Validité</h2>
                    <p class="ocf-section-hint">Durée de validité après achat client.</p>
                </div>
            </div>

            <div class="ocf-field">
                <label class="ocf-label" for="validity_months">Durée de validité (mois) <span class="ocf-label-req">*</span></label>
                <input type="number" id="validity_months" name="validity_months" class="ocf-input"
                       value="{{ old('validity_months', $card->validity_months ?? 12) }}"
                       min="1" max="60" required>
                @error('validity_months') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>

            <div class="ocf-field">
                <label class="ocf-label" for="terms_conditions">Conditions d'utilisation</label>
                <textarea id="terms_conditions" name="terms_conditions" class="ocf-textarea" rows="4" maxlength="5000">{{ old('terms_conditions', $card->terms_conditions) }}</textarea>
                @error('terms_conditions') <p class="ocf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @if($isEdit)
            <div class="ocf-info-card" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border-color:#FCD34D;color:#92400E;">
                <strong>⚠️ Re-validation après modification</strong>
                Si cette carte est déjà publiée, l'enregistrer va la repasser en brouillon. L'admin
                devra la re-valider avant qu'elle redevienne visible sur Kardafrica.
            </div>
        @endif

        <div class="ocf-submit">
            <a href="{{ route('owner.cards') }}" class="ocf-btn ocf-btn--cancel">Annuler</a>
            <button type="submit" class="ocf-btn ocf-btn--save">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $isEdit ? 'Enregistrer' : 'Créer la carte' }}
            </button>
        </div>
    </form>
</div>

<script>
    function ownerCardForm(opts) {
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
