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
        </View>
        <Typography variant="title" weight="bold" style={{marginTop: 18}}>
          Create Account
        </Typography>
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
          <Typography variant="caption" style={{color: theme.palette.primary, fontWeight: '700'}}>
            Sign In
          </Typography>
        </Pressable>
      </View>
    </Screen>
  );
};

const styles = StyleSheet.create({
  input: {
    borderWidth: 1,
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 14,
  },
  logoWrap: {
    width: 84,
    height: 84,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
});

export default SignupScreen;
