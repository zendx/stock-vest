import React from 'react';
import {View, StyleSheet} from 'react-native';
import {Typography} from './Typography';
import {useTheme} from '../theme';

type Props = {
  label: string;
  value: string;
  caption?: string;
};

export const StatCard = ({label, value, caption}: Props) => {
  const theme = useTheme();
  return (
    <View
      style={[
        styles.card,
        {
          backgroundColor: theme.palette.surface,
          borderColor: theme.palette.border,
          borderRadius: theme.radius.lg,
        },
      ]}
    >
      <Typography variant="caption" style={{color: theme.palette.muted}}>
        {label}
      </Typography>
      <Typography variant="title" weight="bold" style={{marginTop: 6}}>
        {value}
      </Typography>
      {caption ? (
        <Typography variant="caption" style={{color: theme.palette.accent, marginTop: 4}}>
          {caption}
        </Typography>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderWidth: 1,
    flex: 1,
  },
});
