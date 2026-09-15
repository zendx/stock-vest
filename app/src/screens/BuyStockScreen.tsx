import React, {useState} from 'react';
import {Alert, StyleSheet, TextInput, View} from 'react-native';
import {useRoute} from '@react-navigation/native';
import {useMutation} from '@tanstack/react-query';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {submitBuyStock} from '../api/portfolio';
import {useSession} from '../hooks/useSession';
import {queryClient} from '../lib/queryClient';
<<<<<<< HEAD
import type {MainStackRouteProp} from '../navigation/types';
import {showFinancialFlowError} from '../lib/financialFlow';

const BuyStockScreen = () => {
  const theme = useTheme();
  const route = useRoute<MainStackRouteProp<'BuyStock'>>();
  const {stock} = route.params;
=======

type StockParam = {
  stock?: {
    id: string;
    name: string;
    price: string;
    rate: string;
    status: string;
  };
};

const BuyStockScreen = () => {
  const theme = useTheme();
  const route = useRoute();
  const {stock} = (route.params as StockParam) || {};
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  const [amount, setAmount] = useState('');
  const [units, setUnits] = useState('');
  const {token} = useSession();

  const mutation = useMutation({
<<<<<<< HEAD
    mutationFn: (payload: {stockId: string; units?: string; amount?: string}) => submitBuyStock(payload, token),
=======
    mutationFn: (payload: {stockId?: string; units?: string; amount?: string}) => submitBuyStock(payload, token),
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['balances']});
      queryClient.invalidateQueries({queryKey: ['transactions']});
      Alert.alert('Order placed', 'Your buy order is captured inside the app.');
    },
<<<<<<< HEAD
    onError: (err: unknown) => showFinancialFlowError(err, 'Stock order', '/wsi/stocks/'),
  });

  const handleSubmit = () => {
    const numericAmount = amount ? Number(amount.replace(/,/g, '')) : undefined;
    const numericUnits = units ? Number(units.replace(/,/g, '')) : undefined;
    const validAmount = numericAmount !== undefined && Number.isFinite(numericAmount) && numericAmount > 0;
    const validUnits = numericUnits !== undefined && Number.isFinite(numericUnits) && numericUnits > 0;
    if (!validAmount && !validUnits) {
      Alert.alert('Add valid order details', 'Specify an amount or number of units greater than zero.');
      return;
    }
    mutation.mutate({
      stockId: stock.id,
=======
    onError: (err: any) => {
      Alert.alert('Order failed', err?.message || 'Unable to place order right now.');
    },
  });

  const handleSubmit = () => {
    if (!amount && !units) {
      Alert.alert('Add order details', 'Specify an amount or units to continue.');
      return;
    }
    mutation.mutate({
      stockId: stock?.id,
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
      units: units.trim() || undefined,
      amount: amount.trim() || undefined,
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
<<<<<<< HEAD
          {stock.name}
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          {`${stock.price} · ${stock.status}`}
=======
          {stock?.name || 'Buy Stock'}
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          {stock ? `${stock.price} · ${stock.status}` : 'Place a buy order without leaving the app.'}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        </Typography>
      </View>

      <Surface>
        <Typography variant="subtitle" weight="medium">
          Order Details
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
<<<<<<< HEAD
          Review the investment amount or units before continuing.
=======
          Connects to the WordPress buy stock flow.
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        </Typography>

        <View style={{gap: 12, marginTop: 14}}>
          <TextInput
            placeholder="Units"
            placeholderTextColor={theme.palette.muted}
            keyboardType="decimal-pad"
            style={inputStyle}
            value={units}
            onChangeText={setUnits}
          />
          <TextInput
            placeholder="Total amount (USD)"
            placeholderTextColor={theme.palette.muted}
            keyboardType="decimal-pad"
            style={inputStyle}
            value={amount}
            onChangeText={setAmount}
          />
        </View>

        <PrimaryButton
          label={mutation.isPending ? 'Placing...' : 'Place order'}
          fullWidth
          style={{marginTop: 16}}
          onPress={mutation.isPending ? undefined : handleSubmit}
          disabled={(!amount && !units) || mutation.isPending}
        />
        {mutation.isPending ? (
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 8}}>
            Submitting buy order...
          </Typography>
        ) : null}
      </Surface>

      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#E7F0FF'}]}>
            <Ionicons name="stats-chart-outline" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Holdings sync</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
<<<<<<< HEAD
              Completed orders appear in Holdings and Activity.
=======
              Orders reflect in holdings and activity, matching stocks.php.
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
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

export default BuyStockScreen;
