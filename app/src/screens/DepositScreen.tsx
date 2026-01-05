import React, {useState} from 'react';
import {Alert, StyleSheet, TextInput, View} from 'react-native';
import {useMutation} from '@tanstack/react-query';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {submitDeposit} from '../api/portfolio';
import {useSession} from '../hooks/useSession';
import {queryClient} from '../lib/queryClient';

const DepositScreen = () => {
  const theme = useTheme();
  const {token} = useSession();
  const [amount, setAmount] = useState('');
  const [method, setMethod] = useState('Bank transfer');
  const [note, setNote] = useState('');

  const mutation = useMutation({
    mutationFn: (payload: {amount: string; method: string; note?: string}) => submitDeposit(payload, token),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['balances']});
      queryClient.invalidateQueries({queryKey: ['transactions']});
      Alert.alert('Deposit submitted', 'Your deposit request is captured in-app. Track status from Activity.');
    },
    onError: (err: any) => {
      Alert.alert('Deposit failed', err?.message || 'Unable to submit deposit right now.');
    },
  });

  const handleSubmit = () => {
    if (!amount) {
      Alert.alert('Enter an amount', 'Add a deposit amount to continue.');
      return;
    }
    mutation.mutate({amount: amount.trim(), method: method.trim() || 'Bank transfer', note: note.trim() || undefined});
  };

  const inputStyle = [
    styles.input,
    {borderColor: theme.palette.border, backgroundColor: theme.palette.surface, color: theme.palette.text},
  ];

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Deposit
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Add funds without leaving the app. Mirrors the WordPress deposit flow.
        </Typography>
      </View>

      <Surface>
        <Typography variant="subtitle" weight="medium">
          New Deposit
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
          Submit deposits without leaving the app. Wire this call to your API endpoint.
        </Typography>

        <View style={{gap: 12, marginTop: 14}}>
          <TextInput
            placeholder="Amount (USD)"
            placeholderTextColor={theme.palette.muted}
            keyboardType="decimal-pad"
            style={inputStyle}
            value={amount}
            onChangeText={setAmount}
          />
          <TextInput
            placeholder="Method (e.g. Bank transfer, USDT)"
            placeholderTextColor={theme.palette.muted}
            style={inputStyle}
            value={method}
            onChangeText={setMethod}
          />
          <TextInput
            placeholder="Note (reference / bank name)"
            placeholderTextColor={theme.palette.muted}
            style={[...inputStyle, {height: 80, textAlignVertical: 'top'}]}
            value={note}
            onChangeText={setNote}
            multiline
          />
        </View>

        <PrimaryButton
          label={mutation.isPending ? 'Submitting...' : 'Submit deposit'}
          fullWidth
          style={{marginTop: 16}}
          onPress={mutation.isPending ? undefined : handleSubmit}
          disabled={!amount || mutation.isPending}
        />
        {mutation.isPending ? (
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 8}}>
            Sending deposit request...
          </Typography>
        ) : null}
      </Surface>

      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#E5F6EC'}]}>
            <Ionicons name="arrow-down-circle-outline" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Supported methods</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
              Bank/manual deposits or crypto per your WordPress settings. Update API wiring as needed.
            </Typography>
          </View>
        </View>
      </Surface>
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
    marginBottom: 12,
  },
  input: {
    borderWidth: 1,
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 14,
  },
  iconBadge: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default DepositScreen;
