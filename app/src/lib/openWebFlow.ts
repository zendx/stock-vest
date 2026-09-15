import {Alert, Linking} from 'react-native';
import Constants from 'expo-constants';
<<<<<<< HEAD
import {siteBaseUrl} from '../api/client';
=======
import {apiBaseUrl} from '../api/client';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

const normalizeBase = () => {
  const envBase = process.env.EXPO_PUBLIC_WEB_BASE_URL || (Constants.expoConfig?.extra as any)?.webBaseUrl;
  if (envBase) return envBase.endsWith('/') ? envBase.slice(0, -1) : envBase;
<<<<<<< HEAD
  return siteBaseUrl;
=======
  // Fallback to API host if available
  try {
    if (apiBaseUrl) {
      const url = new URL(apiBaseUrl);
      return `${url.protocol}//${url.host}`;
    }
  } catch {
    // ignore
  }
  return '';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
};

export const openWebFlow = async (path: string, label: string) => {
  const base = normalizeBase();
  if (!base) {
<<<<<<< HEAD
    Alert.alert(`${label} unavailable`, 'The secure website address is not configured.');
=======
    Alert.alert(`${label} unavailable`, 'Set EXPO_PUBLIC_WEB_BASE_URL to your WordPress site URL to open this flow.');
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    return false;
  }
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  const url = `${base}${normalizedPath}`;
  const supported = await Linking.canOpenURL(url);
  if (!supported) {
    Alert.alert(`${label} unavailable`, 'Unable to open the web flow on this device.');
    return false;
  }
  await Linking.openURL(url);
  return true;
};
