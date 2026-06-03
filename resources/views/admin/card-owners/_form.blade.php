{{--
    Form partagé create + edit pour les propriétaires de cartes locales.
    Variables :
      - $owner       (CardOwner — vide pour create)
      - $categories  (slug => label) — utilisé pour business_type
      - $isEdit      (bool)
--}}
<style>
    .acf-wrap { padding: 24px; font-family: 'Inter','Figtree',sans-serif; max-width: 980px; margin: 0 auto; }
    .acf-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        color: white; border-radius: 18px; padding: 22px 26px; margin-bottom: 18px;
        position: relative; overflow: hidden;
    }
    .acf-hero-glow { position: absolute; top: -40%; right: -10%; width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(94,234,212,0.18), transparent 60%); pointer-events: none; }
    .acf-hero a { display: inline-flex; align-items: center; gap: 5px; color: rgba(255,255,255,0.7);
        font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase;
        letter-spacing: .08em; margin-bottom: 8px; position: relative; }
    .acf-hero a:hover { color: #5EEAD4; }
    .acf-hero h1 { margin: 0; font-family: 'Space Grotesk','Inter',sans-serif; font-size: 24px;
        font-weight: 800; letter-spacing: -0.02em; position: relative; }
    .acf-hero p { margin: 6px 0 0; color: rgba(255,255,255,.75); font-size: 13px; line-height: 1.5;
        max-width: 620px; position: relative; }

    .acf-errors { background: #FEE2E2; color: #991B1B; padding: 12px 14px; border-radius: 12px;
        margin-bottom: 16px; font-size: 13px; }
    .acf-errors strong { display: block; margin-bottom: 4px; font-weight: 800; }
    .acf-errors ul { margin: 0; padding-left: 18px; }

    .acf-section { background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 22px;
        margin-bottom: 14px; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
    .acf-section-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
    .acf-step { flex-shrink: 0; width: 32px; height: 32px; border-radius: 10px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4); color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800; font-size: 14px; }
    .acf-section-title { margin: 0; font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A; line-height: 1.3; }
    .acf-section-hint { font-size: 12px; color: #64748B; margin: 2px 0 0; }

    .acf-field { margin-bottom: 14px; }
    .acf-field:last-child { margin-bottom: 0; }
    .acf-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .acf-label-req { color: #DC2626; }
    .acf-input, .acf-select, .acf-textarea { width: 100%; padding: 10px 12px; font-size: 14px;
        font-family: inherit; color: #0F172A; background: white; border: 1.5px solid #E2E8F0;
        border-radius: 10px; outline: none; }
    .acf-input:focus, .acf-select:focus, .acf-textarea:focus { border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,.15); }
    .acf-error { font-size: 11px; color: #DC2626; margin-top: 4px; font-weight: 600; }
    .acf-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; }
    .acf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .acf-row { grid-template-columns: 1fr; } }

    .acf-toggle { display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px;
        cursor: pointer; }
    .acf-toggle-label { font-weight: 700; color: #0F172A; display: block; font-size: 13px; }
    .acf-toggle-hint { font-size: 11px; color: #64748B; margin-top: 2px; }
    .acf-switch { position: relative; width: 44px; height: 24px; background: #CBD5E1;
        border-radius: 9999px; flex-shrink: 0; }
    .acf-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px;
        height: 20px; background: white; border-radius: 50%; transition: transform .15s;
        box-shadow: 0 2px 4px rgba(0,0,0,.2); }
    .acf-toggle input { display: none; }
    .acf-toggle input:checked ~ .acf-switch { background: #44A08D; }
    .acf-toggle input:checked ~ .acf-switch::after { transform: translateX(20px); }

    .acf-upload { display: block; position: relative; border: 2px dashed #CBD5E1;
        border-radius: 16px; padding: 18px; text-align: center; cursor: pointer;
        background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%); }
    .acf-upload input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .acf-upload-preview { display: block; margin: 0 auto; max-width: 120px; height: 120px;
        object-fit: cover; border-radius: 50%; background: #F1F5F9; }
    .acf-upload-ic { display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px; background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white; border-radius: 16px; margin-bottom: 10px; }
    .acf-upload-text { font-size: 14px; font-weight: 800; color: #0F172A; }
    .acf-upload-hint { font-size: 11px; color: #64748B; margin-top: 4px; }

    .acf-submit { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
    .acf-btn { padding: 12px 24px; border: 0; border-radius: 12px; font-size: 13px;
        font-weight: 800; cursor: pointer; font-family: inherit; display: inline-flex;
        align-items: center; gap: 6px; text-decoration: none; }
    .acf-btn--cancel { background: #F1F5F9; color: #334155; }
    .acf-btn--save { background: linear-gradient(135deg,#44A08D,#4ECDC4); color: white;
        box-shadow: 0 6px 16px -4px rgba(78,205,196,.45); }
</style>

<div class="acf-wrap"
     x-data="adminCardOwnerForm({ initialLogo: @js($owner->logo_url ? asset($owner->logo_url) : null) })">

    <div class="acf-hero">
        <div class="acf-hero-glow"></div>
        <a href="{{ route('admin.card-owners.index') }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux propriétaires
        </a>
        <h1>{{ $isEdit ? 'Modifier le propriétaire' : 'Nouveau propriétaire de carte' }}</h1>
        <p>{{ $isEdit
            ? 'Met à jour les informations de connexion et de contact. Laisse le mot de passe vide pour le conserver.'
            : 'Crée le compte propriétaire (commerçant) à qui seront rattachées des cartes locales. Il pourra ensuite se connecter sur /proprietaire pour son dashboard et le scan au comptoir.' }}</p>
    </div>

    @if($errors->any())
        <div class="acf-errors">
            <strong>Corrige les erreurs suivantes :</strong>
            <ul>@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.card-owners.update', $owner) : route('admin.card-owners.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="acf-section">
            <div class="acf-section-head">
                <div class="acf-step">1</div>
                <div>
                    <h2 class="acf-section-title">Identité du commerce</h2>
                    <p class="acf-section-hint">Raison sociale et personne de contact.</p>
                </div>
            </div>
            <div class="acf-row">
                <div class="acf-field">
                    <label class="acf-label">Raison sociale <span class="acf-label-req">*</span></label>
                    <input type="text" name="business_name" class="acf-input" required maxlength="120"
                           value="{{ old('business_name', $owner->business_name) }}"
                           placeholder="Ex : Restaurant Le Politiqua">
                    @error('business_name') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
                <div class="acf-field">
                    <label class="acf-label">Nom du contact <span class="acf-label-req">*</span></label>
                    <input type="text" name="contact_name" class="acf-input" required maxlength="120"
                           value="{{ old('contact_name', $owner->contact_name) }}"
                           placeholder="Ex : Jean Dupont">
                    @error('contact_name') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="acf-row">
                <div class="acf-field">
                    <label class="acf-label">Type d'activité</label>
                    <select name="business_type" class="acf-select">
                        <option value="">— Aucune —</option>
                        @foreach($categories as $slug => $label)
                            <option value="{{ $slug }}" {{ old('business_type', $owner->business_type) === $slug ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('business_type') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
                <div class="acf-field">
                    <label class="acf-label">Ville</label>
                    <input type="text" name="city" class="acf-input" maxlength="100"
                           value="{{ old('city', $owner->city) }}" placeholder="Libreville">
                    @error('city') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="acf-field">
                <label class="acf-label">Adresse</label>
                <input type="text" name="address" class="acf-input" maxlength="255"
                       value="{{ old('address', $owner->address) }}"
                       placeholder="Quartier, rue, immeuble…">
                @error('address') <p class="acf-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="acf-section">
            <div class="acf-section-head">
                <div class="acf-step">2</div>
                <div>
                    <h2 class="acf-section-title">Connexion & contact</h2>
                    <p class="acf-section-hint">L'email sert d'identifiant de connexion sur /proprietaire.</p>
                </div>
            </div>
            <div class="acf-row">
                <div class="acf-field">
                    <label class="acf-label">Email <span class="acf-label-req">*</span></label>
                    <input type="email" name="email" class="acf-input" required maxlength="190"
                           value="{{ old('email', $owner->email) }}" placeholder="contact@…">
                    @error('email') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
                <div class="acf-field">
                    <label class="acf-label">Mot de passe</label>
                    <input type="text" name="password" class="acf-input" minlength="6" maxlength="120"
                           placeholder="{{ $isEdit ? 'Laisse vide pour conserver' : 'Auto-généré si vide' }}">
                    @error('password') <p class="acf-error">{{ $message }}</p> @enderror
                    <p class="acf-hint">{{ $isEdit
                        ? 'Laisse vide pour conserver le mot de passe actuel.'
                        : 'Si vide, un mot de passe à 8 chiffres sera généré et affiché après la création.' }}</p>
                </div>
            </div>
            <div class="acf-row">
                <div class="acf-field">
                    <label class="acf-label">Téléphone <span class="acf-label-req">*</span></label>
                    <input type="text" name="phone" class="acf-input" required maxlength="30"
                           value="{{ old('phone', $owner->phone) }}" placeholder="+241 …">
                    @error('phone') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
                <div class="acf-field">
                    <label class="acf-label">WhatsApp</label>
                    <input type="text" name="whatsapp_number" class="acf-input" maxlength="30"
                           value="{{ old('whatsapp_number', $owner->whatsapp_number) }}" placeholder="+241 …">
                    @error('whatsapp_number') <p class="acf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="acf-section">
            <div class="acf-section-head">
                <div class="acf-step">3</div>
                <div>
                    <h2 class="acf-section-title">Logo & activation</h2>
                    <p class="acf-section-hint">Optionnel. JPG/PNG/WebP, 2 Mo max.</p>
                </div>
            </div>
            <label class="acf-upload">
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" @change="onLogoChange($event)">
                <template x-if="logoPreview">
                    <img :src="logoPreview" class="acf-upload-preview" alt="Logo">
                </template>
                <template x-if="!logoPreview">
                    <div>
                        <div class="acf-upload-ic">
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="acf-upload-text">Choisir un logo</div>
                        <div class="acf-upload-hint">Format carré recommandé · max 2 Mo</div>
                    </div>
                </template>
            </label>
            @error('logo') <p class="acf-error">{{ $message }}</p> @enderror

            <div class="acf-field" style="margin-top:14px;">
                <label class="acf-toggle">
                    <div>
                        <span class="acf-toggle-label">Compte actif</span>
                        <span class="acf-toggle-hint">Décoche pour bloquer la connexion sur /proprietaire.</span>
                    </div>
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $owner->is_active) ? 'checked' : '' }}>
                    <span class="acf-switch"></span>
                </label>
            </div>
        </div>

        <div class="acf-submit">
            <a href="{{ route('admin.card-owners.index') }}" class="acf-btn acf-btn--cancel">Annuler</a>
            <button type="submit" class="acf-btn acf-btn--save">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $isEdit ? 'Enregistrer' : 'Créer le propriétaire' }}
            </button>
        </div>
    </form>
</div>

<script>
    function adminCardOwnerForm(opts) {
        return {
            logoPreview: opts.initialLogo || null,
            onLogoChange(e) {
                const f = e.target.files && e.target.files[0];
                if (!f) return;
                const r = new FileReader();
                r.onload = ev => this.logoPreview = ev.target.result;
                r.readAsDataURL(f);
            },
        };
    }
</script>
