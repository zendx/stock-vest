import React, {useMemo, useState} from 'react';
import {View, StyleSheet, TextInput, Pressable} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useBalances} from '../hooks/useBalances';

const currency = (value: number) =>
  `$${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

const WalletScreen = () => {
  const theme = useTheme();
  const {data: balances, isLoading, error} = useBalances();
  const [amount, setAmount] = useState('');
  const navigation = useNavigation();

  const view = useMemo(
    () => ({
      available: balances?.available ?? 0,
      profit: balances?.profit ?? 0,
      totalAssets: balances?.totalAssets ?? 0,
    }),
    [balances],
  );

  const lockedAssets = Math.max(view.totalAssets - view.available, 0);

  const applyPercent = (percent: number) => {
    const value = (view.profit * percent) / 100;
    setAmount(value ? value.toFixed(2) : '');
  };

  const infoText =
    'Reinvesting shifts funds from profit balance into your main balance. Wire this to your reinvest endpoint in the plugin.';

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Wallet
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Deposit, withdraw, and reinvest with the same rules as the WordPress flow.
        </Typography>
      </View>

      <View style={{flexDirection: 'row', gap: 12, marginTop: 16}}>
        <Surface style={styles.smallCard}>
          <Typography variant="caption" style={{color: theme.palette.muted}}>
            Available Balance
          </Typography>
          <Typography variant="subtitle" weight="bold" style={{marginTop: 6}}>
            {currency(view.available)}
          </Typography>
        </Surface>
        <Surface style={styles.smallCard}>
          <Typography variant="caption" style={{color: theme.palette.muted}}>
            Profit
          </Typography>
          <Typography variant="subtitle" weight="bold" style={{marginTop: 6, color: theme.palette.success}}>
            {currency(view.profit)}
          </Typography>
        </Surface>
      </View>

      <Surface style={{marginTop: 12}}>
        <Typography variant="subtitle" weight="medium">
          Locked assets
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 4}}>
          Funds currently farming
        </Typography>
        <Typography variant="title" weight="bold" style={{marginTop: 10}}>
          {currency(lockedAssets)}
        </Typography>
        <View style={{flexDirection: 'row', gap: 10, marginTop: 12}}>
          <PrimaryButton label="Deposit" style={{flex: 1}} onPress={() => navigation.navigate('Deposit' as never)} />
          <PrimaryButton label="Withdraw" style={{flex: 1}} onPress={() => navigation.navigate('Withdraw' as never)} />
        </View>
      </Surface>

      <Surface style={{marginTop: 16}}>
        <Typography variant="subtitle" weight="medium">
          Reinvest Profit
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 6}}>
          Move profit balance into your main balance to keep it working for you.
        </Typography>

        <View style={{marginTop: 12}}>
          <Typography variant="caption" style={{color: theme.palette.muted}}>
            Available Profit Balance
          </Typography>
          <Typography variant="title" weight="bold" style={{marginTop: 4, color: theme.palette.success}}>
            {currency(view.profit)}
          </Typography>
        </View>

        <View style={styles.percentRow}>
          {[25, 50, 100].map((p) => (
            <Pressable key={p} onPress={() => applyPercent(p)} style={[styles.percentChip, {borderColor: theme.palette.border}]}>
              <Typography weight="medium">{p}%</Typography>
            </Pressable>
          ))}
        </View>

        <View style={[styles.inputWrap, {borderColor: theme.palette.border, backgroundColor: theme.palette.surfaceAlt}]}>
          <Ionicons name="cash-outline" size={18} color={theme.palette.muted} />
          <TextInput
            placeholder="Amount ($)"
            placeholderTextColor={theme.palette.muted}
            keyboardType="decimal-pad"
            value={amount}
            onChangeText={setAmount}
            style={{flex: 1, color: theme.palette.text, marginLeft: 10}}
          />
        </View>

        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 8}}>
          {infoText}
        </Typography>
        <PrimaryButton label="Reinvest" fullWidth style={{marginTop: 12}} onPress={() => navigation.navigate('Reinvest' as never)} />
      </Surface>

      <Surface muted style={{marginTop: 16}}>
        <Typography variant="subtitle" weight="medium">
          Methods
        </Typography>
        <Typography variant="body" style={{color: theme.palette.muted, marginTop: 8}}>
          Support for bank/manual deposits, USDT (TRC/ERC), ETH, SOL - mirroring plugin settings. Wire the buttons to
          REST endpoints for submission + status tracking.
        </Typography>
      </Surface>

      {isLoading ? (
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 10}}>
          Syncing balances...
        </Typography>
      ) : null}
      {error ? (
        <Typography variant="caption" style={{color: theme.palette.warning, marginTop: 8}}>
          Unable to load balances. Set EXPO_PUBLIC_API_BASE_URL and ensure auth tokens are valid.
        </Typography>
      ) : null}
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
  },
  smallCard: {
    flex: 1,
    borderRadius: 16,
    borderWidth: 1,
  },
  percentRow: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 12,
  },
  percentChip: {
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 12,
    borderWidth: 1,
  },
  inputWrap: {
    marginTop: 12,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
});

export default WalletScreen;
