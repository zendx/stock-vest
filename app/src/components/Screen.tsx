import React, {ReactNode, useEffect} from 'react';
import {KeyboardAvoidingView, Platform, ScrollView, StatusBar, StyleSheet, View} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
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
        bottomInset && {paddingBottom: theme.spacing[6]},
      ]}
    >
      {children}
    </View>
  );

  return (
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
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  keyboardAvoidingView: {
    flex: 1,
  },
  container: {
    flexGrow: 1,
    paddingTop: 12,
  },
});
