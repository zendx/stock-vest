import React, {useEffect, useState} from 'react';
import {QueryClientProvider} from '@tanstack/react-query';
import * as SplashScreen from 'expo-splash-screen';
<<<<<<< HEAD
=======
import {StatusBar} from 'expo-status-bar';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
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

<<<<<<< HEAD
void SplashScreen.preventAutoHideAsync().catch(() => {
  // The splash may already be controlled by the native runtime.
});

export default function App() {
  const [ready, setReady] = useState(false);
  const [fontsLoaded, fontError] = useFonts({
=======
export default function App() {
  const [ready, setReady] = useState(false);
  const [fontsLoaded] = useFonts({
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    Lexend_400Regular,
    Lexend_600SemiBold,
    Lexend_700Bold,
    OpenSans_400Regular,
    OpenSans_600SemiBold,
    OpenSans_700Bold,
  });
  const {status: sessionStatus} = useSession();
<<<<<<< HEAD
  const appReady = ready && (fontsLoaded || Boolean(fontError)) && sessionStatus !== 'hydrating';
=======

  useEffect(() => {
    SplashScreen.preventAutoHideAsync().catch(() => {
      // ignore splash hide errors
    });
  }, []);
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

  useEffect(() => {
    useSession.getState().hydrate().catch(() => {});
  }, []);

  useEffect(() => {
    const prepare = async () => {
      try {
        // preload logo for splash/native navigation headers
        await Asset.loadAsync(require('./assets/logo.png'));
<<<<<<< HEAD
      } catch {
=======
      } catch (err) {
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        // no-op
      } finally {
        setReady(true);
      }
    };
    prepare();
  }, []);

  useEffect(() => {
<<<<<<< HEAD
    if (appReady) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [appReady]);

  if (!appReady) return null;
=======
    if (ready && fontsLoaded) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [ready, fontsLoaded]);

  if (!ready || !fontsLoaded || sessionStatus === 'hydrating') return null;
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <QueryClientProvider client={queryClient}>
<<<<<<< HEAD
=======
          <StatusBar style="dark" />
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
          <AppNavigator />
        </QueryClientProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
