import { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { AlertDialog, AlertVariant, AlertButton } from '../components/AlertDialog';

/**
 * Contexte global pour afficher des alertes custom (= remplace Alert.alert
 * natif). Une seule instance d'AlertDialog est rendue à la racine de l'app.
 *
 * Usage :
 *   const { showAlert } = useAlert();
 *   showAlert({ title: 'Échec', message: '…', variant: 'error' });
 *
 *   // Avec boutons custom :
 *   showAlert({
 *     title: 'Confirmer ?',
 *     message: 'Cette action est irréversible.',
 *     variant: 'warning',
 *     buttons: [
 *       { label: 'Annuler', variant: 'secondary' },
 *       { label: 'Supprimer', variant: 'danger', onPress: () => doDelete() },
 *     ],
 *   });
 */

interface AlertOptions {
  title: string;
  message?: string;
  variant?: AlertVariant;
  buttons?: AlertButton[];
  dismissibleOnBackdrop?: boolean;
}

interface AlertContextValue {
  showAlert: (opts: AlertOptions) => void;
}

const AlertContext = createContext<AlertContextValue | null>(null);

export function AlertProvider({ children }: { children: ReactNode }) {
  const [visible, setVisible] = useState(false);
  const [opts, setOpts]       = useState<AlertOptions>({ title: '' });

  const showAlert = useCallback((newOpts: AlertOptions) => {
    setOpts(newOpts);
    setVisible(true);
  }, []);

  const hide = useCallback(() => setVisible(false), []);

  return (
    <AlertContext.Provider value={{ showAlert }}>
      {children}
      <AlertDialog
        visible={visible}
        title={opts.title}
        message={opts.message}
        variant={opts.variant}
        buttons={opts.buttons}
        dismissibleOnBackdrop={opts.dismissibleOnBackdrop}
        onDismiss={hide}
      />
    </AlertContext.Provider>
  );
}

export function useAlert(): AlertContextValue {
  const ctx = useContext(AlertContext);
  if (!ctx) {
    throw new Error('useAlert must be used within an AlertProvider');
  }
  return ctx;
}
