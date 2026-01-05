import React from 'react';
import {View, StyleSheet, Image, Pressable, useWindowDimensions} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useSession} from '../hooks/useSession';
import {useBalances} from '../hooks/useBalances';
import {useTransactions} from '../hooks/useTransactions';

const formatCurrency = (value: number) => `$${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

const DashboardScreen = () => {
  const {user} = useSession();
  const {data: balances, isLoading, error} = useBalances();
  const {data: transactions = []} = useTransactions();
  const theme = useTheme();
  const navigation = useNavigation();
  const {width} = useWindowDimensions();
  const errorMessage =
    error instanceof Error ? error.message : 'Balance data unavailable. Check API configuration and auth.';

  const balanceView = {
    totalAssets: balances?.totalAssets ?? 0,
    profit: balances?.profit ?? 0,
    available: balances?.available ?? 0,
    net: balances?.net ?? 0,
  };

  const stats = [
    {
      label: 'Total Assets',
      value: formatCurrency(balanceView.totalAssets),
      delta: '+11.7%',
      icon: 'trending-up-outline' as const,
      tone: theme.palette.success,
    },
    {
      label: 'Profit Earned',
      value: formatCurrency(balanceView.profit),
      delta: '+12.5%',
      icon: 'cash-outline' as const,
      tone: theme.palette.success,
    },
    {
      label: 'Available Balance',
      value: formatCurrency(balanceView.available),
      delta: 'Cash',
      icon: 'wallet-outline' as const,
      tone: theme.palette.info,
    },
    {
      label: 'Net Worth',
      value: formatCurrency(balanceView.net),
      delta: '+8.3%',
      icon: 'bar-chart-outline' as const,
      tone: theme.palette.success,
    },
  ];

  const quickActions = [
    {label: 'Deposit', icon: 'arrow-down-circle-outline' as const, color: '#E5F7EE', action: () => navigation.navigate('Deposit' as never)},
    {label: 'Withdraw', icon: 'arrow-up-circle-outline' as const, color: '#FDECEC', action: () => navigation.navigate('Withdraw' as never)},
    {label: 'Reinvest', icon: 'refresh-circle-outline' as const, color: '#E7F0FF', action: () => navigation.navigate('Reinvest' as never)},
    {label: 'More', icon: 'ellipsis-horizontal-circle-outline' as const, color: '#F4F5F7', action: () => navigation.navigate('Settings' as never)},
  ];

  const compactActionText = width < 380;
  const quickActionTextColor = theme.mode === 'dark' ? '#0F172A' : theme.palette.text;

  const recentTransactions = transactions.slice(0, 5);

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
          <View>
            <Typography variant="caption" style={{color: '#D1FAE5'}}>
              Welcome back,
            </Typography>
            <Typography variant="subtitle" weight="bold" style={{color: '#fff', marginTop: 6}}>
              {user?.name || 'Investor'}
            </Typography>
          </View>
          <View style={styles.avatar}>
            <Image source={require('../../assets/logo.png')} style={{width: 30, height: 30, resizeMode: 'contain'}} />
          </View>
        </View>

        <Surface style={[styles.balanceCard, {backgroundColor: '#1F8D45', borderColor: '#1F8D45'}]}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
            <Ionicons name="card-outline" size={18} color="#D1FAE5" />
            <Typography variant="caption" style={{color: '#D1FAE5'}}>
              Total Assets
            </Typography>
          </View>
          <Typography variant="title" weight="bold" style={{color: '#fff', marginTop: 6}}>
            {formatCurrency(balanceView.totalAssets)}
          </Typography>
          <Typography variant="caption" style={{color: '#D1FAE5', marginTop: 6}}>
            {isLoading ? 'Syncing...' : `+${formatCurrency(balanceView.profit)}`}
          </Typography>
        </Surface>
      </View>

      <Surface style={{marginTop: theme.spacing[4]}}>
        <Typography variant="subtitle" weight="medium" style={{marginBottom: 12}}>
          Quick Actions
        </Typography>
        <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
          {quickActions.map((action) => (
            <Pressable key={action.label} style={[styles.actionTile, {backgroundColor: action.color}]} onPress={action.action}>
              <View style={[styles.actionIcon, {backgroundColor: '#fff'}]}>
                <Ionicons name={action.icon} size={20} color={theme.palette.primary} />
              </View>
              <Typography
                variant="caption"
                style={{
                  color: quickActionTextColor,
                  marginTop: 8,
                  fontSize: 8,
                  textAlign: 'center',
                  fontWeight: '600',
                  letterSpacing: 0.1,
                }}
              >
                {action.label}
              </Typography>
            </Pressable>
          ))}
        </View>
      </Surface>

      <View style={{marginTop: theme.spacing[4]}}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12}}>
          <Typography variant="subtitle" weight="medium">
            Portfolio Overview
          </Typography>
          <Typography variant="caption" style={{color: theme.palette.primary}}>
            See All
          </Typography>
        </View>
        <View style={styles.statGrid}>
          {stats.map((item) => (
            <Surface key={item.label} style={styles.statCard}>
              <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
                <Typography variant="caption" style={{color: theme.palette.muted}}>
                  {item.label}
                </Typography>
                <View style={[styles.statBadge, {backgroundColor: item.tone + '22'}]}>
                  <Ionicons name={item.icon} size={14} color={item.tone} />
                </View>
              </View>
              <Typography variant="subtitle" weight="bold" style={{marginTop: 8}}>
                {item.value}
              </Typography>
              <Typography variant="caption" style={{color: item.tone, marginTop: 6}}>
                {isLoading ? 'Loading...' : item.delta}
              </Typography>
            </Surface>
          ))}
        </View>
      </View>

      <View style={{marginTop: theme.spacing[4], marginBottom: theme.spacing[2]}}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12}}>
          <Typography variant="subtitle" weight="medium">
            Recent Transactions
          </Typography>
          <Pressable onPress={() => navigation.navigate('Activity' as never)}>
            <Typography variant="caption" style={{color: theme.palette.primary}}>
              View All
            </Typography>
          </Pressable>
        </View>
        {recentTransactions.map((tx) => {
          const numeric = parseFloat((tx.amount || '0').replace(/[^0-9.-]/g, ''));
          const isPositive = !Number.isNaN(numeric) ? numeric >= 0 : true;
          return (
            <Pressable
              key={tx.id}
              onPress={() => navigation.navigate('TransactionDetail' as never, {tx} as never)}
              style={{marginBottom: 10}}
            >
              <Surface style={styles.txCard}>
                <View style={styles.txAvatar}>
                  <Typography weight="bold" style={{color: theme.palette.primary}}>
                    {tx.title?.[0]?.toUpperCase() || 'T'}
                  </Typography>
                </View>
                <View style={{flex: 1}}>
                  <Typography weight="medium">{tx.title}</Typography>
                  <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
                    {tx.date}
                  </Typography>
                </View>
                <Typography weight="bold" style={{color: isPositive ? theme.palette.success : theme.palette.danger}}>
                  {tx.amount}
                </Typography>
              </Surface>
            </Pressable>
          );
        })}
        {!isLoading && recentTransactions.length === 0 ? (
          <Surface muted style={{marginTop: 8}}>
            <Typography variant="caption" style={{color: theme.palette.muted}}>
              No transactions yet. Mirror the WordPress ledger by performing a deposit or purchase.
            </Typography>
          </Surface>
        ) : null}
      </View>

      {error ? (
        <Surface muted style={{marginTop: 12}}>
          <Typography variant="body" style={{color: theme.palette.warning}}>
            {errorMessage}
          </Typography>
        </Surface>
      ) : null}
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 18,
    borderRadius: 22,
    overflow: 'hidden',
  },
  balanceCard: {
    marginTop: 18,
    borderWidth: 1,
  },
  actionTile: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 16,
    width: '23%',
  },
  actionIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  statCard: {
    width: '48%',
    borderRadius: 16,
    borderWidth: 1,
  },
  statBadge: {
    width: 28,
    height: 28,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  txCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 10,
  },
  txAvatar: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: '#E5F6EC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatar: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#ffffff22',
    borderWidth: 1,
    borderColor: '#ffffff44',
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default DashboardScreen;
