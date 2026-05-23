import { View, Text } from 'react-native';
import Svg, { Path, Circle, Rect, Polygon, G, Defs } from 'react-native-svg';
import { BrandKey } from '../utils/brandStyle';

/**
 * Logos officiels SVG des 9 marques principales (= proposition design).
 * Tous les SVG sont vectoriels (pas d'images externes) — légers et nets à
 * toute taille. Couleurs intégrées (white sur Netflix/PSN/Spotify, noir/multi
 * sur Apple/Google/Roblox/Xbox/Steam/Amazon).
 *
 * Pour les marques non listées (logoType=null), on rend une initiale dans
 * un cercle blanc — pas via ce composant.
 */

interface BrandLogoProps {
  brand: BrandKey;
  size?: number;
  /** Override pour les variantes texte (Netflix/PSN/Amazon) : largeur > hauteur */
  width?: number;
  height?: number;
}

export function BrandLogo({ brand, size = 52, width, height }: BrandLogoProps) {
  const w = width ?? size;
  const h = height ?? size;

  switch (brand) {
    case 'apple':
      return (
        <Svg width={w} height={h} viewBox="0 0 384 512">
          <Path
            fill="#1d1d1f"
            d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"
          />
        </Svg>
      );

    case 'psn':
      // PS large + N fin (= proposition)
      return (
        <Svg width={width ?? 80} height={height ?? 32} viewBox="0 0 200 80">
          <Path d="M0 60 L0 0 L42 0 L42 12 L18 12 L18 24 L40 24 L40 36 L18 36 L18 60 Z M52 60 L52 0 L80 0 L80 60 Z" fill="#ffffff" />
          <Path d="M90 60 L90 0 L110 0 L110 28 L130 0 L150 0 L130 30 L150 60 L130 60 L110 32 L110 60 Z" fill="#ffffff" />
        </Svg>
      );

    case 'netflix':
      // Logo "N" stylisé Netflix (rouge sur fond sombre)
      return (
        <Svg width={width ?? 80} height={height ?? 24} viewBox="0 0 200 60">
          <Path
            d="M0 0 L0 60 L18 60 L18 25 L36 60 L52 60 L52 0 L34 0 L34 33 L18 0 Z"
            fill="#E50914"
          />
          <Path
            d="M62 0 L62 60 L78 60 L78 35 L96 35 L96 25 L78 25 L78 12 L100 12 L100 0 Z"
            fill="#E50914"
          />
        </Svg>
      );

    case 'roblox':
      // Carré incliné blanc avec petit carré sombre au centre
      return (
        <Svg width={w} height={h} viewBox="0 0 100 100">
          <G transform="rotate(8 50 50)">
            <Rect x="15" y="15" width="70" height="70" rx="6" fill="#ffffff" />
            <Rect x="38" y="38" width="24" height="24" rx="2" fill="#232527" />
          </G>
        </Svg>
      );

    case 'google':
      // Triangle "play" en 4 couleurs Google
      return (
        <Svg width={w} height={h} viewBox="0 0 100 100">
          <Polygon points="20,15 75,50 20,85" fill="#34A853" />
          <Polygon points="20,15 75,50 50,40" fill="#FBBC04" />
          <Polygon points="20,85 75,50 50,60" fill="#EA4335" />
          <Polygon points="20,15 50,40 50,60 20,85" fill="#4285F4" />
        </Svg>
      );

    case 'xbox':
      // X stylisé dans un cercle blanc
      return (
        <Svg width={w} height={h} viewBox="0 0 100 100">
          <Circle cx="50" cy="50" r="42" fill="#ffffff" />
          <Path d="M30 25 Q45 40 50 50 Q55 40 70 25 Q60 18 50 18 Q40 18 30 25Z" fill="#107C10" />
          <Path d="M30 75 Q45 60 50 50 Q55 60 70 75 Q60 82 50 82 Q40 82 30 75Z" fill="#107C10" />
          <Path d="M22 35 Q35 50 22 65 Q12 55 12 50 Q12 45 22 35Z" fill="#107C10" />
          <Path d="M78 35 Q65 50 78 65 Q88 55 88 50 Q88 45 78 35Z" fill="#107C10" />
        </Svg>
      );

    case 'spotify':
      // Cercle vert avec 3 ondes
      return (
        <Svg width={w} height={h} viewBox="0 0 100 100">
          <Circle cx="50" cy="50" r="44" fill="#1ED760" />
          <Path d="M28 38 Q50 32 72 42" stroke="#000" strokeWidth="6" strokeLinecap="round" fill="none" />
          <Path d="M30 52 Q50 46 70 56" stroke="#000" strokeWidth="5" strokeLinecap="round" fill="none" />
          <Path d="M32 64 Q50 60 66 68" stroke="#000" strokeWidth="4" strokeLinecap="round" fill="none" />
        </Svg>
      );

    case 'steam':
      // Logo Steam stylisé (2 cercles concentriques)
      return (
        <Svg width={w} height={h} viewBox="0 0 100 100">
          <Circle cx="50" cy="50" r="44" fill="none" stroke="#c7d5e0" strokeWidth="3" />
          <Circle cx="38" cy="40" r="14" fill="none" stroke="#c7d5e0" strokeWidth="3" />
          <Circle cx="38" cy="40" r="6" fill="#c7d5e0" />
          <Circle cx="62" cy="58" r="10" fill="none" stroke="#c7d5e0" strokeWidth="3" />
          <Circle cx="62" cy="58" r="4" fill="#c7d5e0" />
        </Svg>
      );

    case 'amazon':
      // "amazon" en text + sourire orange
      return (
        <View style={{ width: width ?? 80, height: height ?? 32, alignItems: 'center', justifyContent: 'center' }}>
          <Text style={{ color: '#ffffff', fontWeight: '700', fontSize: 20, letterSpacing: -0.5 }}>amazon</Text>
          <Svg width={width ?? 80} height={6} viewBox="0 0 80 6" style={{ marginTop: -2 }}>
            <Path d="M2 2 Q40 8 78 2" stroke="#FF9900" strokeWidth="2" fill="none" strokeLinecap="round" />
          </Svg>
        </View>
      );

    default:
      return null;
  }
}
