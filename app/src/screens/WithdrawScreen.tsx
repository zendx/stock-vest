import React, {useState} from 'react';
import {Alert, Pressable, StyleSheet, TextInput, View} from 'react-native';
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
import {showFinancialFlowError} from '../lib/financialFlow';
import {useBalances} from '../hooks/useBalances';
import type {WithdrawalRequest, WithdrawalSource} from '../types';

const WithdrawScreen = () => {
  const theme = useTheme();
  const {token} = useSession();
  const [amount, setAmount] = useState('');
  const [destination, setDestination] = useState('');
  const [note, setNote] = useState('');
  const [source, setSource] = useState<WithdrawalSource>('available_balance');
  const {data: balances, error: balanceError} = useBalances();
  const assetsLocked = balances?.totalAssetsLocked !== false;

  const mutation = useMutation({
    mutationFn: (payload: WithdrawalRequest) => submitWithdrawal(payload, token),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['balances']});
      queryClient.invalidateQueries({queryKey: ['transactions']});
      Alert.alert('Withdrawal submitted', 'Your withdrawal request is captured in-app. Track status from Activity.');
    },
    onError: (err: unknown, payload) => showFinancialFlowError(err, 'Withdrawal', `/wsi/withdrawal/?withdrawal_source=${payload.withdrawal_source}`),
  });

  const handleSubmit = () => {
    if (!balances || balanceError || (source === 'total_assets' && assetsLocked)) {
      Alert.alert('Account unavailable', 'Refresh your balances or select an unlocked account.');
      return;
    }
    const value = Number(amount.replace(/,/g, ''));
    if (!Number.isFinite(value) || value <= 0 || !destination.trim()) {
      Alert.alert('Add valid details', 'Enter an amount greater than zero and a destination.');
      return;
    }
    const available = source === 'total_assets' ? balances.totalAssetsUnlockedAmount : balances.available;
    if (value > available) {
      Alert.alert('Insufficient balance', 'The amount exceeds the balance in the selected account.');
      return;
    }
    mutation.mutate({
      amount: value.toFixed(2),
      withdrawal_source: source,
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
          Withdraw from Available Balance or unlocked Total Assets.
        </Typography>
      </View>

      <Surface>
        <Typography variant="subtitle" weight="medium">
          Withdraw Funds
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
          Enter the amount and destination details for this request.
        </Typography>

        <View style={{gap: 12, marginTop: 14}}>
          <Typography weight="medium">Withdraw from</Typography>
          {(['available_balance', 'total_assets'] as const).map(account => {
            const isAssets = account === 'total_assets';
            const locked = isAssets && assetsLocked;
            const value = isAssets ? balances?.totalAssetsUnlockedAmount : balances?.available;
            return (
              <Pressable
                key={account}
                accessibilityRole="radio"
                accessibilityState={{selected: source === account, disabled: locked || !balances || !!balanceError}}
                disabled={locked || !balances || !!balanceError || mutation.isPending}
                onPress={() => setSource(account)}
                style={[styles.input, {borderColor: source === account ? theme.palette.primary : theme.palette.border}]}
              >
                <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
                  <Ionicons name={source === account ? 'radio-button-on' : 'radio-button-off'} size={18} color={theme.palette.primary} />
                  <Typography>{isAssets ? 'Total Assets (unlocked)' : 'Available Balance'}: {value === undefined ? 'Loading...' : `$${value.toFixed(2)}`}</Typography>
                  {isAssets ? <Ionicons name={assetsLocked ? 'lock-closed-outline' : 'lock-open-outline'} size={18} color={assetsLocked ? theme.palette.danger : theme.palette.success} accessibilityLabel={assetsLocked ? 'Total assets locked' : 'Total assets unlocked'} /> : null}
                </View>
              </Pressable>
            );
          })}
          <Typography variant="caption" style={{color: theme.palette.muted}}>
            {balanceError ? 'Balances are temporarily unavailable.' : assetsLocked
              ? (balances?.totalAssetsUnlockAt ? `Total Assets unlock on ${new Date(balances.totalAssetsUnlockAt).toLocaleString()}.` : 'Total Assets withdrawals are locked until the investment period ends.')
              : 'Total Assets are unlocked for withdrawal.'}
          </Typography>
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
          disabled={!amount || !destination || mutation.isPending || !balances || !!balanceError || (source === 'total_assets' && assetsLocked)}
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
              Available payout methods and processing rules are confirmed before submission.
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
