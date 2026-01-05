import React, {useState} from 'react';
import {Alert, StyleSheet, TextInput, View} from 'react-native';
import {useMutation} from '@tanstack/react-query';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useSession} from '../hooks/useSession';
import {submitWithdrawal} from '../api/portfolio';
import {queryClient} from '../lib/queryClient';

const WithdrawScreen = () => {
  const theme = useTheme();
  const {token} = useSession();
  const [amount, setAmount] = useState('');
  const [destination, setDestination] = useState('');
  const [note, setNote] = useState('');

  const mutation = useMutation({
    mutationFn: (payload: {amount: string; destination: string; note?: string}) => submitWithdrawal(payload, token),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['balances']});
      queryClient.invalidateQueries({queryKey: ['transactions']});
      Alert.alert('Withdrawal submitted', 'Your withdrawal request is captured in-app. Track status from Activity.');
    },
    onError: (err: any) => {
      Alert.alert('Withdrawal failed', err?.message || 'Unable to submit withdrawal right now.');
    },
  });

  const handleSubmit = () => {
    if (!amount || !destination) {
      Alert.alert('Add details', 'Include amount and destination to continue.');
      return;
    }
    mutation.mutate({
      amount: amount.trim(),
      destination: destination.trim(),
      note: note.trim() || undefined,
    });
  };

  const inputStyle = [
    styles.input,
    {borderColor: theme.palette.border, backgroundColor: theme.palette.surface, color: theme.palette.text},
  ];

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Withdraw
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Request withdrawals inside the app with the same rules as the WordPress flow.
        </Typography>
      </View>

      <Surface>
        <Typography variant="subtitle" weight="medium">
          Withdraw Funds
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
          Keep withdrawal processing in the app. Connect this action to your REST endpoint.
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
            placeholder="Destination account / wallet"
            placeholderTextColor={theme.palette.muted}
            style={inputStyle}
            value={destination}
            onChangeText={setDestination}
          />
          <TextInput
            placeholder="Note (optional)"
            placeholderTextColor={theme.palette.muted}
            style={[...inputStyle, {height: 80, textAlignVertical: 'top'}]}
            value={note}
            onChangeText={setNote}
            multiline
          />
        </View>

        <PrimaryButton
          label={mutation.isPending ? 'Submitting...' : 'Submit withdrawal'}
          fullWidth
          style={{marginTop: 16}}
          onPress={mutation.isPending ? undefined : handleSubmit}
          disabled={!amount || !destination || mutation.isPending}
        />
        {mutation.isPending ? (
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 8}}>
            Sending withdrawal request...
          </Typography>
        ) : null}
      </Surface>

      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#FDECEC'}]}>
            <Ionicons name="arrow-up-circle-outline" size={18} color={theme.palette.danger} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Payout methods</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
              Crypto or bank per plugin settings. Adjust defaults via env if needed.
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

export default WithdrawScreen;
