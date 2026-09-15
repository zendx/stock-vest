import React, {ReactNode, useEffect} from 'react';
<<<<<<< HEAD
import {KeyboardAvoidingView, Platform, ScrollView, StatusBar, StyleSheet, View} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
=======
import {ScrollView, StatusBar, StyleSheet, View} from 'react-native';
import {SafeAreaView, useSafeAreaInsets} from 'react-native-safe-area-context';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
import {useTheme} from '../theme';
import {useSession} from '../hooks/useSession';

type ScreenProps = {
  children: ReactNode;
  padded?: boolean;
  scroll?: boolean;
  bottomInset?: boolean;
  requireAuth?: boolean;
};

export const Screen = ({
  children,
  padded = true,
  scroll = true,
  bottomInset = true,
  requireAuth = true,
}: ScreenProps) => {
  const theme = useTheme();
  const {isAuthenticated, hydrated, status, token, logout} = useSession();
<<<<<<< HEAD
=======
  const insets = useSafeAreaInsets();
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  const gating = requireAuth;

  useEffect(() => {
    if (!gating) return;
    if (!hydrated || status === 'hydrating') return;
    if (!isAuthenticated || !token) {
      logout().catch(() => {});
    }
  }, [gating, hydrated, status, isAuthenticated, token, logout]);

  if (gating && (!hydrated || status === 'hydrating' || !isAuthenticated || !token)) {
    return null;
  }
  const content = (
    <View
      style={[
        styles.container,
        {backgroundColor: theme.palette.background},
        padded && {paddingHorizontal: theme.spacing[5]},
<<<<<<< HEAD
=======
        {paddingTop: Math.max(insets.top, 12)},
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        bottomInset && {paddingBottom: theme.spacing[6]},
      ]}
    >
      {children}
    </View>
  );

  return (
<<<<<<< HEAD
    <SafeAreaView
      style={[styles.safeArea, {backgroundColor: theme.palette.background}]}
      edges={bottomInset ? ['top', 'left', 'right', 'bottom'] : ['top', 'left', 'right']}
    >
      <StatusBar
        barStyle={theme.mode === 'dark' ? 'light-content' : 'dark-content'}
        backgroundColor={theme.palette.background}
        translucent={false}
      />
      <KeyboardAvoidingView
        style={styles.keyboardAvoidingView}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        {scroll ? (
          <ScrollView
            showsVerticalScrollIndicator={false}
            keyboardDismissMode={Platform.OS === 'ios' ? 'interactive' : 'on-drag'}
            keyboardShouldPersistTaps="handled"
          >
            {content}
          </ScrollView>
        ) : (
          content
        )}
      </KeyboardAvoidingView>
=======
    <SafeAreaView style={{flex: 1, backgroundColor: theme.palette.background}} edges={['top', 'left', 'right', 'bottom']}>
      <StatusBar barStyle="dark-content" backgroundColor={theme.palette.background} translucent={false} />
      {scroll ? <ScrollView showsVerticalScrollIndicator={false}>{content}</ScrollView> : content}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
<<<<<<< HEAD
  safeArea: {
    flex: 1,
  },
  keyboardAvoidingView: {
    flex: 1,
  },
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  container: {
    flexGrow: 1,
    paddingTop: 12,
  },
});
