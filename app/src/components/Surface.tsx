import React, {ReactNode} from 'react';
import {View, ViewProps, StyleSheet} from 'react-native';
import {useTheme} from '../theme';

type SurfaceProps = ViewProps & {
  children: ReactNode;
  inset?: boolean;
  muted?: boolean;
};

export const Surface = ({children, style, inset = false, muted = false, ...rest}: SurfaceProps) => {
  const theme = useTheme();
  return (
    <View
      style={[
        styles.base,
        {
          backgroundColor: muted ? theme.palette.surfaceAlt : theme.palette.surface,
          borderColor: theme.palette.border,
          borderRadius: theme.radius.lg,
          padding: inset ? theme.spacing[4] : theme.spacing[3],
        },
        style,
      ]}
      {...rest}
    >
      {children}
    </View>
  );
};

const styles = StyleSheet.create({
  base: {
    borderWidth: 1,
  },
});
