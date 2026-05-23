import { View, Text, TouchableOpacity, Modal, Animated, Easing, Dimensions } from 'react-native';
import { useEffect, useRef } from 'react';
import {
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from 'react-native-heroicons/outline';

/**
 * Boîte de dialogue custom KardAfrica — remplace l'Alert.alert natif (plain
 * popup OS) par une carte animée et brandée. Gère les 4 variantes de feedback
 * (success / error / warning / info) avec icône et couleur d'accent dédiées.
 *
 * Animations à l'ouverture : fade backdrop + slide-up + scale-in.
 * Animations à la fermeture : fade-out + scale-down.
 *
 * S'utilise via useAlert() (context/AlertContext.tsx) :
 *   const { showAlert } = useAlert();
 *   showAlert({ title: 'Échec', message: '…', variant: 'error' });
 */

export type AlertVariant = 'success' | 'error' | 'warning' | 'info';

export interface AlertButton {
  label: string;
  /** Style visuel du bouton */
  variant?: 'primary' | 'secondary' | 'danger';
  /** Callback exécuté avant la fermeture du modal */
  onPress?: () => void | Promise<void>;
}

export interface AlertDialogProps {
  visible: boolean;
  title: string;
  message?: string;
  variant?: AlertVariant;
  buttons?: AlertButton[];
  onDismiss: () => void;
  /** Si false, click sur le backdrop ne ferme pas (alertes critiques) */
  dismissibleOnBackdrop?: boolean;
}

const VARIANTS: Record<AlertVariant, {
  Icon: typeof CheckCircleIcon;
  accent: string;
  accentSoft: string;
  accentBorder: string;
  iconBg: string;
}> = {
  success: {
    Icon: CheckCircleIcon,
    accent: '#059669',
    accentSoft: '#D1FAE5',
    accentBorder: '#A7F3D0',
    iconBg: '#ECFDF5',
  },
  error: {
    Icon: XCircleIcon,
    accent: '#DC2626',
    accentSoft: '#FEE2E2',
    accentBorder: '#FECACA',
    iconBg: '#FEF2F2',
  },
  warning: {
    Icon: ExclamationTriangleIcon,
    accent: '#B45309',
    accentSoft: '#FEF3C7',
    accentBorder: '#FDE68A',
    iconBg: '#FFFBEB',
  },
  info: {
    Icon: InformationCircleIcon,
    accent: '#0F766E',
    accentSoft: '#CCFBF1',
    accentBorder: '#99F6E4',
    iconBg: '#F0FDFA',
  },
};

const { width: SCREEN_WIDTH } = Dimensions.get('window');

export function AlertDialog({
  visible,
  title,
  message,
  variant = 'info',
  buttons,
  onDismiss,
  dismissibleOnBackdrop = true,
}: AlertDialogProps) {
  const backdropOpacity = useRef(new Animated.Value(0)).current;
  const cardOpacity     = useRef(new Animated.Value(0)).current;
  const cardScale       = useRef(new Animated.Value(0.92)).current;
  const cardTranslateY  = useRef(new Animated.Value(24)).current;

  useEffect(() => {
    if (visible) {
      Animated.parallel([
        Animated.timing(backdropOpacity, {
          toValue: 1, duration: 220, useNativeDriver: true,
          easing: Easing.out(Easing.quad),
        }),
        Animated.timing(cardOpacity, {
          toValue: 1, duration: 280, useNativeDriver: true,
          easing: Easing.out(Easing.cubic),
        }),
        Animated.spring(cardScale, {
          toValue: 1, useNativeDriver: true,
          tension: 100, friction: 9,
        }),
        Animated.timing(cardTranslateY, {
          toValue: 0, duration: 320, useNativeDriver: true,
          easing: Easing.out(Easing.cubic),
        }),
      ]).start();
    } else {
      backdropOpacity.setValue(0);
      cardOpacity.setValue(0);
      cardScale.setValue(0.92);
      cardTranslateY.setValue(24);
    }
  }, [visible]);

  const v = VARIANTS[variant];
  const Icon = v.Icon;

  const finalButtons: AlertButton[] = buttons && buttons.length > 0
    ? buttons
    : [{ label: 'OK', variant: 'primary' }];

  const handleButtonPress = async (btn: AlertButton) => {
    try { await btn.onPress?.(); } catch (e) { console.error(e); }
    onDismiss();
  };

  return (
    <Modal
      visible={visible}
      transparent
      statusBarTranslucent
      animationType="none"
      onRequestClose={onDismiss}
    >
      {/* Backdrop animé */}
      <Animated.View
        style={{
          position: 'absolute', inset: 0,
          backgroundColor: 'rgba(15,23,42,0.55)',
          opacity: backdropOpacity,
        }}
      >
        <TouchableOpacity
          activeOpacity={1}
          onPress={dismissibleOnBackdrop ? onDismiss : undefined}
          style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 }}
        >
          {/* Card animée — slide-up + scale + fade */}
          <Animated.View
            // eslint-disable-next-line react-native/no-inline-styles
            style={{
              width: '100%', maxWidth: Math.min(360, SCREEN_WIDTH - 48),
              backgroundColor: 'white',
              borderRadius: 22,
              overflow: 'hidden',
              opacity: cardOpacity,
              transform: [
                { translateY: cardTranslateY },
                { scale: cardScale },
              ],
              shadowColor: v.accent,
              shadowOffset: { width: 0, height: 24 },
              shadowOpacity: 0.30,
              shadowRadius: 40,
              elevation: 24,
            }}
          >
            {/* Bande colorée verticale du variant + halo derrière l'icône */}
            <View style={{
              position: 'absolute', top: 0, left: 0, right: 0,
              height: 6, backgroundColor: v.accent,
            }} />

            {/* Halo doux sous l'icône */}
            <View style={{
              position: 'absolute', top: -30, left: '50%', marginLeft: -90,
              width: 180, height: 180, borderRadius: 90,
              backgroundColor: v.accentSoft,
              opacity: 0.5,
            }} />

            {/* Bloquer le tap sur la card pour ne pas fermer */}
            <TouchableOpacity activeOpacity={1} onPress={() => {}} style={{ padding: 28, paddingTop: 32 }}>

              {/* Icône en cercle, animée (pulse subtil pour success) */}
              <View style={{ alignItems: 'center', marginBottom: 18 }}>
                <View style={{
                  width: 72, height: 72, borderRadius: 999,
                  backgroundColor: v.iconBg,
                  borderWidth: 2, borderColor: v.accentBorder,
                  alignItems: 'center', justifyContent: 'center',
                  shadowColor: v.accent,
                  shadowOffset: { width: 0, height: 6 },
                  shadowOpacity: 0.20,
                  shadowRadius: 12,
                  elevation: 6,
                }}>
                  <Icon size={36} color={v.accent} />
                </View>
              </View>

              {/* Titre */}
              <Text
                style={{
                  fontSize: 20, fontWeight: '900',
                  color: '#0F172A',
                  textAlign: 'center',
                  letterSpacing: -0.4,
                  marginBottom: message ? 8 : 0,
                }}
              >
                {title}
              </Text>

              {/* Message (optionnel) */}
              {!!message && (
                <Text
                  style={{
                    fontSize: 14, color: '#475569',
                    textAlign: 'center',
                    lineHeight: 21,
                    marginBottom: 4,
                  }}
                >
                  {message}
                </Text>
              )}

              {/* Boutons — empilés verticalement si > 1 */}
              <View style={{ marginTop: 22, gap: 8 }}>
                {finalButtons.map((btn, i) => {
                  const isPrimary = (btn.variant ?? 'primary') === 'primary';
                  const isDanger  = btn.variant === 'danger';
                  const isSec     = btn.variant === 'secondary';

                  let bg: string;
                  let textColor: string;
                  let border: string | undefined;

                  if (isDanger) {
                    bg = '#DC2626';
                    textColor = 'white';
                    border = undefined;
                  } else if (isSec) {
                    bg = 'white';
                    textColor = '#475569';
                    border = '#E2E8F0';
                  } else {
                    // primary → couleur du variant
                    bg = v.accent;
                    textColor = 'white';
                    border = undefined;
                  }

                  return (
                    <TouchableOpacity
                      key={i}
                      activeOpacity={0.85}
                      onPress={() => handleButtonPress(btn)}
                      style={{
                        paddingVertical: 13, paddingHorizontal: 18,
                        borderRadius: 12,
                        backgroundColor: bg,
                        borderWidth: border ? 1.5 : 0,
                        borderColor: border,
                        alignItems: 'center',
                        shadowColor: isPrimary || isDanger ? bg : 'transparent',
                        shadowOffset: isPrimary || isDanger ? { width: 0, height: 4 } : undefined,
                        shadowOpacity: isPrimary || isDanger ? 0.25 : 0,
                        shadowRadius: isPrimary || isDanger ? 8 : 0,
                        elevation: isPrimary || isDanger ? 2 : 0,
                      }}
                    >
                      <Text style={{ fontSize: 14, fontWeight: '900', color: textColor, letterSpacing: 0.2 }}>
                        {btn.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
            </TouchableOpacity>
          </Animated.View>
        </TouchableOpacity>
      </Animated.View>
    </Modal>
  );
}
