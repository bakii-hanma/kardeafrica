# Kardafrica Mobile App

Application mobile React Native Expo pour Kardafrica.

## Prérequis

- Node.js
- npm ou yarn
- Expo Go sur votre téléphone (Android ou iOS)

## Installation

```bash
cd mobile
npm install
```

## Lancement

```bash
npx expo start
```

## Structure du projet

- `app/` : Routes et écrans (Expo Router)
  - `(tabs)/` : Navigation par onglets (Accueil, Cartes, Panier, Profil)
  - `product/[id].tsx` : Détails d'un produit
  - `payment/index.tsx` : Écran de paiement
- `components/` : Composants réutilisables
- `data/` : Données mockées (produits, catégories)
- `assets/` : Images et icônes

## Technologies

- React Native
- Expo Router
- NativeWind (Tailwind CSS)
- Lucide React Native (Icônes)
