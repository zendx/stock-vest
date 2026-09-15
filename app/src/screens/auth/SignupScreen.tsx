<<<<<<< HEAD
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
=======
import React, {useState} from 'react';
import {Image, TextInput, View, StyleSheet, Pressable} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Screen} from '../../components/Screen';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';
import {useSession} from '../../hooks/useSession';

const SignupScreen = () => {
  const theme = useTheme();
  const navigation = useNavigation();
  const {signup, status, error} = useSession();
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    referralCode: '',
  });
  const [showErrors, setShowErrors] = useState(false);

  const updateField = (key: keyof typeof form, value: string) => setForm({...form, [key]: value});

  const handleSignup = async () => {
    setShowErrors(true);
    if (!form.name || !form.email || !form.password || !form.referralCode) return;
    try {
      await signup({
        name: form.name,
        email: form.email,
        phone: form.phone || undefined,
        password: form.password,
        referralCode: form.referralCode,
      });
    } catch {
      // handled via session error state
    }
  };

  const inputStyle = [
    styles.input,
    {borderColor: theme.palette.border, color: theme.palette.text, backgroundColor: theme.palette.surface},
  ];

  return (
    <Screen requireAuth={false}>
      <View style={{alignItems: 'center', marginTop: 12}}>
        <View style={[styles.logoWrap, {backgroundColor: theme.palette.primary + '11', borderColor: theme.palette.border}]}>
          <Image source={require('../../../assets/logo.png')} style={{width: 50, height: 50, resizeMode: 'contain'}} />
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        </View>
        <Typography variant="title" weight="bold" style={{marginTop: 18}}>
          Create Account
        </Typography>
<<<<<<< HEAD
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
=======
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 6}}>
          Onboard with the same signup fields as the WordPress plugin.
        </Typography>
      </View>

      <View style={{gap: 12, marginTop: 24}}>
        <TextInput
          placeholder="Full name"
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
          value={form.name}
          onChangeText={(text) => updateField('name', text)}
        />
        <TextInput
          placeholder="Email"
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
          keyboardType="email-address"
          autoCapitalize="none"
          value={form.email}
          onChangeText={(text) => updateField('email', text)}
        />
        <TextInput
          placeholder="Phone"
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
          keyboardType="phone-pad"
          value={form.phone}
          onChangeText={(text) => updateField('phone', text)}
        />
        <TextInput
          placeholder="Password"
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
          secureTextEntry
          value={form.password}
          onChangeText={(text) => updateField('password', text)}
        />
        <TextInput
          placeholder="Referral code"
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
          value={form.referralCode}
          onChangeText={(text) => updateField('referralCode', text)}
        />
      </View>

      <PrimaryButton
        label={status === 'loading' ? 'Creating account...' : 'Sign Up'}
        fullWidth
        style={{marginTop: 18}}
        disabled={status === 'loading' || !form.name || !form.email || !form.password || !form.referralCode}
        onPress={status === 'loading' ? undefined : handleSignup}
      />
      {showErrors && (!form.referralCode || !form.name || !form.email || !form.password) ? (
        <Typography variant="caption" style={{color: theme.palette.warning, marginTop: 10}}>
          Please complete all required fields, including referral code.
        </Typography>
      ) : null}
      {error ? (
        <Typography variant="caption" style={{color: theme.palette.warning, marginTop: 10}}>
          {error}
        </Typography>
      ) : null}
      <View style={{flexDirection: 'row', justifyContent: 'center', marginTop: 16}}>
        <Typography variant="caption" style={{color: theme.palette.muted}}>
          Already have an account?{' '}
        </Typography>
        <Pressable onPress={() => navigation.navigate('Login' as never)}>
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
          <Typography variant="caption" style={{color: theme.palette.primary, fontWeight: '700'}}>
            Sign In
          </Typography>
        </Pressable>
      </View>
    </Screen>
  );
};

const styles = StyleSheet.create({
<<<<<<< HEAD
  header: {
    alignItems: 'center',
    marginTop: 12,
=======
  input: {
    borderWidth: 1,
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 14,
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  },
  logoWrap: {
    width: 84,
    height: 84,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
<<<<<<< HEAD
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
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
});

export default SignupScreen;
