import { View, Text, StyleSheet, Image } from 'react-native';
import Svg, { Defs, LinearGradient as SvgLinearGradient, Stop, Rect } from 'react-native-svg';
import { CheckIcon } from 'react-native-heroicons/outline';
import {
  detectBrand, BRAND_STYLES, fallbackStyle, regionInfo, BrandStyle,
} from '../utils/brandStyle';
import { BrandLogo } from './BrandLogo';

/**
 * Helper interne — rend un gradient linéaire diagonal via react-native-svg
 * (évite la dépendance à expo-linear-gradient).
 */
function GradientBg({ colors, gradKey }: { colors: string[]; gradKey: string }) {
  return (
    <Svg style={StyleSheet.absoluteFillObject as any} preserveAspectRatio="none">
      <Defs>
        <SvgLinearGradient id={gradKey} x1="0%" y1="0%" x2="100%" y2="100%">
          {colors.map((c, i) => (
            <Stop key={i} offset={`${(i / (colors.length - 1)) * 100}%`} stopColor={c} />
          ))}
        </SvgLinearGradient>
      </Defs>
      <Rect x="0" y="0" width="100%" height="100%" fill={`url(#${gradKey})`} />
    </Svg>
  );
}

/**
 * Helper interne — chip doré (gradient + 2 lignes sombres).
 */
function GoldChip() {
  return (
    <Svg width={34} height={26} viewBox="0 0 34 26">
      <Defs>
        <SvgLinearGradient id="chipGold" x1="0%" y1="0%" x2="100%" y2="100%">
          <Stop offset="0%" stopColor="#f4d77a" />
          <Stop offset="100%" stopColor="#c89b3a" />
        </SvgLinearGradient>
      </Defs>
      <Rect x="0" y="0" width="34" height="26" rx="3" fill="url(#chipGold)" stroke="rgba(0,0,0,0.12)" strokeWidth="1" />
      <Rect x="4" y="7" width="26" height="1" fill="rgba(0,0,0,0.18)" />
      <Rect x="4" y="14" width="26" height="1" fill="rgba(0,0,0,0.18)" />
    </Svg>
  );
}

/**
 * Visuel de carte cadeau (= design hybride proposé) :
 *  - Background gradient officiel de la marque (ou fallback brandColor)
 *  - Glow d'ambiance par-dessus
 *  - Frame KardAfrica top + badge ✓ Vérifié
 *  - Logo officiel SVG (ou initiale fallback)
 *  - Nom de la marque big
 *  - Chip doré vertical-centered à droite
 *  - Région + drapeau + devise en bas
 *
 * Aspect ratio 1.586/1 (= ratio carte bancaire).
 * Utilisé par boutique.tsx (grid + list) et index.tsx (Populaires).
 */

interface GiftCardVisualProps {
  /** Nom de la marque affiché en gros (ex: "Apple", "Netflix") */
  brandLabel: string;
  /** Couleur fallback si la marque n'est pas dans le registre */
  brandColor: string;
  /** ISO countryCode pour le drapeau + label région ('FR', 'BE', 'EU'…) */
  countryCode?: string | null;
  /** Devise native (affichée si ≠ XAF) */
  currency?: string | null;
  /** Image officielle afrikard de la carte — affichée en fond (comme le web) */
  logoUrl?: string | null;
  /** Compact = pour mini-cartes (hero stack notamment) */
  compact?: boolean;
  /** ID unique pour le gradient SVG (évite les collisions si plusieurs cards sur la page) */
  gradId?: string;
}

export function GiftCardVisual({
  brandLabel,
  brandColor,
  countryCode,
  currency,
  logoUrl,
  compact = false,
  gradId,
}: GiftCardVisualProps) {
  const brandKey = detectBrand(brandLabel);
  const style: BrandStyle = brandKey ? BRAND_STYLES[brandKey] : fallbackStyle(brandColor);
  const [flag, regionLabel] = regionInfo(countryCode);

  const textColor = style.text;
  const showCurrency = currency && !['XAF', 'XOF'].includes(currency.toUpperCase());

  // ID unique pour le gradient SVG (évite les collisions multi-card)
  const uniqueGradId = gradId ?? `bg-${brandKey ?? 'fb'}-${(brandLabel || '').replace(/[^a-z0-9]/gi, '')}`;

  // Position de la glow d'ambiance
  const glowPos = (() => {
    if (!style.glow) return null;
    switch (style.glow.position) {
      case 'tl': return { top: -40, left: -40 };
      case 'tr': return { top: -40, right: -40 };
      case 'bl': return { bottom: -40, left: -40 };
      case 'br': return { bottom: -40, right: -40 };
      case 'center': return { top: '25%', left: '25%' };
    }
  })();

  // Logo : SVG officiel pour les marques connues, sinon initiale
  const renderLogo = () => {
    if (brandKey) {
      // Marques avec logo "wide" (text logos)
      if (brandKey === 'psn')     return <BrandLogo brand="psn"     width={compact ? 64 : 80} height={compact ? 26 : 32} />;
      if (brandKey === 'netflix') return <BrandLogo brand="netflix" width={compact ? 70 : 86} height={compact ? 22 : 26} />;
      if (brandKey === 'amazon')  return <BrandLogo brand="amazon"  width={compact ? 70 : 86} height={compact ? 24 : 30} />;
      // Marques carrées (logos)
      return <BrandLogo brand={brandKey} size={compact ? 44 : 52} />;
    }
    // Fallback : initiale dans un carré arrondi de la couleur du texte
    return (
      <View style={{
        width: compact ? 40 : 48, height: compact ? 40 : 48,
        borderRadius: 12, backgroundColor: textColor,
        alignItems: 'center', justifyContent: 'center',
      }}>
        <Text style={{ color: brandColor, fontSize: compact ? 18 : 22, fontWeight: '800', letterSpacing: -0.5 }}>
          {brandLabel.charAt(0).toUpperCase()}
        </Text>
      </View>
    );
  };

  return (
    <View style={{
      aspectRatio: 1.586,
      borderRadius: 18,
      overflow: 'hidden',
      position: 'relative',
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 12 },
      shadowOpacity: 0.20,
      shadowRadius: 18,
      elevation: 6,
    }}>
      {/* Gradient principal (via react-native-svg, pas de dépendance externe) */}
      <GradientBg colors={style.gradient as string[]} gradKey={uniqueGradId} />

      {/* Artwork officiel de la carte en WATERMARK à droite (identique au web :
          background-position 78% center, background-size 55% auto, contain =
          non déformé). Pas en cover plein cadre (ça recadrait/déformait). */}
      {logoUrl ? (
        <Image
          source={{ uri: logoUrl }}
          resizeMode="contain"
          style={{
            position: 'absolute',
            right: 0, top: 0, bottom: 0,
            width: '55%', height: '100%',
            opacity: brandKey ? 0.30 : 0.5,
          }}
        />
      ) : null}

      {/* Glow d'ambiance optionnelle (= proposition) */}
      {style.glow && glowPos && (
        <View style={{
          position: 'absolute',
          width: 200, height: 200, borderRadius: 100,
          backgroundColor: style.glow.color,
          ...glowPos,
        }} />
      )}

      {/* Contenu */}
      <View style={{
        flex: 1,
        padding: compact ? 12 : 14,
        justifyContent: 'space-between',
      }}>
        {/* Frame top : KardAfrica + ✓ Vérifié */}
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', position: 'relative', zIndex: 2 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}>
            <View style={{ width: 11, height: 11, borderRadius: 3, backgroundColor: textColor, opacity: 0.85 }} />
            <Text style={{ fontSize: 8, fontWeight: '700', letterSpacing: 1.2, textTransform: 'uppercase', color: textColor, opacity: 0.75 }}>
              KardAfrica
            </Text>
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 3 }}>
            <CheckIcon size={9} color={textColor} strokeWidth={3} />
            <Text style={{ fontSize: 8, fontWeight: '600', letterSpacing: 0.6, textTransform: 'uppercase', color: textColor, opacity: 0.7 }}>
              Vérifié
            </Text>
          </View>
        </View>

        {/* Logo + nom marque */}
        <View style={{ position: 'relative', zIndex: 2 }}>
          {renderLogo()}
          <Text
            numberOfLines={1}
            style={{
              color: textColor,
              fontSize: compact ? 18 : 22,
              fontWeight: '700',
              letterSpacing: -0.4,
              marginTop: 4,
              maxWidth: '70%',
            }}
          >
            {brandLabel}
          </Text>
        </View>

        {/* Région + drapeau */}
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', position: 'relative', zIndex: 2 }}>
          <Text style={{ fontSize: 11, color: textColor, opacity: 0.85, fontWeight: '500' }} numberOfLines={1}>
            {flag ? `${flag} ` : ''}{regionLabel}{showCurrency ? ` · ${currency!.toUpperCase()}` : ''}
          </Text>
          {/* Spacer (le chip est en absolute) */}
          <View style={{ width: 32 }} />
        </View>
      </View>

      {/* Chip doré vertical-center, droite */}
      <View style={{
        position: 'absolute',
        right: compact ? 12 : 14,
        top: '50%',
        marginTop: -13,
        zIndex: 1,
      }}>
        <GoldChip />
      </View>
    </View>
  );
}
