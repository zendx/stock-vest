import React from 'react';
import {Image, Pressable, StyleSheet, View} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Screen} from '../../components/Screen';
import {Surface} from '../../components/Surface';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';
import type {AuthNavigationProp} from '../../navigation/types';
import {openWebFlow} from '../../lib/openWebFlow';

const SignupScreen = () => {
  const theme = useTheme();
  const navigation = useNavigation<AuthNavigationProp<'Signup'>>();

  return (
    <Screen requireAuth={false}>
      <View style={styles.header}>
        <View style={[styles.logoWrap, {backgroundColor: theme.palette.primary + '11', borderColor: theme.palette.border}]}>
          <Image source={require('../../../assets/logo.png')} style={styles.logo} />
        </View>
        <Typography variant="title" weight="bold" style={{marginTop: 18}}>
          Create Account
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 6, textAlign: 'center'}}>
          Registration uses a secure invite-code flow.
        </Typography>
      </View>

      <Surface style={{marginTop: 24}}>
        <Typography variant="subtitle" weight="medium">
          Have your invite code ready
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 8}}>
          Continue to COFCO Capital to validate your invite and create the account. Return here afterward to sign in.
        </Typography>
        <PrimaryButton
          label="Open secure registration"
          fullWidth
          style={{marginTop: 18}}
          onPress={() => void openWebFlow('/wsi/signup/', 'Registration')}
        />
      </Surface>

      <View style={styles.signInRow}>
        <Typography variant="caption" style={{color: theme.palette.muted}}>
          Already have an account?{' '}
        </Typography>
        <Pressable accessibilityRole="button" onPress={() => navigation.navigate('Login')}>
          <Typography variant="caption" style={{color: theme.palette.primary, fontWeight: '700'}}>
            Sign In
          </Typography>
        </Pressable>
      </View>
    </Screen>
  );
};

const styles = StyleSheet.create({
  header: {
    alignItems: 'center',
    marginTop: 12,
  },
  logoWrap: {
    width: 84,
    height: 84,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  logo: {
    width: 58,
    height: 58,
    resizeMode: 'contain',
  },
  signInRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 18,
  },
});

export default SignupScreen;
