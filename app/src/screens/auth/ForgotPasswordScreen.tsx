import React, {useState} from 'react';
import {Alert, StyleSheet, TextInput, View} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Screen} from '../../components/Screen';
import {Surface} from '../../components/Surface';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';

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

  return (
    <Screen requireAuth={false}>
      <Surface>
        <Typography variant="subtitle" weight="medium">
          Reset password
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
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
      </Surface>
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
});

export default ForgotPasswordScreen;
