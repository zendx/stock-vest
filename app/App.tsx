import React, {useEffect, useState} from 'react';
import {QueryClientProvider} from '@tanstack/react-query';
import * as SplashScreen from 'expo-splash-screen';
import {Asset} from 'expo-asset';
import {
  useFonts,
  Lexend_400Regular,
  Lexend_600SemiBold,
  Lexend_700Bold,
} from '@expo-google-fonts/lexend';
import {
  OpenSans_400Regular,
  OpenSans_600SemiBold,
  OpenSans_700Bold,
} from '@expo-google-fonts/open-sans';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import {queryClient} from './src/lib/queryClient';
import {ThemeProvider} from './src/theme';
import AppNavigator from './src/navigation/AppNavigator';
import {useSession} from './src/hooks/useSession';

void SplashScreen.preventAutoHideAsync().catch(() => {
  // The splash may already be controlled by the native runtime.
});

export default function App() {
  const [ready, setReady] = useState(false);
  const [fontsLoaded, fontError] = useFonts({
    Lexend_400Regular,
    Lexend_600SemiBold,
    Lexend_700Bold,
    OpenSans_400Regular,
    OpenSans_600SemiBold,
    OpenSans_700Bold,
  });
  const {status: sessionStatus} = useSession();
  const appReady = ready && (fontsLoaded || Boolean(fontError)) && sessionStatus !== 'hydrating';

  useEffect(() => {
    useSession.getState().hydrate().catch(() => {});
  }, []);

  useEffect(() => {
    const prepare = async () => {
      try {
        // preload logo for splash/native navigation headers
        await Asset.loadAsync(require('./assets/logo.png'));
      } catch {
        // no-op
      } finally {
        setReady(true);
      }
    };
    prepare();
  }, []);

  useEffect(() => {
    if (appReady) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [appReady]);

  if (!appReady) return null;

  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <QueryClientProvider client={queryClient}>
          <AppNavigator />
        </QueryClientProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
