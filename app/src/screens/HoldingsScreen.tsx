import React from 'react';
import {FlatList, Image, StyleSheet, View} from 'react-native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Surface} from '../components/Surface';
import {Typography} from '../components/Typography';
import {useTheme} from '../theme';
import {useHoldings} from '../hooks/useHoldings';

const HoldingsScreen = () => {
  const theme = useTheme();
  const {data: holdings = [], isLoading, error} = useHoldings();

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
    <Screen>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Holdings
        </Typography>
        <Typography variant="caption" style={{color: '#E7F6ED', marginTop: 6}}>
          Mirrors holdings.php with invested, shares, profit, and rate.
        </Typography>
      </View>
      <Typography variant="caption" style={{color: theme.palette.muted, marginBottom: 12}}>
        Pulled from the WordPress holdings table.
      </Typography>
      <FlatList
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
        refreshing={isLoading}
      />
      <Surface muted style={{marginTop: 12}}>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
          <View style={[styles.iconBadge, {backgroundColor: '#E7F0FF'}]}>
            <Ionicons name="pie-chart-outline" size={18} color={theme.palette.primary} />
          </View>
          <View style={{flex: 1}}>
            <Typography weight="medium">Portfolio snapshot</Typography>
            <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
              Data stays in sync with holdings.php and WordPress tables.
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
