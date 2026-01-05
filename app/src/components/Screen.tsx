import React, {ReactNode, useEffect} from 'react';
import {ScrollView, StatusBar, StyleSheet, View} from 'react-native';
import {SafeAreaView, useSafeAreaInsets} from 'react-native-safe-area-context';
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
  const insets = useSafeAreaInsets();
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
        {paddingTop: Math.max(insets.top, 12)},
        bottomInset && {paddingBottom: theme.spacing[6]},
      ]}
    >
      {children}
    </View>
  );

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: theme.palette.background}} edges={['top', 'left', 'right', 'bottom']}>
      <StatusBar barStyle="dark-content" backgroundColor={theme.palette.background} translucent={false} />
      {scroll ? <ScrollView showsVerticalScrollIndicator={false}>{content}</ScrollView> : content}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flexGrow: 1,
    paddingTop: 12,
  },
});
