import { Tabs } from 'expo-router';
import { HomeIcon, CreditCardIcon, ShoppingBagIcon, UserIcon, ShoppingCartIcon } from 'react-native-heroicons/outline';

export default function TabLayout() {
  return (
    <Tabs screenOptions={{
      tabBarActiveTintColor: '#4ECDC4',
      tabBarInactiveTintColor: '#718096',
      headerShown: false,
    }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Accueil',
          tabBarIcon: ({ color }) => <HomeIcon size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="boutique"
        options={{
          title: 'Boutique',
          tabBarIcon: ({ color }) => <ShoppingBagIcon size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: 'Mes Cartes',
          tabBarIcon: ({ color }) => <CreditCardIcon size={24} color={color} />,
        }}
      />

      <Tabs.Screen
        name="cart"
        options={{
          title: 'Panier',
          tabBarIcon: ({ color }) => <ShoppingCartIcon size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="profile"
        options={{
          title: 'Profil',
          tabBarIcon: ({ color }) => <UserIcon size={24} color={color} />,
        }}
      />
    </Tabs>
  );
}
