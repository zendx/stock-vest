import React from 'react';
import {RouteProp, useRoute} from '@react-navigation/native';
import {View, StyleSheet} from 'react-native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {useTheme} from '../theme';
import {Transaction} from '../types';

type ParamList = {
  TransactionDetail: {
    tx: Transaction;
  };
};

const TransactionDetailScreen = () => {
  const theme = useTheme();
  const route = useRoute<RouteProp<ParamList, 'TransactionDetail'>>();
  const tx = route.params?.tx;

  if (!tx) return null;

  const rows = [
    {label: 'Description', value: tx.note || tx.title || '—'},
    {label: 'ID', value: tx.id},
    {label: 'Date', value: tx.date},
    {label: 'Amount', value: tx.amount},
  ];

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Transaction
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Full details from your WordPress ledger.
        </Typography>
      </View>

      <Surface style={styles.card}>
        <Typography variant="subtitle" weight="bold">
          Transaction Details
        </Typography>
        <View style={{marginTop: 12, gap: 10}}>
          {rows.map((row) => (
            <View key={row.label} style={styles.row}>
              <Typography variant="caption" style={{color: theme.palette.muted}}>
                {row.label}
              </Typography>
              <Typography weight="medium">{row.value}</Typography>
            </View>
          ))}
        </View>
      </Surface>

      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#E7F0FF'}]}>
            <Ionicons name="time-outline" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">History</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
              Tap transactions in Activity to view details like on transactions.php.
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
  card: {
    borderRadius: 14,
    borderWidth: 1,
    padding: 14,
  },
  row: {
    borderBottomWidth: 1,
    borderBottomColor: '#EEF1F5',
    paddingBottom: 8,
  },
  iconBadge: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default TransactionDetailScreen;
