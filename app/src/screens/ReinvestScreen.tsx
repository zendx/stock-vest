import React, {useMemo, useState} from 'react';
import {Alert, StyleSheet, TextInput, View, Pressable} from 'react-native';
import {useMutation} from '@tanstack/react-query';
<<<<<<< HEAD
import {useRoute} from '@react-navigation/native';
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useSession} from '../hooks/useSession';
import {submitReinvest} from '../api/portfolio';
import {queryClient} from '../lib/queryClient';
import {useBalances} from '../hooks/useBalances';
<<<<<<< HEAD
import {showFinancialFlowError} from '../lib/financialFlow';
import type {MainStackRouteProp} from '../navigation/types';
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

const formatCurrency = (value: number) =>
  `$${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

const ReinvestScreen = () => {
  const theme = useTheme();
<<<<<<< HEAD
  const route = useRoute<MainStackRouteProp<'Reinvest'>>();
  const {token} = useSession();
  const [amount, setAmount] = useState(route.params?.amount || '');
=======
  const {token} = useSession();
  const [amount, setAmount] = useState('');
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  const [note, setNote] = useState('');
  const {data: balances} = useBalances();

  const profit = useMemo(() => balances?.profit ?? 0, [balances]);

  const mutation = useMutation({
    mutationFn: (payload: {amount: string; note?: string}) => submitReinvest(payload, token),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['balances']});
      queryClient.invalidateQueries({queryKey: ['transactions']});
      Alert.alert('Reinvested', 'Funds reinvested in-app.');
    },
<<<<<<< HEAD
    onError: (err: unknown) => showFinancialFlowError(err, 'Reinvestment', '/wsi/reinvest/'),
  });

  const handleSubmit = () => {
    const value = Number(amount.replace(/,/g, ''));
    if (!Number.isFinite(value) || value <= 0) {
      Alert.alert('Add a valid amount', 'Reinvestment amount must be greater than zero.');
      return;
    }
    if (value > profit) {
      Alert.alert('Amount too high', 'Reinvestment amount cannot exceed your available profit.');
=======
    onError: (err: any) => {
      Alert.alert('Reinvest failed', err?.message || 'Unable to reinvest right now.');
    },
  });

  const handleSubmit = () => {
    if (!amount) {
      Alert.alert('Add amount', 'Enter the amount to reinvest.');
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
      return;
    }
    mutation.mutate({amount: amount.trim(), note: note.trim() || undefined});
  };

  const applyPercent = (pct: number) => {
    const value = (profit * pct) / 100;
    setAmount(value ? value.toFixed(2) : '');
  };

  const inputStyle = [
    styles.input,
    {borderColor: theme.palette.border, backgroundColor: theme.palette.surface, color: theme.palette.text},
  ];

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Reinvest
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Move profit balance into your main balance to keep it working for you.
        </Typography>
      </View>

      <Surface style={{marginTop: 12}}>
        <Typography variant="subtitle" weight="medium">
          Reinvest Profit
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 6}}>
<<<<<<< HEAD
          Choose how much of your available profit to put back to work.
=======
          Mirror the reinvest flow from WordPress without leaving the app.
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        </Typography>

        <View style={{marginTop: 14}}>
          <Typography variant="caption" style={{color: theme.palette.muted}}>
            Available Profit Balance
          </Typography>
          <Typography variant="title" weight="bold" style={{marginTop: 6, color: theme.palette.success}}>
            {formatCurrency(profit)}
          </Typography>
        </View>

        <View style={styles.percentRow}>
          {[25, 50, 100].map((pct) => (
            <Pressable key={pct} style={[styles.percentChip, {borderColor: theme.palette.border}]} onPress={() => applyPercent(pct)}>
              <Typography weight="medium">{pct}%</Typography>
            </Pressable>
          ))}
        </View>

        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 14}}>
          Amount ($)
        </Typography>
        <TextInput
          placeholder="Amount (USD)"
          placeholderTextColor={theme.palette.muted}
          keyboardType="decimal-pad"
          style={[...inputStyle, {marginTop: 8}]}
          value={amount}
          onChangeText={setAmount}
        />
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 10}}>
          Note to admin (optional)
        </Typography>
        <TextInput
          placeholder="Add a note"
          placeholderTextColor={theme.palette.muted}
          style={[...inputStyle, {height: 80, textAlignVertical: 'top', marginTop: 8}]}
          value={note}
          onChangeText={setNote}
          multiline
        />
        <PrimaryButton
          label={mutation.isPending ? 'Submitting...' : 'Confirm reinvest'}
          fullWidth
          style={{marginTop: 14}}
          onPress={mutation.isPending ? undefined : handleSubmit}
          disabled={!amount || mutation.isPending}
        />
        {mutation.isPending ? (
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 8}}>
            Sending reinvest request...
          </Typography>
        ) : null}
      </Surface>

      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#DFF3E6'}]}>
            <Ionicons name="refresh" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Keep Earnings Working</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
              Roll profits back into your portfolio to grow balance without new deposits.
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
    borderRadius: 16,
    marginBottom: 8,
  },
  input: {
    borderWidth: 1,
    borderRadius: 14,
    paddingVertical: 14,
    paddingHorizontal: 14,
  },
  percentRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 12,
  },
  percentChip: {
    paddingVertical: 10,
    paddingHorizontal: 14,
    borderRadius: 10,
    borderWidth: 1,
  },
  iconBadge: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default ReinvestScreen;
