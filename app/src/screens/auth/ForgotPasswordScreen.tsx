<<<<<<< HEAD
import React from 'react';
=======
import React, {useState} from 'react';
import {Alert, StyleSheet, TextInput, View} from 'react-native';
import {useNavigation} from '@react-navigation/native';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
import {Screen} from '../../components/Screen';
import {Surface} from '../../components/Surface';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';
<<<<<<< HEAD
import {openWebFlow} from '../../lib/openWebFlow';

const ForgotPasswordScreen = () => {
  const theme = useTheme();
  const handleSubmit = () => openWebFlow('/wsi/forgot-password/', 'Password reset');
=======

const ForgotPasswordScreen = () => {
  const theme = useTheme();
  const navigation = useNavigation();
  const [email, setEmail] = useState('');

  const handleSubmit = () => {
    if (!email) {
      Alert.alert('Enter email', 'Add the account email to receive a reset link.');
      return;
    }
    Alert.alert('Reset link sent', 'Check your inbox for password reset instructions.');
    navigation.goBack();
  };
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

  return (
    <Screen requireAuth={false}>
      <Surface>
        <Typography variant="subtitle" weight="medium">
          Reset password
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
<<<<<<< HEAD
          Continue to the secure COFCO Capital page to request a reset link.
        </Typography>

        <PrimaryButton label="Open password reset" fullWidth style={{marginTop: 16}} onPress={handleSubmit} />
=======
          Stay inside the app to request a reset link.
        </Typography>

        <View style={{marginTop: 16}}>
          <TextInput
            placeholder="Email address"
            placeholderTextColor={theme.palette.muted}
            style={[
              styles.input,
              {borderColor: theme.palette.border, backgroundColor: theme.palette.surface, color: theme.palette.text},
            ]}
            keyboardType="email-address"
            autoCapitalize="none"
            value={email}
            onChangeText={setEmail}
          />
        </View>

        <PrimaryButton label="Send reset link" fullWidth style={{marginTop: 16}} onPress={handleSubmit} disabled={!email} />
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
      </Surface>
    </Screen>
  );
};

<<<<<<< HEAD
=======
const styles = StyleSheet.create({
  input: {
    borderWidth: 1,
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 14,
  },
});

>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
export default ForgotPasswordScreen;
