import React from 'react';
import {View, StyleSheet} from 'react-native';
import {Typography} from './Typography';
import {StatusPill} from './StatusPill';
import {useTheme} from '../theme';

type Props = {
  title: string;
  subtitle?: string;
  amount?: string;
  status?: 'approved' | 'pending' | 'processing' | 'failed';
};

export const ListItem = ({title, subtitle, amount, status}: Props) => {
  const theme = useTheme();
  const tone =
    status === 'approved' ? 'success' : status === 'pending' ? 'warning' : status === 'failed' ? 'danger' : 'info';
  return (
    <View style={[styles.row, {borderColor: theme.palette.border}]}>
      <View style={{flex: 1}}>
        <Typography variant="subtitle" weight="medium">
          {title}
        </Typography>
        {subtitle ? (
          <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 4}}>
            {subtitle}
          </Typography>
        ) : null}
      </View>
      <View style={{alignItems: 'flex-end', gap: 6}}>
        {amount ? (
          <Typography weight="bold" style={{color: theme.palette.accent}}>
            {amount}
          </Typography>
        ) : null}
        {status ? <StatusPill label={status} tone={tone} /> : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 14,
    borderBottomWidth: 1,
  },
});
