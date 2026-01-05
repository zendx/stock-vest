import React, {useMemo, useState} from 'react';
import {FlatList, View, StyleSheet, TextInput} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {Ionicons} from '@expo/vector-icons';
import {Screen} from '../components/Screen';
import {Typography} from '../components/Typography';
import {Surface} from '../components/Surface';
import {PrimaryButton} from '../components/PrimaryButton';
import {useTheme} from '../theme';
import {useStocks} from '../hooks/useStocks';

const StocksScreen = () => {
  const theme = useTheme();
  const {data: stocks = [], isLoading, error} = useStocks();
  const [query, setQuery] = useState('');
  const navigation = useNavigation();

  const filtered = useMemo(
    () =>
      stocks.filter(
        (item) =>
          item.name.toLowerCase().includes(query.toLowerCase()) ||
          item.status.toLowerCase().includes(query.toLowerCase()),
      ),
    [stocks, query],
  );

  return (
    <Screen scroll={false}>
      <View style={[styles.hero, {backgroundColor: theme.palette.primary}]}>
        <Typography variant="subtitle" weight="bold" style={{color: '#fff'}}>
          Stocks
        </Typography>
        <View style={[styles.search, {backgroundColor: '#1F8D45'}]}>
          <Ionicons name="search-outline" size={18} color="#E7F6ED" />
          <TextInput
            placeholder="Search stocks..."
            placeholderTextColor="#D1FAE5"
            style={{flex: 1, color: '#fff', marginHorizontal: 10}}
            value={query}
            onChangeText={setQuery}
          />
          <Ionicons name="options-outline" size={18} color="#E7F6ED" />
        </View>
      </View>

      <Typography variant="subtitle" weight="medium" style={{marginTop: 18, marginBottom: 10}}>
        Available Stocks
      </Typography>

      <FlatList
        data={filtered}
        keyExtractor={(item) => item.id}
        ItemSeparatorComponent={() => <View style={{height: 12}} />}
        ListEmptyComponent={() =>
          !isLoading && (
            <Typography variant="body" style={{color: theme.palette.muted}}>
              {error ? 'Unable to load stocks. Check API configuration.' : 'No stocks available.'}
            </Typography>
          )
        }
        refreshing={isLoading}
        renderItem={({item}) => (
          <Surface style={styles.stockCard}>
            <View style={styles.stockRow}>
              <View style={styles.stockAvatar}>
                <Typography weight="bold" style={{color: theme.palette.primary}}>
                  {item.name?.[0]?.toUpperCase() || 'S'}
                </Typography>
              </View>
              <View style={{flex: 1}}>
                <Typography weight="medium">{item.name}</Typography>
                <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
                  {item.status.toUpperCase()}
                </Typography>
              </View>
              <View style={{alignItems: 'flex-end', gap: 4}}>
                <Typography weight="bold">{item.price}</Typography>
                <Typography variant="caption" style={{color: item.rate.startsWith('-') ? theme.palette.danger : theme.palette.success}}>
                  {item.rate}
                </Typography>
              </View>
              <PrimaryButton
                label="Buy"
                style={{marginLeft: 10, minWidth: 72}}
                compact
                onPress={() => navigation.navigate('BuyStock' as never, {stock: item} as never)}
              />
            </View>
          </Surface>
        )}
      />
    </Screen>
  );
};

const styles = StyleSheet.create({
  hero: {
    padding: 16,
    borderRadius: 18,
  },
  search: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginTop: 12,
  },
  stockRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  stockCard: {
    borderRadius: 16,
    borderWidth: 1,
  },
  stockAvatar: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#E5F6EC',
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default StocksScreen;
