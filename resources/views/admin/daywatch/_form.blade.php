@php
    $isEdit = isset($product) && $product->exists;
    $action = $isEdit ? route('admin.daywatch.update', $product) : route('admin.daywatch.store');
    $featuresText = $product->features ? implode("\n", $product->features) : '';
@endphp

<form method="POST" action="{{ $action }}" data-no-loader
      style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Colonne gauche : champs principaux --}}
    <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#44A08D;margin-bottom:14px;">Informations</div>

        {{-- Nom --}}
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Nom <span style="color:#F43F5E;">*</span></label>
            <input type="text" name="name" required maxlength="120"
                   value="{{ old('name', $product->name) }}"
                   placeholder="Ex : Daywatch Premium"
                   style="width:100%;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;color:#0F172A;outline:none;"
                   onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
            @error('name')<div style="color:#BE123C;font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>

        {{-- Sous-titre --}}
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Sous-titre</label>
            <input type="text" name="subtitle" maxlength="160"
                   value="{{ old('subtitle', $product->subtitle) }}"
                   placeholder="Une ligne de pitch courte"
                   style="width:100%;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;color:#0F172A;outline:none;"
                   onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
        </div>

        {{-- Description --}}
        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Description</label>
            <textarea name="description" rows="4" maxlength="2000"
                      placeholder="Décrivez l'abonnement, le contenu, les avantages…"
                      style="width:100%;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;color:#0F172A;outline:none;resize:vertical;font-family:inherit;"
                      onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                      onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">{{ old('description', $product->description) }}</textarea>
        </div>

        {{-- Durée + Prix --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Durée (jours) <span style="color:#F43F5E;">*</span></label>
                <input type="number" name="duration_days" required min="1" max="3650"
                       value="{{ old('duration_days', $product->duration_days ?? 30) }}"
                       style="width:100%;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;color:#0F172A;outline:none;font-variant-numeric:tabular-nums;"
                       onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Prix <span style="color:#F43F5E;">*</span></label>
                <div style="position:relative;">
                    <input type="number" name="price_xaf" required min="0"
                           value="{{ old('price_xaf', $product->price_xaf ?? 0) }}"
                           style="width:100%;padding:11px 60px 11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;color:#0F172A;outline:none;font-variant-numeric:tabular-nums;"
                           onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                           onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                    <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:#94A3B8;">FCFA</span>
                </div>
                <input type="hidden" name="currency" value="{{ old('currency', $product->currency ?? 'XAF') }}">
            </div>
        </div>

        {{-- Features (textarea, 1 ligne = 1 feature) --}}
        <div style="margin-bottom:0;">
            <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">
                Caractéristiques <span style="color:#94A3B8;font-weight:400;">(une par ligne)</span>
            </label>
            <textarea name="features_text" rows="6"
                      placeholder="Catalogue complet&#10;Qualité Full HD&#10;4 écrans simultanés&#10;…"
                      style="width:100%;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#0F172A;outline:none;resize:vertical;font-family:inherit;line-height:1.6;"
                      onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                      onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">{{ old('features_text', $featuresText) }}</textarea>
        </div>
    </div>

    {{-- Colonne droite : visuel + flags + preview --}}
    <div style="display:flex;flex-direction:column;gap:14px;">

        {{-- Preview live --}}
        <div x-data="{
                name:    '{{ addslashes(old('name', $product->name ?? 'Daywatch')) }}',
                price:   {{ (int) old('price_xaf', $product->price_xaf ?? 0) }},
                duration:{{ (int) old('duration_days', $product->duration_days ?? 30) }},
                color:   '{{ old('color', $product->color ?? '#44A08D') }}',
                subtitle:'{{ addslashes(old('subtitle', $product->subtitle ?? '')) }}'
             }"
             x-init="
                document.querySelector('input[name=name]').addEventListener('input', e => name = e.target.value || 'Daywatch');
                document.querySelector('input[name=price_xaf]').addEventListener('input', e => price = parseInt(e.target.value)||0);
                document.querySelector('input[name=duration_days]').addEventListener('input', e => duration = parseInt(e.target.value)||0);
                document.querySelector('input[name=color]').addEventListener('input', e => color = e.target.value);
                document.querySelector('input[name=subtitle]').addEventListener('input', e => subtitle = e.target.value);
             "
             style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#94A3B8;margin-bottom:10px;">Aperçu</div>
            <div :style="`position:relative;height:140px;border-radius:14px;padding:18px;color:#fff;background:linear-gradient(135deg,${color} 0%,${color}cc 100%);overflow:hidden;box-shadow:0 14px 30px -10px ${color}66;`">
                <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 0%,rgba(255,255,255,0.20) 0%,transparent 50%);pointer-events:none;"></div>
                <div style="position:relative;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;opacity:0.8;">Daywatch</div>
                    <div x-text="name" style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;margin-top:2px;line-height:1.1;"></div>
                    <div x-show="subtitle" x-text="subtitle" style="font-size:11px;opacity:0.85;margin-top:4px;"></div>
                </div>
                <div style="position:absolute;bottom:14px;left:18px;right:18px;display:flex;align-items:end;justify-content:space-between;">
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1;">
                        <span x-text="price.toLocaleString('fr-FR')"></span>
                        <span style="font-size:11px;font-weight:500;opacity:0.8;">FCFA</span>
                    </div>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:rgba(0,0,0,0.20);padding:3px 8px;border-radius:6px;">
                        <span x-text="duration"></span> j
                    </div>
                </div>
            </div>
        </div>

        {{-- Apparence --}}
        <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#94A3B8;margin-bottom:14px;">Apparence</div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Couleur</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" name="color"
                           value="{{ old('color', $product->color ?? '#44A08D') }}"
                           style="width:48px;height:38px;border:1px solid #E2E8F0;border-radius:10px;cursor:pointer;background:transparent;">
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach (['#44A08D','#3B82F6','#7C3AED','#EA580C','#DC2626','#F59E0B','#10B981','#1E293B'] as $c)
                            <button type="button" onclick="document.querySelector('input[name=color]').value='{{ $c }}';document.querySelector('input[name=color]').dispatchEvent(new Event('input'));"
                                    style="width:24px;height:24px;border-radius:7px;border:1px solid rgba(15,23,42,0.10);background:{{ $c }};cursor:pointer;"></button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Image (URL)</label>
                <input type="url" name="image_url" maxlength="500"
                       value="{{ old('image_url', $product->image_url) }}"
                       placeholder="https://…"
                       style="width:100%;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#0F172A;outline:none;font-family:monospace;"
                       onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
            </div>
        </div>

        {{-- Statut + ordre --}}
        <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:#94A3B8;margin-bottom:14px;">Visibilité</div>

            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#F8FAFC;border:1px solid #E2E8F0;">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
                       style="margin-top:2px;width:16px;height:16px;accent-color:#44A08D;">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#0F172A;">Actif</div>
                    <div style="font-size:11px;color:#64748B;margin-top:2px;">Visible sur la boutique publique.</div>
                </div>
            </label>

            <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin-bottom:14px;padding:10px 12px;border-radius:10px;background:#F8FAFC;border:1px solid #E2E8F0;">
                <input type="checkbox" name="is_featured" value="1"
                       {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}
                       style="margin-top:2px;width:16px;height:16px;accent-color:#44A08D;">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#0F172A;">Mis en avant</div>
                    <div style="font-size:11px;color:#64748B;margin-top:2px;">Affiche le badge « À la une ».</div>
                </div>
            </label>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Ordre</label>
                    <input type="number" name="sort_order" min="0" max="9999"
                           value="{{ old('sort_order', $product->sort_order ?? 0) }}"
                           style="width:100%;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#0F172A;outline:none;font-variant-numeric:tabular-nums;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">Stock <span style="color:#94A3B8;font-weight:400;">(opt.)</span></label>
                    <input type="number" name="stock" min="0"
                           value="{{ old('stock', $product->stock) }}"
                           placeholder="∞"
                           style="width:100%;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#0F172A;outline:none;font-variant-numeric:tabular-nums;">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a href="{{ route('admin.daywatch.index') }}"
               style="padding:12px 18px;background:#F1F5F9;color:#475569;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;">
                Annuler
            </a>
            <button type="submit"
                    style="padding:12px 22px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border:0;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);">
                {{ $isEdit ? 'Enregistrer' : 'Créer le Daywatch' }}
            </button>
        </div>
    </div>
</form>
