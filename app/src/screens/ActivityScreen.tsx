import React, {useMemo, useState, useEffect, useCallback} from 'react';
import {FlatList, StyleSheet, View, Pressable, RefreshControl} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {useTransactions} from '../hooks/useTransactions';
import {useTheme} from '../theme';

const parseAmount = (amount?: string) => {
  if (!amount) return 0;
  const value = parseFloat(amount.replace(/[^0-9.-]/g, ''));
  return Number.isFinite(value) ? value : 0;
};

const ActivityScreen = () => {
  const theme = useTheme();
  const {data: transactions = [], isLoading, error, refetch, isFetching} = useTransactions();
  const navigation = useNavigation();
  const [page, setPage] = useState(0);
  const [refreshing, setRefreshing] = useState(false);

  const summary = useMemo(() => {
    let totalEarned = 0;
    let totalSpent = 0;
    transactions.forEach((tx) => {
      const value = parseAmount(tx.amount);
      if (value >= 0) totalEarned += value;
      if (value < 0) totalSpent += Math.abs(value);
    });
    const net = totalEarned - totalSpent;
    return {totalEarned, totalSpent, net};
  }, [transactions]);

  const pageSize = 10;
  const totalPages = Math.max(1, Math.ceil(transactions.length / pageSize));
  useEffect(() => {
    if (page >= totalPages) {
      setPage(Math.max(0, totalPages - 1));
    }
  }, [page, totalPages]);
  const paginatedTransactions = useMemo(() => {
    const start = page * pageSize;
    return transactions.slice(start, start + pageSize);
  }, [transactions, page, pageSize]);
  const showPagination = transactions.length > pageSize;
  const canPrev = page > 0;
  const canNext = page < totalPages - 1 && transactions.length > (page + 1) * pageSize;

  const handleRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await refetch();
    } finally {
      setRefreshing(false);
    }
  }, [refetch]);

  const handlePrev = () => {
    if (canPrev) setPage((prev) => prev - 1);
  };

  const handleNext = () => {
    if (canNext) setPage((prev) => prev + 1);
  };

  const renderCard = (label: string, value: number) => (
    <Surface style={[styles.summaryCard, {borderColor: theme.palette.border}]}>
      <Typography variant="caption" style={{color: '#E7F6ED'}}>
        {label}
      </Typography>
      <Typography variant="subtitle" weight="bold" style={{marginTop: 6, color: '#fff'}}>
        ${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
      </Typography>
    </Surface>
  );

  return (
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
          <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
            Transactions
          </Typography>
          <Ionicons name="calendar-outline" size={22} color="#E7F6ED" />
        </View>
        <View style={styles.summaryRow}>
          {renderCard('Total Spent', summary.totalSpent)}
          {renderCard('Total Earned', summary.totalEarned)}
          {renderCard('Net Profit', summary.net)}
        </View>
      </View>

      <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 14, marginBottom: 10}}>
        Tap any transaction to view full details.
      </Typography>

      <FlatList
        data={paginatedTransactions}
        keyExtractor={(item) => item.id}
        renderItem={({item}) => {
          const value = parseAmount(item.amount);
          const isPositive = value >= 0;
          return (
            <Pressable onPress={() => navigation.navigate('TransactionDetail' as never, {tx: item} as never)}>
              <Surface style={styles.txCard}>
                <View style={styles.txAvatar}>
                  <Typography weight="bold" style={{color: theme.palette.primary}}>
                    {item.title?.[0]?.toUpperCase() || 'T'}
                  </Typography>
                </View>
                <View style={{flex: 1}}>
                  <Typography weight="medium">{item.title}</Typography>
                  <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
                    {item.date}
                  </Typography>
                </View>
                <Typography weight="bold" style={{color: isPositive ? theme.palette.success : theme.palette.danger}}>
                  {item.amount}
                </Typography>
              </Surface>
            </Pressable>
          );
        }}
        ItemSeparatorComponent={() => <View style={{height: 10}} />}
        ListFooterComponent={() =>
          showPagination ? (
            <View style={styles.paginationRow}>
              <Pressable onPress={handlePrev} disabled={!canPrev} style={[styles.paginationButton, !canPrev && styles.paginationDisabled]}>
                <Ionicons name="arrow-back" size={16} color={canPrev ? theme.palette.primary : theme.palette.muted} />
                <Typography style={{color: canPrev ? theme.palette.primary : theme.palette.muted}}>Prev</Typography>
              </Pressable>
              <Typography variant="caption" style={{color: theme.palette.muted}}>
                Page {page + 1} of {totalPages}
              </Typography>
              <Pressable onPress={handleNext} disabled={!canNext} style={[styles.paginationButton, !canNext && styles.paginationDisabled]}>
                <Typography style={{color: canNext ? theme.palette.primary : theme.palette.muted}}>Next</Typography>
                <Ionicons name="arrow-forward" size={16} color={canNext ? theme.palette.primary : theme.palette.muted} />
              </Pressable>
            </View>
          ) : null
        }
        refreshControl={
          <RefreshControl
            refreshing={refreshing || isFetching}
            onRefresh={handleRefresh}
            tintColor={theme.palette.accent}
          />
        }
        ListEmptyComponent={() =>
          !isLoading && (
            <Typography variant="body" style={{color: theme.palette.muted}}>
              {error ? 'Unable to load activity. Check API connectivity and auth token.' : 'No transactions yet.'}
            </Typography>
          )
        }
      />
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
  },
  summaryRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 14,
  },
  summaryCard: {
    flex: 1,
    borderRadius: 14,
    borderWidth: 1,
    backgroundColor: '#1F8D45',
  },
  txCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 14,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  txAvatar: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: '#E5F6EC',
    alignItems: 'center',
    justifyContent: 'center',
  },
  viewAllButton: {
    marginTop: 6,
    alignSelf: 'center',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
});

export default ActivityScreen;
