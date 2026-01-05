import {Alert, Linking} from 'react-native';
import Constants from 'expo-constants';
import {apiBaseUrl} from '../api/client';

const normalizeBase = () => {
  const envBase = process.env.EXPO_PUBLIC_WEB_BASE_URL || (Constants.expoConfig?.extra as any)?.webBaseUrl;
  if (envBase) return envBase.endsWith('/') ? envBase.slice(0, -1) : envBase;
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
};

export const openWebFlow = async (path: string, label: string) => {
  const base = normalizeBase();
  if (!base) {
    Alert.alert(`${label} unavailable`, 'Set EXPO_PUBLIC_WEB_BASE_URL to your WordPress site URL to open this flow.');
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
