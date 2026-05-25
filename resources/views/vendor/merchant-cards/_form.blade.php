{{--
    Shared form partial (create + edit)
    Expects:
      - $card       (MerchantCard instance — empty pour create)
      - $categories (assoc array slug => label)
      - $isEdit     (bool)
      - $reseller
--}}
<style>
    .mcf-wrap { max-width: 1180px; margin: 0 auto; padding: 16px 16px 100px; }

    /* ============ Hero ============ */
    .mcf-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        color: white;
        border-radius: 18px;
        padding: 22px 26px;
        margin-bottom: 18px;
        position: relative;
        overflow: hidden;
    }
    .mcf-hero::before {
        content: ''; position: absolute;
        top: -40%; right: -10%;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(78,205,196,.25), transparent 70%);
        pointer-events: none;
    }
    .mcf-crumb {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,.55);
        text-decoration: none;
        margin-bottom: 6px;
        text-transform: uppercase; letter-spacing: .12em;
    }
    .mcf-crumb:hover { color: #5EEAD4; }
    .mcf-crumb svg { width: 12px; height: 12px; }
    .mcf-hero h1 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 24px; font-weight: 800;
        letter-spacing: -0.02em;
    }
    .mcf-hero p {
        margin: 6px 0 0;
        color: rgba(255,255,255,.7);
        font-size: 13px;
        max-width: 580px;
        line-height: 1.5;
    }
    @media (min-width:640px) { .mcf-hero h1 { font-size: 28px; } }

    /* ============ Layout 2 colonnes ============ */
    .mcf-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: 1fr;
    }
    @media (min-width: 960px) {
        .mcf-grid { grid-template-columns: minmax(0,1fr) 360px; }
    }

    /* ============ Errors ============ */
    .mcf-errors {
        background: linear-gradient(135deg,#FEE2E2,#FECACA);
        color: #991B1B;
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 16px; font-size: 13px;
        border: 1px solid rgba(220,38,38,.25);
    }
    .mcf-errors strong { display: block; margin-bottom: 4px; font-weight: 800; }
    .mcf-errors ul { margin: 0; padding-left: 18px; }

    /* ============ Section card ============ */
    .mcf-section {
        background: white;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
        margin-bottom: 14px;
    }
    .mcf-section-head {
        display: flex; align-items: flex-start; gap: 12px;
        margin-bottom: 16px;
    }
    .mcf-step {
        flex-shrink: 0;
        width: 32px; height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 14px;
        box-shadow: 0 4px 10px -3px rgba(78,205,196,.45),
                    inset 0 1px 0 rgba(255,255,255,.3);
    }
    .mcf-section-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        margin: 0;
        line-height: 1.3;
    }
    .mcf-section-hint {
        font-size: 12px; color: #64748B; margin: 2px 0 0; line-height: 1.5;
    }

    /* ============ Field ============ */
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

    /* ============ Visual upload ============ */
    .mcf-upload {
        position: relative;
        border: 2px dashed #CBD5E1;
        border-radius: 14px;
        padding: 24px 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        background: #F8FAFC;
    }
    .mcf-upload:hover { border-color: #44A08D; background: #F0FDFA; }
    .mcf-upload input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .mcf-upload-preview {
        display: block;
        margin: 0 auto 10px;
        max-width: 320px; width: 100%;
        aspect-ratio: 1.55;
        object-fit: cover;
        border-radius: 12px;
        background: #F1F5F9;
        box-shadow: 0 6px 20px -6px rgba(15,23,42,.20);
    }
    .mcf-upload-empty {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px;
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        color: #44A08D;
        border-radius: 50%; margin-bottom: 10px;
    }
    .mcf-upload-empty svg { width: 26px; height: 26px; }
    .mcf-upload-text { font-size: 14px; font-weight: 800; color: #0F172A; }
    .mcf-upload-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; }
    .mcf-upload-cta {
        display: inline-block;
        margin-top: 8px;
        font-size: 12px; font-weight: 700; color: #44A08D;
        padding: 5px 12px;
        background: white;
        border: 1px solid #BBF7D0;
        border-radius: 9999px;
    }

    /* ============ Denominations chips ============ */
    .mcf-denoms {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 10px;
        border: 1.5px solid #E2E8F0; border-radius: 12px;
        background: #F8FAFC;
        min-height: 60px;
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
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .mcf-denom-chip button {
        display: inline-flex; align-items: center; justify-content: center;
        width: 18px; height: 18px; border: 0; background: #FEE2E2; color: #B91C1C;
        border-radius: 50%; cursor: pointer; padding: 0;
        font-size: 14px; font-weight: 800; line-height: 1;
    }
    .mcf-denom-add { display: inline-flex; align-items: center; gap: 6px; }
    .mcf-denom-add input {
        width: 130px;
        padding: 8px 12px;
        border: 1.5px solid #CBD5E1;
        border-radius: 9999px;
        font-size: 13px;
        background: white;
        font-variant-numeric: tabular-nums;
    }
    .mcf-denom-add input:focus {
        outline: 0; border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,.15);
    }
    .mcf-denom-add button {
        padding: 8px 14px;
        background: #44A08D; color: white;
        border: 0; border-radius: 9999px;
        font-size: 12px; font-weight: 700;
        cursor: pointer;
        transition: background .15s;
    }
    .mcf-denom-add button:hover { background: #0F4F44; }
    .mcf-denom-quick { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
    .mcf-denom-quick button {
        font-size: 11px; font-weight: 700; color: #44A08D;
        background: white; border: 1px solid #BBF7D0;
        padding: 5px 10px; border-radius: 9999px;
        cursor: pointer;
        font-variant-numeric: tabular-nums;
    }
    .mcf-denom-quick button:hover { background: #ECFDF5; }

    /* ============ Toggle switch ============ */
    .mcf-toggle {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px;
        background: #F8FAFC;
        border: 1.5px solid #E2E8F0;
        border-radius: 12px;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .mcf-toggle:hover { background: #F1F5F9; }
    .mcf-toggle-label-wrap { font-size: 13px; }
    .mcf-toggle-label { font-weight: 700; color: #0F172A; display: block; }
    .mcf-toggle-hint  { font-size: 11px; color: #64748B; margin-top: 2px; }
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

    /* ============ Live preview panel ============ */
    .mcf-aside {
        position: relative;
    }
    @media (min-width: 960px) {
        .mcf-aside { position: sticky; top: 80px; align-self: start; }
    }
    .mcf-aside-card {
        background: white;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
    }
    .mcf-aside-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 12px; font-weight: 800; color: #64748B;
        margin: 0 0 12px;
        text-transform: uppercase; letter-spacing: .08em;
    }
    .mcf-aside-title svg { width: 14px; height: 14px; vertical-align: -2px; margin-right: 5px; color: #44A08D; }

    /* Live card preview — mimic the merchant gift card */
    .mcp {
        position: relative;
        aspect-ratio: 1.55;
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(135deg,#1E293B,#0F4F44);
        color: white;
        box-shadow: 0 18px 40px -12px rgba(15,23,42,.40),
                    inset 0 1px 0 rgba(255,255,255,.15);
        margin-bottom: 14px;
    }
    .mcp-bg {
        position: absolute; inset: 0;
        background-size: cover; background-position: center;
        opacity: .85;
        transition: opacity .25s;
    }
    .mcp-grad {
        position: absolute; inset: 0;
        background: linear-gradient(160deg, rgba(15,23,42,.20) 0%, rgba(15,23,42,.85) 100%);
        pointer-events: none;
    }
    .mcp-corner {
        position: absolute; top: 12px; left: 14px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 9px;
        letter-spacing: .14em; text-transform: uppercase;
        color: rgba(255,255,255,.85);
    }
    .mcp-chip {
        position: absolute; top: 12px; right: 14px;
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 9px;
        background: rgba(255,255,255,.15);
        border-radius: 9999px;
        font-size: 9px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .08em;
        backdrop-filter: blur(6px);
    }
    .mcp-bottom {
        position: absolute; bottom: 14px; left: 14px; right: 14px;
    }
    .mcp-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        line-height: 1.2;
        margin: 0 0 6px;
        text-shadow: 0 1px 4px rgba(0,0,0,.4);
    }
    .mcp-denoms {
        display: flex; flex-wrap: wrap; gap: 4px;
    }
    .mcp-denoms span {
        padding: 3px 7px;
        background: rgba(255,255,255,.18);
        border-radius: 5px;
        font-size: 10px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        backdrop-filter: blur(4px);
    }
    .mcp-meta {
        display: flex; justify-content: space-between;
        font-size: 11px; color: #64748B; padding-top: 12px;
        border-top: 1px solid #F1F5F9;
    }
    .mcp-meta strong { color: #0F172A; font-weight: 800; font-variant-numeric: tabular-nums; }

    /* ============ Sticky submit bar ============ */
    .mcf-submit {
        position: sticky;
        bottom: 0;
        margin: 18px -16px -100px;
        padding: 14px 18px;
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(12px);
        border-top: 1px solid #E2E8F0;
        display: flex; gap: 10px; justify-content: flex-end;
        z-index: 40;
        box-shadow: 0 -8px 20px -10px rgba(15,23,42,.10);
    }
    @media (min-width: 640px) {
        .mcf-submit { margin: 18px 0 0; padding: 12px 0 0; background: transparent; box-shadow: none; border-top: 0; position: static; }
    }
    .mcf-btn {
        padding: 12px 24px;
        font-size: 13px; font-weight: 800;
        border-radius: 12px;
        text-decoration: none; border: 0; cursor: pointer;
        transition: transform .15s, box-shadow .15s;
        display: inline-flex; align-items: center; gap: 6px;
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
    .mcf-btn--save svg { width: 14px; height: 14px; }
</style>

<div class="mcf-wrap"
     x-data="merchantCardForm({
        initialDenoms: {{ json_encode(old('denominations', $card->denominations ?? [])) }},
        initialVisual: @js($card->visual_url ? \Storage::url($card->visual_url) : null),
        initialCustom: {{ old('allow_custom_amount', $card->allow_custom_amount) ? 'true' : 'false' }},
        initialName:   @js(old('name', $card->name)),
        initialValidity: {{ (int) old('validity_months', $card->validity_months ?? 12) }},
     })">

    {{-- ============ HERO ============ --}}
    <div class="mcf-hero">
        <a href="{{ route('vendor.merchant-cards.index') }}" class="mcf-crumb">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Mes cartes-cadeau
        </a>
        <h1>{{ $isEdit ? 'Modifier la carte' : 'Créer une nouvelle carte-cadeau' }}</h1>
        <p>{{ $isEdit
            ? 'Toute modification d\'une carte active la repassera en attente de validation par l\'équipe Kardafrica.'
            : 'En 4 étapes, crée une carte vendable sous ta propre marque. Tu pourras la modifier tant qu\'elle n\'a pas été publiée.' }}</p>
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

        <div class="mcf-grid">
            {{-- ====================== COL GAUCHE — FORM ====================== --}}
            <div>
                {{-- =========== SECTION 1 : Identité =========== --}}
                <div class="mcf-section">
                    <div class="mcf-section-head">
                        <div class="mcf-step">1</div>
                        <div>
                            <h2 class="mcf-section-title">Identité de la carte</h2>
                            <p class="mcf-section-hint">Le nom et la catégorie qui s'afficheront aux clients sur Kardafrica.</p>
                        </div>
                    </div>

                    <div class="mcf-field">
                        <label class="mcf-label" for="name">Nom de la carte <span class="mcf-label-req">*</span></label>
                        <input type="text" id="name" name="name" class="mcf-input"
                               value="{{ old('name', $card->name) }}"
                               x-model="cardName"
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
                    <div class="mcf-section-head">
                        <div class="mcf-step">2</div>
                        <div>
                            <h2 class="mcf-section-title">Visuel de la carte</h2>
                            <p class="mcf-section-hint">L'image affichée sur le catalogue et au moment de l'envoi au destinataire. JPG, PNG ou WebP — 3&nbsp;Mo max.</p>
                        </div>
                    </div>

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
                                <div class="mcf-upload-hint">Format recommandé : 1550 × 1000 px · ratio paysage</div>
                                <span class="mcf-upload-cta">Parcourir mes fichiers</span>
                            </div>
                        </template>
                        <template x-if="visualPreview">
                            <span class="mcf-upload-cta">Changer le visuel</span>
                        </template>
                    </label>
                    @error('visual') <p class="mcf-error">{{ $message }}</p> @enderror
                </div>

                {{-- =========== SECTION 3 : Montants =========== --}}
                <div class="mcf-section">
                    <div class="mcf-section-head">
                        <div class="mcf-step">3</div>
                        <div>
                            <h2 class="mcf-section-title">Montants proposés</h2>
                            <p class="mcf-section-hint">Les dénominations affichées au client (en FCFA). Tu peux aussi autoriser un montant libre.</p>
                        </div>
                    </div>

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
                            <input type="number" x-model.number="newDenom" min="500" max="1000000" step="500" placeholder="Ex : 15000"
                                   @keydown.enter.prevent="addDenom()">
                            <button type="button" @click="addDenom()">+ Ajouter</button>
                        </div>
                        <div class="mcf-denom-quick">
                            <span style="font-size:11px;color:#94A3B8;align-self:center;margin-right:4px;font-weight:600;">Rapide :</span>
                            <template x-for="v in [5000,10000,15000,25000,50000,100000]" :key="v">
                                <button type="button" @click="quickAdd(v)" x-text="formatXAF(v)" :disabled="denoms.includes(v)" :style="denoms.includes(v) ? 'opacity:.4;cursor:default;' : ''"></button>
                            </template>
                        </div>
                        @error('denominations')   <p class="mcf-error">{{ $message }}</p> @enderror
                        @error('denominations.*') <p class="mcf-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="mcf-field">
                        <label class="mcf-toggle">
                            <div class="mcf-toggle-label-wrap">
                                <span class="mcf-toggle-label">Autoriser un montant libre</span>
                                <span class="mcf-toggle-hint">Le client peut saisir son propre montant (ex&nbsp;: 7&nbsp;500&nbsp;F).</span>
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
                    <div class="mcf-section-head">
                        <div class="mcf-step">4</div>
                        <div>
                            <h2 class="mcf-section-title">Validité & conditions</h2>
                            <p class="mcf-section-hint">Combien de temps la carte reste utilisable et les conditions affichées au client.</p>
                        </div>
                    </div>

                    <div class="mcf-row">
                        <div class="mcf-field">
                            <label class="mcf-label" for="validity_months">Durée de validité (mois) <span class="mcf-label-req">*</span></label>
                            <input type="number" id="validity_months" name="validity_months" class="mcf-input"
                                   value="{{ old('validity_months', $card->validity_months ?? 12) }}"
                                   x-model.number="validityMonths"
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

                {{-- =========== SUBMIT (sticky on mobile) =========== --}}
                <div class="mcf-submit">
                    <a href="{{ route('vendor.merchant-cards.index') }}" class="mcf-btn mcf-btn--cancel">Annuler</a>
                    <button type="submit" class="mcf-btn mcf-btn--save">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $isEdit ? 'Enregistrer' : 'Créer la carte' }}
                    </button>
                </div>
            </div>

            {{-- ====================== COL DROITE — PREVIEW ====================== --}}
            <aside class="mcf-aside">
                <div class="mcf-aside-card">
                    <p class="mcf-aside-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Aperçu en direct
                    </p>

                    <div class="mcp">
                        <div class="mcp-bg" :style="visualPreview ? `background-image:url('${visualPreview}')` : ''"></div>
                        <div class="mcp-grad"></div>
                        <span class="mcp-corner">{{ Str::upper($reseller->business_name ?? $reseller->name) }}</span>
                        <span class="mcp-chip">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="width:9px;height:9px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span x-text="validityMonths + ' mois'"></span>
                        </span>
                        <div class="mcp-bottom">
                            <h3 class="mcp-name" x-text="cardName || 'Nom de la carte'"></h3>
                            <div class="mcp-denoms">
                                <template x-for="d in denoms.slice(0, 5)" :key="d">
                                    <span x-text="formatXAF(d)"></span>
                                </template>
                                <span x-show="allowCustom" style="background:rgba(94,234,212,.30);">+ libre</span>
                            </div>
                        </div>
                    </div>

                    <div class="mcp-meta">
                        <span>Marchand</span>
                        <strong>{{ $reseller->business_name ?? $reseller->name }}</strong>
                    </div>
                    <div class="mcp-meta" style="border-top:0;padding-top:6px;">
                        <span>Statut</span>
                        <strong style="color:{{ $isEdit && $card->is_active ? '#10B981' : '#F59E0B' }};">
                            {{ $isEdit && $card->is_active ? 'Active' : 'Brouillon' }}
                        </strong>
                    </div>

                    <div style="margin-top:14px;padding:10px 12px;background:#F0F9FF;border-radius:10px;font-size:11px;color:#0E7490;line-height:1.5;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="width:14px;height:14px;vertical-align:-3px;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            La carte sera <strong>publiée après validation</strong> par l'équipe Kardafrica.
                    </div>
                </div>
            </aside>
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
            cardName:      opts.initialName || '',
            validityMonths: opts.initialValidity || 12,

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
