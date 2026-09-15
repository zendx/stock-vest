<<<<<<< HEAD
import React, {useCallback} from 'react';
=======
import React from 'react';
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
import {FlatList, Image, StyleSheet, View} from 'react-native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {useTheme} from '../theme';
import {useHoldings} from '../hooks/useHoldings';

const HoldingsScreen = () => {
  const theme = useTheme();
<<<<<<< HEAD
  const {data: holdings = [], isLoading, error, refetch, isFetching} = useHoldings();

  const handleRefresh = useCallback(() => {
    void refetch();
  }, [refetch]);
=======
  const {data: holdings = [], isLoading, error} = useHoldings();
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd

  const renderHolding = ({item}: any) => (
    <Surface style={styles.card}>
      <View style={styles.row}>
        <View style={styles.thumbWrap}>
          {item.image ? (
            <Image source={{uri: item.image}} style={styles.thumb} />
          ) : (
            <Typography weight="bold" style={{color: theme.palette.primary}}>
              {item.name?.[0]?.toUpperCase() || 'H'}
            </Typography>
          )}
        </View>
        <View style={{flex: 1}}>
          <Typography weight="medium">{item.name}</Typography>
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
            Added {item.createdAt}
          </Typography>
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 2}}>
            Shares: {item.shares} • Status: {item.status}
          </Typography>
        </View>
        <View style={{alignItems: 'flex-end'}}>
          <Typography weight="bold">{item.invested}</Typography>
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
            Profit {item.profit}
          </Typography>
          {item.currentPrice ? (
            <Typography variant="caption" style={{color: item.status === 'closed' ? theme.palette.muted : theme.palette.success, marginTop: 4}}>
              {item.currentPrice} ({item.rate})
            </Typography>
          ) : null}
        </View>
      </View>
    </Surface>
  );

  return (
<<<<<<< HEAD
    <Screen scroll={false} bottomInset={false}>
=======
    <Screen>
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Holdings
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
<<<<<<< HEAD
          Track invested value, shares, profit, and current rates.
        </Typography>
      </View>
      <Typography variant="caption" style={{color: theme.palette.muted, marginBottom: 12}}>
        Your current and historical investments.
      </Typography>
      <FlatList
        style={styles.list}
=======
          Mirrors holdings.php with invested, shares, profit, and rate.
        </Typography>
      </View>
      <Typography variant="caption" style={{color: theme.palette.muted, marginBottom: 12}}>
        Pulled from the WordPress holdings table.
      </Typography>
      <FlatList
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
        data={holdings}
        keyExtractor={(item) => item.id}
        renderItem={renderHolding}
        ItemSeparatorComponent={() => <View style={{height: 10}} />}
        ListEmptyComponent={() =>
          !isLoading && (
            <Surface muted>
              <Typography variant="caption" style={{color: theme.palette.muted}}>
                {error ? 'Unable to load holdings.' : 'No holdings yet.'}
              </Typography>
            </Surface>
          )
        }
<<<<<<< HEAD
        refreshing={isFetching}
        onRefresh={handleRefresh}
=======
        refreshing={isLoading}
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
      />
      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#E7F0FF'}]}>
            <Ionicons name="pie-chart-outline" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Portfolio snapshot</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
<<<<<<< HEAD
              Holdings stay in sync with your COFCO Capital account.
=======
              Data stays in sync with holdings.php and WordPress tables.
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
            </Typography>
          </View>
        </View>
      </Surface>
    </Screen>
  );
};

const styles = StyleSheet.create({
<<<<<<< HEAD
  list: {
    flex: 1,
  },
=======
>>>>>>> 78468fb11cd1afb0eec0af2a3b55e12954a970cd
  hero: {
    padding: 16,
    borderRadius: 18,
    marginBottom: 12,
  },
  card: {
    borderRadius: 14,
    borderWidth: 1,
    padding: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  thumbWrap: {
    width: 48,
    height: 48,
    borderRadius: 12,
    backgroundColor: '#E5F6EC',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  thumb: {
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
  },
  iconBadge: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default HoldingsScreen;
