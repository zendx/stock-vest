import React, {createContext, useContext, ReactNode, useMemo, useState} from 'react';
import {Theme as NavigationTheme} from '@react-navigation/native';

const lightPalette = {
  primary: '#1B7F3A',
  accent: '#28B463',
  accentSoft: '#E5F6EC',
  background: '#F6F7FB',
  surface: '#FFFFFF',
  surfaceAlt: '#F1F5F9',
  border: '#E5E7EB',
  text: '#0F172A',
  muted: '#6B7280',
  warning: '#F59E0B',
  danger: '#EF4444',
  success: '#16A34A',
  info: '#0EA5E9',
};

const darkPalette = {
  primary: '#28B463',
  accent: '#2DD4BF',
  accentSoft: '#1F3B2A',
  background: '#0B1020',
  surface: '#111827',
  surfaceAlt: '#0F172A',
  border: '#1F2937',
  text: '#E5E7EB',
  muted: '#9CA3AF',
  warning: '#F59E0B',
  danger: '#F87171',
  success: '#34D399',
  info: '#38BDF8',
};

const spacing = [4, 8, 12, 16, 20, 24, 28, 32];

export type AppTheme = {
  palette: typeof lightPalette;
  spacing: typeof spacing;
  radius: {
    xs: number;
    sm: number;
    md: number;
    lg: number;
    xl: number;
  };
  typography: {
    title: {
      regular: string;
      medium: string;
      bold: string;
    };
    body: {
      regular: string;
      medium: string;
      bold: string;
    };
  };
  navTheme: NavigationTheme;
};

type ThemeContextValue = AppTheme & {
  mode: 'light' | 'dark';
  setMode: (mode: 'light' | 'dark') => void;
  toggleMode: () => void;
};

const makeTheme = (palette: typeof lightPalette, dark: boolean): AppTheme => ({
  palette,
  spacing,
  radius: {
    xs: 6,
    sm: 10,
    md: 14,
    lg: 18,
    xl: 24,
  },
  typography: {
    title: {
      regular: 'Lexend_400Regular',
      medium: 'Lexend_600SemiBold',
      bold: 'Lexend_700Bold',
    },
    body: {
      regular: 'OpenSans_400Regular',
      medium: 'OpenSans_600SemiBold',
      bold: 'OpenSans_700Bold',
    },
  },
  navTheme: {
    dark,
    colors: {
      primary: palette.primary,
      background: palette.background,
      card: palette.surface,
      text: palette.text,
      border: palette.border,
      notification: palette.accent,
    },
  },
});

const ThemeContext = createContext<ThemeContextValue>({
  ...makeTheme(lightPalette, false),
  mode: 'light',
  setMode: () => {},
  toggleMode: () => {},
});

export const ThemeProvider = ({children}: {children: ReactNode}) => {
  const [mode, setMode] = useState<'light' | 'dark'>('light');
  const theme = useMemo(() => makeTheme(mode === 'dark' ? darkPalette : lightPalette, mode === 'dark'), [mode]);

  const value = useMemo(
    () => ({
      ...theme,
      mode,
      setMode,
      toggleMode: () => setMode((m) => (m === 'dark' ? 'light' : 'dark')),
    }),
    [theme, mode],
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export const useTheme = () => useContext(ThemeContext);
