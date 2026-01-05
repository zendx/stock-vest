import React, {useEffect, useState} from 'react';
import {QueryClientProvider} from '@tanstack/react-query';
import * as SplashScreen from 'expo-splash-screen';
import {StatusBar} from 'expo-status-bar';
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

export default function App() {
  const [ready, setReady] = useState(false);
  const [fontsLoaded] = useFonts({
    Lexend_400Regular,
    Lexend_600SemiBold,
    Lexend_700Bold,
    OpenSans_400Regular,
    OpenSans_600SemiBold,
    OpenSans_700Bold,
  });
  const {status: sessionStatus} = useSession();

  useEffect(() => {
    SplashScreen.preventAutoHideAsync().catch(() => {
      // ignore splash hide errors
    });
  }, []);

  useEffect(() => {
    useSession.getState().hydrate().catch(() => {});
  }, []);

  useEffect(() => {
    const prepare = async () => {
      try {
        // preload logo for splash/native navigation headers
        await Asset.loadAsync(require('./assets/logo.png'));
      } catch (err) {
        // no-op
      } finally {
        setReady(true);
      }
    };
    prepare();
  }, []);

  useEffect(() => {
    if (ready && fontsLoaded) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [ready, fontsLoaded]);

  if (!ready || !fontsLoaded || sessionStatus === 'hydrating') return null;

  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <QueryClientProvider client={queryClient}>
          <StatusBar style="dark" />
          <AppNavigator />
        </QueryClientProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
