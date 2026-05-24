{{--
    Shared form partial (create + edit)
    Expects:
      - $card       (MerchantCard instance — empty pour create)
      - $categories (assoc array slug => label)
      - $isEdit     (bool)
--}}
<style>
    .mcf-wrap { max-width: 900px; margin: 0 auto; padding: 20px 16px 80px; }

    .mcf-head { margin-bottom: 18px; }
    .mcf-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700; color: #64748B; text-decoration: none;
        margin-bottom: 8px;
    }
    .mcf-back:hover { color: #44A08D; }
    .mcf-back svg { width: 14px; height: 14px; }
    .mcf-head h1 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;
    }
    .mcf-head p { margin: 4px 0 0; color: #64748B; font-size: 13px; }

    /* ----- Errors ----- */
    .mcf-errors {
        background: #FEE2E2; color: #991B1B;
        padding: 12px 14px; border-radius: 12px;
        margin-bottom: 16px; font-size: 13px;
    }
    .mcf-errors strong { display: block; margin-bottom: 4px; font-weight: 800; }
    .mcf-errors ul { margin: 0; padding-left: 18px; }

    /* ----- Section card ----- */
    .mcf-section {
        background: white;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
        margin-bottom: 16px;
    }
    .mcf-section-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        margin: 0 0 4px;
        text-transform: uppercase; letter-spacing: .06em;
    }
    .mcf-section-hint {
        font-size: 12px; color: #64748B; margin: 0 0 16px; line-height: 1.5;
    }

    /* ----- Field ----- */
    .mcf-field { margin-bottom: 14px; }
    .mcf-field:last-child { margin-bottom: 0; }
    .mcf-label {
        display: block;
        font-size: 12px; font-weight: 700; color: #334155;
        margin-bottom: 6px;
    }
    .mcf-label-req { color: #DC2626; }
    .mcf-input, .mcf-select, .mcf-textarea {
        width: 100%;
        padding: 10px 12px;
        font-size: 14px;
        font-family: inherit;
        color: #0F172A;
        background: white;
        border: 1.5px solid #E2E8F0;
        border-radius: 10px;
        transition: border-color .15s, box-shadow .15s;
    }
    .mcf-input:focus, .mcf-select:focus, .mcf-textarea:focus {
        outline: 0;
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,.15);
    }
    .mcf-textarea { resize: vertical; min-height: 80px; }
    .mcf-error { font-size: 11px; color: #DC2626; margin-top: 4px; font-weight: 600; }
    .mcf-hint  { font-size: 11px; color: #94A3B8; margin-top: 4px; }

    .mcf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .mcf-row { grid-template-columns: 1fr; } }

    /* ----- Visual upload ----- */
    .mcf-upload {
        position: relative;
        border: 2px dashed #CBD5E1;
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }
    .mcf-upload:hover { border-color: #44A08D; background: #F0FDFA; }
    .mcf-upload input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .mcf-upload-preview {
        display: block;
        margin: 0 auto 12px;
        max-width: 280px;
        aspect-ratio: 1.55;
        object-fit: cover;
        border-radius: 10px;
        background: #F1F5F9;
    }
    .mcf-upload-empty {
        display: inline-flex; align-items: center; justify-content: center;
        width: 48px; height: 48px;
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        color: #44A08D;
        border-radius: 50%; margin-bottom: 10px;
    }
    .mcf-upload-empty svg { width: 22px; height: 22px; }
    .mcf-upload-text { font-size: 13px; font-weight: 700; color: #334155; }
    .mcf-upload-hint { font-size: 11px; color: #94A3B8; margin-top: 3px; }

    /* ----- Denominations chips ----- */
    .mcf-denoms {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 8px;
        border: 1.5px solid #E2E8F0; border-radius: 12px;
        background: #F8FAFC;
        min-height: 56px;
        align-items: center;
    }
    .mcf-denom-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 10px 6px 12px;
        background: white;
        border: 1.5px solid #E2E8F0;
        border-radius: 9999px;
        font-size: 13px; font-weight: 700; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .mcf-denom-chip button {
        display: inline-flex; align-items: center; justify-content: center;
        width: 18px; height: 18px; border: 0; background: #FEE2E2; color: #B91C1C;
        border-radius: 50%; cursor: pointer; padding: 0;
        font-size: 14px; font-weight: 800; line-height: 1;
    }
    .mcf-denom-add {
        display: inline-flex; align-items: center; gap: 6px;
    }
    .mcf-denom-add input {
        width: 110px;
        padding: 8px 10px;
        border: 1.5px solid #CBD5E1;
        border-radius: 9999px;
        font-size: 13px;
        background: white;
        font-variant-numeric: tabular-nums;
    }
    .mcf-denom-add button {
        padding: 8px 12px;
        background: #44A08D; color: white;
        border: 0; border-radius: 9999px;
        font-size: 12px; font-weight: 700;
        cursor: pointer;
    }
    .mcf-denom-quick {
        display: flex; gap: 6px; flex-wrap: wrap;
        margin-top: 8px;
    }
    .mcf-denom-quick button {
        font-size: 11px; font-weight: 600; color: #44A08D;
        background: white; border: 1px solid #BBF7D0;
        padding: 4px 9px; border-radius: 9999px;
        cursor: pointer;
    }
    .mcf-denom-quick button:hover { background: #ECFDF5; }

    /* ----- Toggle switch ----- */
    .mcf-toggle {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 14px;
        background: #F8FAFC;
        border: 1.5px solid #E2E8F0;
        border-radius: 12px;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .mcf-toggle:hover { background: #F1F5F9; }
    .mcf-toggle-label-wrap { font-size: 13px; }
    .mcf-toggle-label { font-weight: 700; color: #0F172A; display: block; }
    .mcf-toggle-hint { font-size: 11px; color: #64748B; margin-top: 2px; }
    .mcf-switch {
        position: relative;
        width: 44px; height: 24px;
        background: #CBD5E1;
        border-radius: 9999px;
        transition: background .15s;
        flex-shrink: 0;
    }
    .mcf-switch::after {
        content: ''; position: absolute;
        top: 2px; left: 2px;
        width: 20px; height: 20px;
        background: white; border-radius: 50%;
        transition: transform .15s;
        box-shadow: 0 2px 4px rgba(0,0,0,.2);
    }
    .mcf-toggle input { display: none; }
    .mcf-toggle input:checked ~ .mcf-switch { background: #44A08D; }
    .mcf-toggle input:checked ~ .mcf-switch::after { transform: translateX(20px); }

    /* ----- Submit bar ----- */
    .mcf-submit {
        display: flex; gap: 10px; justify-content: flex-end;
        padding-top: 4px;
    }
    .mcf-btn {
        padding: 11px 22px;
        font-size: 13px; font-weight: 700;
        border-radius: 12px;
        text-decoration: none; border: 0; cursor: pointer;
        transition: transform .15s, box-shadow .15s;
    }
    .mcf-btn--cancel { background: #F1F5F9; color: #334155; }
    .mcf-btn--cancel:hover { background: #E2E8F0; }
    .mcf-btn--save {
        background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white;
        box-shadow: 0 6px 16px -4px rgba(78,205,196,.45),
                    inset 0 1px 0 rgba(255,255,255,.3);
    }
    .mcf-btn--save:hover { transform: translateY(-1px); }
</style>

<div class="mcf-wrap"
     x-data="merchantCardForm({
        initialDenoms: {{ json_encode(old('denominations', $card->denominations ?? [])) }},
        initialVisual: @js($card->visual_url ? \Storage::url($card->visual_url) : null),
        initialCustom: {{ old('allow_custom_amount', $card->allow_custom_amount) ? 'true' : 'false' }},
     })">

    <div class="mcf-head">
        <a href="{{ route('vendor.merchant-cards.index') }}" class="mcf-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à la liste
        </a>
        <h1>{{ $isEdit ? 'Modifier la carte' : 'Nouvelle carte-cadeau' }}</h1>
        <p>{{ $isEdit
            ? 'Toute modification d\'une carte active la repassera en attente de validation admin.'
            : 'Crée le visuel et les montants. La carte sera publiée après validation par l\'équipe KardAfrica.' }}</p>
    </div>

    @if($errors->any())
        <div class="mcf-errors">
            <strong>Corrige les erreurs suivantes :</strong>
            <ul>
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('vendor.merchant-cards.update', $card) : route('vendor.merchant-cards.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- =========== SECTION 1 : Identité =========== --}}
        <div class="mcf-section">
            <h2 class="mcf-section-title">1. Identité de la carte</h2>
            <p class="mcf-section-hint">Le nom et la catégorie qui s'afficheront aux clients sur Carte&nbsp;Gabon.</p>

            <div class="mcf-field">
                <label class="mcf-label" for="name">Nom de la carte <span class="mcf-label-req">*</span></label>
                <input type="text" id="name" name="name" class="mcf-input"
                       value="{{ old('name', $card->name) }}"
                       placeholder="Ex : Carte cadeau Hôtel Le Méridien" required maxlength="120">
                @error('name') <p class="mcf-error">{{ $message }}</p> @enderror
            </div>

            <div class="mcf-field">
                <label class="mcf-label" for="category">Catégorie <span class="mcf-label-req">*</span></label>
                <select id="category" name="category" class="mcf-select" required>
                    <option value="">— Choisir —</option>
                    @foreach($categories as $slug => $label)
                        <option value="{{ $slug }}" {{ old('category', $card->category) === $slug ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="mcf-error">{{ $message }}</p> @enderror
            </div>

            <div class="mcf-field">
                <label class="mcf-label" for="description">Description (optionnel)</label>
                <textarea id="description" name="description" class="mcf-textarea" rows="3" maxlength="2000"
                          placeholder="Décris en quelques mots ce que le client peut acheter avec cette carte.">{{ old('description', $card->description) }}</textarea>
                @error('description') <p class="mcf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- =========== SECTION 2 : Visuel =========== --}}
        <div class="mcf-section">
            <h2 class="mcf-section-title">2. Visuel de la carte</h2>
            <p class="mcf-section-hint">Image affichée sur le catalogue et au moment de l'envoi au destinataire. JPG/PNG/WebP, max 3&nbsp;Mo.</p>

            <label class="mcf-upload">
                <input type="file" name="visual" accept="image/jpeg,image/png,image/webp" @change="onFileChange($event)">
                <template x-if="visualPreview">
                    <img :src="visualPreview" class="mcf-upload-preview" alt="Aperçu">
                </template>
                <template x-if="!visualPreview">
                    <div>
                        <div class="mcf-upload-empty">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div class="mcf-upload-text">Clique pour choisir un visuel</div>
                        <div class="mcf-upload-hint">Format recommandé : 1550 × 1000 px</div>
                    </div>
                </template>
                <template x-if="visualPreview">
                    <div style="margin-top:8px;">
                        <span class="mcf-upload-text" style="color:#44A08D;">Changer le visuel</span>
                    </div>
                </template>
            </label>
            @error('visual') <p class="mcf-error">{{ $message }}</p> @enderror
        </div>

        {{-- =========== SECTION 3 : Montants =========== --}}
        <div class="mcf-section">
            <h2 class="mcf-section-title">3. Montants proposés</h2>
            <p class="mcf-section-hint">Les dénominations affichées au client (en FCFA). Tu peux aussi autoriser un montant libre.</p>

            <div class="mcf-field">
                <label class="mcf-label">Dénominations fixes <span class="mcf-label-req">*</span></label>
                <div class="mcf-denoms">
                    <template x-for="(d, i) in denoms" :key="i">
                        <div class="mcf-denom-chip">
                            <span x-text="formatXAF(d)"></span>
                            <input type="hidden" :name="'denominations[]'" :value="d">
                            <button type="button" @click="removeDenom(i)" title="Retirer">×</button>
                        </div>
                    </template>
                    <span x-show="denoms.length === 0" style="color:#94A3B8;font-size:12px;padding-left:6px;">Ajoute au moins un montant ci-dessous.</span>
                </div>
                <div class="mcf-denom-add" style="margin-top:10px;">
                    <input type="number" x-model.number="newDenom" min="500" max="1000000" step="500" placeholder="Ex : 15000">
                    <button type="button" @click="addDenom()">+ Ajouter</button>
                </div>
                <div class="mcf-denom-quick">
                    <span style="font-size:11px;color:#94A3B8;align-self:center;margin-right:4px;">Rapide :</span>
                    <template x-for="v in [5000,10000,15000,25000,50000,100000]" :key="v">
                        <button type="button" @click="quickAdd(v)" x-text="formatXAF(v)"></button>
                    </template>
                </div>
                @error('denominations')   <p class="mcf-error">{{ $message }}</p> @enderror
                @error('denominations.*') <p class="mcf-error">{{ $message }}</p> @enderror
            </div>

            <div class="mcf-field">
                <label class="mcf-toggle" :class="allowCustom ? 'mcf-toggle--on' : ''">
                    <div class="mcf-toggle-label-wrap">
                        <span class="mcf-toggle-label">Autoriser un montant libre</span>
                        <span class="mcf-toggle-hint">Le client peut saisir son propre montant (ex : 7&nbsp;500&nbsp;F).</span>
                    </div>
                    <input type="checkbox" name="allow_custom_amount" value="1" x-model="allowCustom">
                    <span class="mcf-switch"></span>
                </label>
            </div>

            <div x-show="allowCustom" x-transition class="mcf-row">
                <div class="mcf-field">
                    <label class="mcf-label" for="min_amount">Montant minimum (FCFA)</label>
                    <input type="number" id="min_amount" name="min_amount" class="mcf-input"
                           value="{{ old('min_amount', $card->min_amount) }}"
                           min="500" max="1000000" step="500" placeholder="Ex : 1000">
                    @error('min_amount') <p class="mcf-error">{{ $message }}</p> @enderror
                </div>
                <div class="mcf-field">
                    <label class="mcf-label" for="max_amount">Montant maximum (FCFA)</label>
                    <input type="number" id="max_amount" name="max_amount" class="mcf-input"
                           value="{{ old('max_amount', $card->max_amount) }}"
                           min="500" max="1000000" step="500" placeholder="Ex : 200000">
                    @error('max_amount') <p class="mcf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- =========== SECTION 4 : Validité & conditions =========== --}}
        <div class="mcf-section">
            <h2 class="mcf-section-title">4. Validité & conditions</h2>
            <p class="mcf-section-hint">Combien de temps la carte reste utilisable + les conditions affichées au client.</p>

            <div class="mcf-row">
                <div class="mcf-field">
                    <label class="mcf-label" for="validity_months">Durée de validité (mois) <span class="mcf-label-req">*</span></label>
                    <input type="number" id="validity_months" name="validity_months" class="mcf-input"
                           value="{{ old('validity_months', $card->validity_months ?? 12) }}"
                           min="1" max="60" required>
                    <p class="mcf-hint">Standard : 12 mois. La carte expire automatiquement après ce délai.</p>
                    @error('validity_months') <p class="mcf-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mcf-field">
                <label class="mcf-label" for="terms_conditions">Conditions d'utilisation (optionnel)</label>
                <textarea id="terms_conditions" name="terms_conditions" class="mcf-textarea" rows="4" maxlength="5000"
                          placeholder="Ex : Utilisable uniquement en magasin, non remboursable, non échangeable contre de l'espèce.">{{ old('terms_conditions', $card->terms_conditions) }}</textarea>
                @error('terms_conditions') <p class="mcf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- =========== SUBMIT =========== --}}
        <div class="mcf-submit">
            <a href="{{ route('vendor.merchant-cards.index') }}" class="mcf-btn mcf-btn--cancel">Annuler</a>
            <button type="submit" class="mcf-btn mcf-btn--save">
                {{ $isEdit ? 'Enregistrer les modifications' : 'Créer la carte' }}
            </button>
        </div>
    </form>
</div>

<script>
    function merchantCardForm(opts) {
        return {
            denoms:        (opts.initialDenoms || []).map(Number).filter(n => n > 0).sort((a,b) => a-b),
            newDenom:      null,
            allowCustom:   !!opts.initialCustom,
            visualPreview: opts.initialVisual || null,

            formatXAF(n) {
                return Number(n).toLocaleString('fr-FR', { useGrouping: true }) + ' F';
            },
            addDenom() {
                const v = parseInt(this.newDenom, 10);
                if (!v || v < 500) return;
                if (this.denoms.includes(v)) { this.newDenom = null; return; }
                if (this.denoms.length >= 10) { alert('10 dénominations maximum.'); return; }
                this.denoms.push(v);
                this.denoms.sort((a,b) => a-b);
                this.newDenom = null;
            },
            quickAdd(v) {
                if (this.denoms.includes(v)) return;
                if (this.denoms.length >= 10) return;
                this.denoms.push(v);
                this.denoms.sort((a,b) => a-b);
            },
            removeDenom(i) {
                this.denoms.splice(i, 1);
            },
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
