import React, {useState} from 'react';
import {Image, TextInput, View, StyleSheet, Pressable, Switch} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Screen} from '../../components/Screen';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';
import {useSession} from '../../hooks/useSession';

const LoginScreen = () => {
  const theme = useTheme();
  const navigation = useNavigation();
  const {login, status, error} = useSession();
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(true);

  const handleLogin = async () => {
    try {
      await login({identifier, password, remember});
    } catch {
      // handled via session error state
    }
  };

  const inputStyle = [
    styles.input,
    {
      borderColor: theme.palette.border,
      color: theme.palette.text,
      backgroundColor: theme.palette.surface,
    },
  ];

  return (
    <Screen requireAuth={false}>
      <View style={{alignItems: 'center', marginTop: 12}}>
        <View style={[styles.logoWrap, {backgroundColor: theme.palette.primary + '11', borderColor: theme.palette.border}]}>
          <Image source={require('../../../assets/logo.png')} style={{width: 50, height: 50, resizeMode: 'contain'}} />
        </View>
        <Typography variant="title" weight="bold" style={{marginTop: 18}}>
          Welcome Back
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 6}}>
          Sign in to continue
        </Typography>
      </View>

      <View style={{gap: 12, marginTop: 24}}>
        <TextInput
          placeholder="Email or Username"
          value={identifier}
          autoCapitalize="none"
          keyboardType="email-address"
          onChangeText={setIdentifier}
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
        />
        <TextInput
          placeholder="Enter your password"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
          placeholderTextColor={theme.palette.muted}
          style={inputStyle}
        />
      </View>

      <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 12}}>
        <Pressable onPress={() => setRemember(!remember)} style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
          <Switch
            value={remember}
            onValueChange={setRemember}
            thumbColor={remember ? theme.palette.surface : '#ffffff'}
            trackColor={{true: theme.palette.primary, false: theme.palette.border}}
          />
          <Typography variant="caption" style={{color: theme.palette.text}}>
            Remember me
          </Typography>
        </Pressable>
        <Pressable onPress={() => navigation.navigate('ForgotPassword' as never)}>
          <Typography variant="caption" style={{color: theme.palette.primary, fontWeight: '600'}}>
            Forgot Password?
          </Typography>
        </Pressable>
      </View>

      <PrimaryButton
        label={status === 'loading' ? 'Signing in...' : 'Sign In'}
        fullWidth
        style={{marginTop: 18}}
        disabled={!identifier || !password || status === 'loading'}
        onPress={status === 'loading' ? undefined : handleLogin}
      />
      {error ? (
        <Typography variant="caption" style={{color: theme.palette.warning, marginTop: 10}}>
          {error}
        </Typography>
      ) : null}
      <View style={{flexDirection: 'row', justifyContent: 'center', marginTop: 16}}>
        <Typography variant="caption" style={{color: theme.palette.muted}}>
          Don&apos;t have an account?{' '}
        </Typography>
        <Pressable onPress={() => navigation.navigate('Signup' as never)}>
          <Typography variant="caption" style={{color: theme.palette.primary, fontWeight: '700'}}>
            Sign Up
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

export default LoginScreen;
