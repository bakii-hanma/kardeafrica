import {
  View, Text, TouchableOpacity, ScrollView, TextInput, KeyboardAvoidingView,
  Platform, Animated, Easing, StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'expo-router';
import {
  ArrowLeftIcon, PaperAirplaneIcon, SparklesIcon,
} from 'react-native-heroicons/outline';
import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * Vue native dédiée à l'assistante IA Kara — mêmes intentions que le widget
 * web (resources/views/profile/assistant.blade.php). Routage simple par
 * keywords avec liens deep vers les écrans natifs (boutique, orders, wallet…).
 *
 * Persistance : sessionStorage côté web, AsyncStorage côté mobile (clé
 * `kaMobileHistory`, max 100 messages).
 */

type LinkBtn = { label: string; route: string };
type Msg = { text: string; isUser: boolean; links?: LinkBtn[] };

interface Intent {
  match: RegExp;
  reply: () => string;
  links?: LinkBtn[];
}

const STORAGE_KEY = 'kaMobileHistory';

const DEFAULT_CHIPS: { label: string; intent: string }[] = [
  { label: 'Voir le catalogue',  intent: 'voir le catalogue' },
  { label: 'Comment payer ?',    intent: 'comment payer' },
  { label: 'Mes commandes',      intent: 'mes commandes' },
  { label: 'Délai de livraison', intent: 'délai livraison' },
  { label: 'Mes cartes',         intent: 'mes cartes' },
  { label: 'Daywatch',           intent: 'daywatch' },
];

const intents: Intent[] = [
  {
    match: /(catalog|boutique|cartes? disponibles|liste)/i,
    reply: () => `Voici le catalogue complet — plus de 300 marques disponibles.`,
    links: [{ label: 'Ouvrir la boutique', route: '/(tabs)/boutique' }],
  },
  {
    match: /(payer|paiement|mobile money|carte bancaire|airtel|moov)/i,
    reply: () => `On accepte Mobile Money (Airtel, Moov) et carte bancaire via E-Billing. Paiement instantané et 100% sécurisé.`,
    links: [{ label: 'Voir mon panier', route: '/(tabs)/cart' }],
  },
  {
    match: /(commande|order|achat)/i,
    reply: () => `Tu peux retrouver toutes tes commandes dans ton espace.`,
    links: [{ label: 'Mes commandes', route: '/orders' }],
  },
  {
    match: /(carte|cards|reçue)/i,
    reply: () => `Tes cartes achetées sont dans « Mes cartes » avec leurs codes/PIN.`,
    links: [{ label: 'Mes cartes', route: '/(tabs)/wallet' }],
  },
  {
    match: /(profile|profil|compte)/i,
    reply: () => `Mets à jour ton profil ici.`,
    links: [{ label: 'Mon profil', route: '/(tabs)/profile' }],
  },
  {
    match: /(livraison|délai|delai|reçoi|recevra)/i,
    reply: () => `Livraison instantanée par email après le paiement, en moins de 60 secondes dans 99% des cas.`,
  },
  {
    match: /(daywatch)/i,
    reply: () => `Daywatch — streaming local africain.`,
    links: [{ label: 'Voir Daywatch', route: '/category/5' }],
  },
  {
    match: /(netflix)/i,
    reply: () => `Netflix — abonnement streaming international.`,
    links: [{ label: 'Voir Netflix', route: '/search?q=Netflix' }],
  },
  {
    match: /(spotify)/i,
    reply: () => `Spotify Premium — musique sans pub.`,
    links: [{ label: 'Voir Spotify', route: '/search?q=Spotify' }],
  },
  {
    match: /(playstation|psn)/i,
    reply: () => `PlayStation Store — recharger un compte PSN.`,
    links: [{ label: 'Voir PSN', route: '/search?q=Playstation' }],
  },
  {
    match: /(steam)/i,
    reply: () => `Steam Wallet — acheter des jeux PC.`,
    links: [{ label: 'Voir Steam', route: '/search?q=Steam' }],
  },
  {
    match: /(apple|itunes|app store)/i,
    reply: () => `Apple Gift Card — App Store, iTunes, iCloud.`,
    links: [{ label: 'Voir Apple', route: '/search?q=Apple' }],
  },
  {
    match: /(merci|thanks|thank|cool|super|génial)/i,
    reply: () => `Avec plaisir ! 🙌 N'hésite pas si tu as d'autres questions.`,
  },
  {
    match: /(salut|bonjour|hello|coucou|hi|hey)/i,
    reply: () => `Bonjour ! 👋 Je suis Kara, l'assistante KardAfrica. Comment puis-je t'aider ?`,
  },
];

function detect(text: string): { reply: string; links: LinkBtn[] } {
  for (const it of intents) {
    if (it.match.test(text)) return { reply: it.reply(), links: it.links ?? [] };
  }
  return {
    reply: `Pas sûre d'avoir compris ! 🤔 Essaie l'une des suggestions ci-dessous, ou contacte le support pour être aidé(e) directement.`,
    links: [],
  };
}

// ============================================================
// Bubble component (animated)
// ============================================================
function Bubble({ msg, delay, onLinkPress }: { msg: Msg; delay: number; onLinkPress: (route: string) => void }) {
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(8)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 250, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
      Animated.timing(translateY, { toValue: 0, duration: 250, delay, useNativeDriver: true, easing: Easing.out(Easing.cubic) }),
    ]).start();
  }, []);

  const isUser = msg.isUser;

  return (
    <Animated.View style={{
      opacity, transform: [{ translateY }],
      flexDirection: isUser ? 'row-reverse' : 'row',
      alignItems: 'flex-end', gap: 6, maxWidth: '90%',
      alignSelf: isUser ? 'flex-end' : 'flex-start',
      marginBottom: 8,
    }}>
      {!isUser && (
        <View style={{
          width: 26, height: 26, borderRadius: 13,
          backgroundColor: '#44A08D',
          alignItems: 'center', justifyContent: 'center',
          flexShrink: 0, marginBottom: 2,
        }}>
          <SparklesIcon size={14} color="white" />
        </View>
      )}
      <View style={{
        backgroundColor: isUser ? '#44A08D' : 'white',
        borderRadius: 16,
        borderBottomLeftRadius: isUser ? 16 : 4,
        borderBottomRightRadius: isUser ? 4 : 16,
        paddingHorizontal: 13, paddingVertical: 9,
        shadowColor: isUser ? '#44A08D' : '#0F172A',
        shadowOffset: { width: 0, height: isUser ? 4 : 1 },
        shadowOpacity: isUser ? 0.20 : 0.05,
        shadowRadius: isUser ? 8 : 2,
        elevation: isUser ? 3 : 1,
      }}>
        <Text style={{
          color: isUser ? 'white' : '#0F172A',
          fontSize: 14, lineHeight: 20,
        }}>
          {msg.text}
        </Text>
        {!isUser && msg.links && msg.links.length > 0 && (
          <View style={{ marginTop: 8, gap: 6 }}>
            {msg.links.map((l, i) => (
              <TouchableOpacity
                key={i}
                onPress={() => onLinkPress(l.route)}
                activeOpacity={0.85}
                style={{
                  alignSelf: 'flex-start',
                  paddingHorizontal: 12, paddingVertical: 6,
                  borderRadius: 9,
                  backgroundColor: '#F0FDFA',
                  borderWidth: 1, borderColor: '#99F6E4',
                }}
              >
                <Text style={{ fontSize: 12, fontWeight: '900', color: '#0F766E' }}>
                  {l.label} →
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        )}
      </View>
    </Animated.View>
  );
}

// ============================================================
// Typing indicator (3 dots animées)
// ============================================================
function Typing() {
  const dots = [0, 1, 2].map(() => useRef(new Animated.Value(0.4)).current);

  useEffect(() => {
    const animations = dots.map((dot, i) =>
      Animated.loop(
        Animated.sequence([
          Animated.delay(i * 150),
          Animated.timing(dot, { toValue: 1, duration: 400, useNativeDriver: true }),
          Animated.timing(dot, { toValue: 0.4, duration: 400, useNativeDriver: true }),
        ]),
      ),
    );
    animations.forEach(a => a.start());
    return () => animations.forEach(a => a.stop());
  }, []);

  return (
    <View style={{
      flexDirection: 'row', alignItems: 'flex-end', gap: 6,
      alignSelf: 'flex-start', marginBottom: 8,
    }}>
      <View style={{
        width: 26, height: 26, borderRadius: 13,
        backgroundColor: '#44A08D',
        alignItems: 'center', justifyContent: 'center',
      }}>
        <SparklesIcon size={14} color="white" />
      </View>
      <View style={{
        backgroundColor: 'white',
        borderRadius: 16, borderBottomLeftRadius: 4,
        paddingHorizontal: 14, paddingVertical: 12,
        flexDirection: 'row', gap: 4,
      }}>
        {dots.map((dot, i) => (
          <Animated.View key={i} style={{
            width: 7, height: 7, borderRadius: 999,
            backgroundColor: '#94A3B8',
            opacity: dot,
          }} />
        ))}
      </View>
    </View>
  );
}

// ============================================================
// Main screen
// ============================================================
export default function AssistantScreen() {
  const router = useRouter();
  const scrollRef = useRef<ScrollView>(null);
  const [messages, setMessages] = useState<Msg[]>([]);
  const [input, setInput] = useState('');
  const [typing, setTyping] = useState(false);

  // Load history
  useEffect(() => {
    (async () => {
      try {
        const raw = await AsyncStorage.getItem(STORAGE_KEY);
        const hist: Msg[] = raw ? JSON.parse(raw) : [];
        if (hist.length === 0) {
          const greeting: Msg = {
            text: `Bonjour 👋 Je suis Kara, l'assistante KardAfrica. Comment puis-je t'aider aujourd'hui ?`,
            isUser: false,
          };
          setMessages([greeting]);
          await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify([greeting]));
        } else {
          setMessages(hist);
        }
      } catch {
        setMessages([{ text: `Bonjour 👋 Je suis Kara, l'assistante KardAfrica.`, isUser: false }]);
      }
    })();
  }, []);

  // Persist on every change
  useEffect(() => {
    if (messages.length > 0) {
      AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(messages.slice(-100))).catch(() => {});
    }
    // Scroll to bottom on new message
    setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 50);
  }, [messages]);

  const send = (text: string) => {
    const t = text.trim();
    if (!t) return;
    setMessages(prev => [...prev, { text: t, isUser: true }]);
    setInput('');
    setTyping(true);

    setTimeout(() => {
      const res = detect(t);
      setTyping(false);
      setMessages(prev => [...prev, { text: res.reply, isUser: false, links: res.links }]);
    }, 600 + Math.random() * 400);
  };

  const handleLinkPress = (route: string) => {
    router.push(route as any);
  };

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: '#F8FAFC' }} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* ===== Header sombre avec avatar Kara ===== */}
      <View style={{
        backgroundColor: '#0F172A',
        paddingHorizontal: 14, paddingTop: 8, paddingBottom: 16,
        position: 'relative', overflow: 'hidden',
      }}>
        {/* Halo */}
        <View style={{
          position: 'absolute', top: -60, right: -40,
          width: 200, height: 200, borderRadius: 100,
          backgroundColor: 'rgba(78,205,196,0.18)',
        }} />

        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
          <TouchableOpacity
            onPress={() => router.back()}
            style={{
              width: 38, height: 38, borderRadius: 11,
              backgroundColor: 'rgba(255,255,255,0.08)',
              borderWidth: 1, borderColor: 'rgba(255,255,255,0.15)',
              alignItems: 'center', justifyContent: 'center',
            }}
          >
            <ArrowLeftIcon size={18} color="white" />
          </TouchableOpacity>

          {/* Avatar Kara */}
          <View style={{
            width: 46, height: 46, borderRadius: 14,
            backgroundColor: '#44A08D',
            alignItems: 'center', justifyContent: 'center',
            position: 'relative',
            shadowColor: '#44A08D',
            shadowOffset: { width: 0, height: 8 },
            shadowOpacity: 0.40, shadowRadius: 16, elevation: 6,
          }}>
            <SparklesIcon size={22} color="white" />
            <View style={{
              position: 'absolute', bottom: -2, right: -2,
              width: 14, height: 14, borderRadius: 7,
              backgroundColor: '#34D399',
              borderWidth: 3, borderColor: '#0F172A',
            }} />
          </View>

          <View style={{ flex: 1, minWidth: 0 }}>
            <Text style={{
              color: '#5EEAD4', fontSize: 10, fontWeight: '900',
              letterSpacing: 1.4, textTransform: 'uppercase',
            }}>
              Assistante IA
            </Text>
            <Text style={{
              color: 'white', fontSize: 18, fontWeight: '900',
              letterSpacing: -0.3, marginTop: 1,
            }}>
              Kara
            </Text>
            <Text style={{ color: '#94A3B8', fontSize: 11, marginTop: 1 }}>
              <Text style={{ color: '#34D399', fontWeight: '900' }}>●</Text> En ligne · Réponse instantanée
            </Text>
          </View>
        </View>
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
      >
        {/* ===== Messages ===== */}
        <ScrollView
          ref={scrollRef}
          style={{ flex: 1 }}
          contentContainerStyle={{ padding: 14, paddingBottom: 20 }}
          showsVerticalScrollIndicator={false}
        >
          {messages.map((m, i) => (
            <Bubble key={i} msg={m} delay={0} onLinkPress={handleLinkPress} />
          ))}
          {typing && <Typing />}
        </ScrollView>

        {/* ===== Suggestion chips (scroll horizontal) ===== */}
        <View style={{
          backgroundColor: 'white',
          borderTopWidth: 1, borderTopColor: '#F1F5F9',
        }}>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ paddingHorizontal: 12, paddingVertical: 8, gap: 6 }}
          >
            {DEFAULT_CHIPS.map((chip, i) => (
              <TouchableOpacity
                key={i}
                onPress={() => send(chip.intent)}
                activeOpacity={0.85}
                style={{
                  paddingHorizontal: 13, paddingVertical: 7,
                  borderRadius: 999,
                  backgroundColor: '#F8FAFC',
                  borderWidth: 1, borderColor: '#E2E8F0',
                }}
              >
                <Text style={{ fontSize: 12, fontWeight: '600', color: '#475569' }}>
                  {chip.label}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>

        {/* ===== Composer ===== */}
        <View style={{
          flexDirection: 'row', alignItems: 'center',
          padding: 12, gap: 8,
          backgroundColor: 'white',
          borderTopWidth: 1, borderTopColor: '#F1F5F9',
        }}>
          <TextInput
            value={input}
            onChangeText={setInput}
            placeholder="Pose ta question…"
            placeholderTextColor="#94A3B8"
            style={{
              flex: 1,
              paddingHorizontal: 16, paddingVertical: 11,
              backgroundColor: '#F8FAFC',
              borderRadius: 999,
              borderWidth: 1, borderColor: '#E2E8F0',
              fontSize: 14, color: '#0F172A',
            }}
            returnKeyType="send"
            onSubmitEditing={() => send(input)}
            blurOnSubmit={false}
          />
          <TouchableOpacity
            onPress={() => send(input)}
            disabled={!input.trim()}
            activeOpacity={0.85}
            style={{
              width: 42, height: 42, borderRadius: 999,
              backgroundColor: input.trim() ? '#44A08D' : '#CBD5E1',
              alignItems: 'center', justifyContent: 'center',
              shadowColor: '#44A08D',
              shadowOffset: { width: 0, height: 6 },
              shadowOpacity: input.trim() ? 0.40 : 0,
              shadowRadius: 12,
              elevation: input.trim() ? 4 : 0,
            }}
          >
            <PaperAirplaneIcon size={16} color="white" />
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
